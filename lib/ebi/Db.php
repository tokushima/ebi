<?php
namespace ebi;

class Db implements \Iterator{
	static private $autocommit;
	
	private ?string $dbname = null;
	private \PDO $connection;
	/**
	 * @var \PDOStatement|bool $statement
	 */
	private $statement;

	/**
	 * @var array|bool $resultset
	 */
	private $resultset;
	private int $resultset_counter;
	private \ebi\DbConnector $connector;
	
	/**
	 * @param string{} $def 接続情報 [type,host,name,port,user,password,sock,encode,timezone]
	 */
	public function __construct(array $def=[]){
		if(empty($def)){
			/**
			 * @param string{} $connection デフォルトの接続情報 [type,host,name,port,user,password,sock,encode,timezone]
			 */
			$def = \ebi\Conf::gets('connection');
		}
		$type = $def['type'] ?? null;
		$host = $def['host'] ?? null;
		$dbname = $def['name'] ?? null;
		$port = $def['port'] ?? null;
		$user = $def['user'] ?? null;
		$password = $def['password'] ?? null;
		$sock = $def['sock'] ?? null;
		$encode = $def['encode'] ?? null;
		$timezone = $def['timezone'] ?? null;
		
		if(empty($type)){
			$type = \ebi\SqliteConnector::type();
		}
		if(empty($encode)){
			$encode = 'utf8';
		}
		
		$r = new \ReflectionClass($type);
		$this->dbname = $dbname;
		$this->connector = $r->newInstanceArgs([$encode, $timezone]);
		
		if(!($this->connector instanceof \ebi\DbConnector)){
			throw new \ebi\exception\ConnectionException('must be an instance of \ebi\DbConnector');
		}
		if(self::$autocommit === null){
			/**
			 * @param bool $autocommit オートコミットを行うかの真偽値
			 */
			self::$autocommit = \ebi\Conf::get('autocommit',false);
		}
		$this->connection = $this->connector->connect($this->dbname, $host, $port, $user, $password, $sock, self::$autocommit);
		
		if(empty($this->connection)){
			throw new \ebi\exception\ConnectionException('connection fail '.$this->dbname);
		}
		if(self::$autocommit !== true){
			$this->connection->beginTransaction();
		}
	}

	/**
	 * 接続DB名
	 */
	public function name(): string{
		return $this->dbname;
	}

	/**
	 * 接続モジュール
	 */
	public function connector(): \ebi\DbConnector{
		return $this->connector;
	}

	public function __destruct(){
		if($this->connection !== null){
			try{
				$this->connection->commit();
			}catch(\Exception $e){}
		}
	}

	/**
	 * コミットする
	 */
	public function commit(): void{
		if(!self::$autocommit){
			$this->connection->commit();
			$this->connection->beginTransaction();
		}
	}

	/**
	 * ロールバックする
	 */
	public function rollback(): void{
		if(!self::$autocommit){
			$this->connection->rollBack();
			$this->connection->beginTransaction();
		}
	}

	/**
	 * 文を実行する準備を行う
	 */
	public function prepare(string $sql): \PDOStatement{
		return $this->connection->prepare($sql);
	}
	
	/**
	 * 直近の操作に関連する SQLSTATE を取得する
	 */
	public function error_code(): ?string{
		if($this->statement === false){
			return null;
		}
		return $this->statement->errorCode();
	}

	/**
	 * SQL ステートメントを実行する
	 */
	public function query(string $sql): self{
		$args = func_get_args();
		$this->statement = $this->prepare($sql);
		
		if($this->statement === false){
			throw new \ebi\exception\InvalidQueryException($sql);
		}
		array_shift($args);
		$this->statement->execute($args);
		$errors = $this->statement->errorInfo();
		
		if(isset($errors[1])){
			$this->rollback();
			throw new \ebi\exception\InvalidQueryException('['.$errors[1].'] '.($errors[2] ?? '').' : '.$sql);
		}
		return $this;
	}

	/**
	 * 直前に実行したSQL ステートメントに値を変更して実行する
	 */
	public function re(...$args): self{
		if(!isset($this->statement)){
			throw new \ebi\exception\BadMethodCallException('undefined statement');
		}
		$this->statement->execute($args);
		$errors = $this->statement->errorInfo();
		
		if(isset($errors[1])){
			$this->rollback();
			throw new \ebi\exception\InvalidQueryException('['.$errors[1].'] '.($errors[2] ?? '').' : #requery');
		}
		return $this;
	}

	/**
	 * 結果セットから次の行を取得する
	 * @return mixed
	 */
	public function next_result(?string $target_property=null){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
		
		if($this->resultset !== false){
			if($target_property === null){
				return $this->resultset;
			}
			return (isset($this->resultset[$target_property])) ? $this->resultset[$target_property] : null;
		}
		return null;
	}


	public function rewind(): void{
		$this->resultset_counter = 0;
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}

	#[\ReturnTypeWillChange]
	public function current(){
		return $this->resultset;
	}

	#[\ReturnTypeWillChange]
	public function key(){
		return $this->resultset_counter++;
	}
	/**
	 * @see \Iterator
	 */
	public function valid(): bool{
		return ($this->resultset !== false);
	}
	/**
	 * @see \Iterator
	 */
	public function next(): void{
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
}

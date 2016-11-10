<?php
namespace ebi;
/**
 * DBコントローラ
 * @author tokushima
 */
class Db implements \Iterator{
	static private $autocommit;
	
	private $dbname;	
	private $connection;
	private $statement;
	private $resultset;
	private $resultset_counter;
	private $connector;
	
	/**
	 * コンストラクタ
	 * @param string{} $def 接続情報 [type,host,name,port,user,password,sock,encode,timezone]
	 */
	public function __construct(array $def=[]){
		if(empty($def)){
			/**
			 * @param string{} $connection デフォルトの接続情報 [type,host,name,port,user,password,sock,encode,timezone]
			 */
			$def = \ebi\Conf::gets('connection');
		}
		$type = isset($def['type']) ? $def['type'] : null;
		$host = isset($def['host']) ? $def['host'] : null;
		$dbname = isset($def['name']) ? $def['name'] : null;
		$port = isset($def['port']) ? $def['port'] : null;
		$user = isset($def['user']) ? $def['user'] : null;
		$password = isset($def['password']) ? $def['password'] : null;
		$sock = isset($def['sock']) ? $def['sock'] : null;
		$encode = isset($def['encode']) ? $def['encode'] : null;
		$timezone = isset($def['timezone']) ? $def['timezone'] : null;
		
		if(empty($type)){
			$type = \ebi\SqliteConnector::type();
		}
		if(empty($encode)){
			$encode = 'utf8';
		}
		try{
			$type = \ebi\Util::get_class_name($type);
		}catch(\InvalidArgumentException $e){
			throw new \ebi\exception\ConnectionException('could not find connector `'.$type.'`');
		}
		
		$r = new \ReflectionClass($type);
		$this->dbname = $dbname;
		$this->connector = $r->newInstanceArgs([$encode,$timezone]);
		
		if(!($this->connector instanceof \ebi\DbConnector)){
			throw new \ebi\exception\ConnectionException('must be an instance of \ebi\DbConnector');
		}
		if(self::$autocommit === null){
			/**
			 * @param boolean $autocommit オートコミットを行うかの真偽値
			 */
			self::$autocommit = \ebi\Conf::get('autocommit',false);
		}
		$this->connection = $this->connector->connect($this->dbname,$host,$port,$user,$password,$sock,self::$autocommit);
		
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
	public function name(){
		return $this->dbname;
	}
	/**
	 * 接続モジュール
	 */
	public function connector(){
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
	public function commit(){
		if(!self::$autocommit){
			$this->connection->commit();
			$this->connection->beginTransaction();
		}
	}
	/**
	 * ロールバックする
	 */
	public function rollback(){
		if(!self::$autocommit){
			$this->connection->rollBack();
			$this->connection->beginTransaction();
		}
	}
	/**
	 * 文を実行する準備を行う
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function prepare($sql){
		return $this->connection->prepare($sql);
	}
	/**
	 * SQL ステートメントを実行する
	 * @param string $sql 実行するSQL
	 */
	public function query($sql){
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
			throw new \ebi\exception\InvalidQueryException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').' : '.$sql);
		}
		return $this;
	}
	/**
	 * 直前に実行したSQL ステートメントに値を変更して実行する
	 */
	public function re(){
		if(!isset($this->statement)){
			throw new \ebi\exception\BadMethodCallException('undefined statement');
		}
		$args = func_get_args();
		$this->statement->execute($args);
		$errors = $this->statement->errorInfo();
		
		if(isset($errors[1])){
			$this->rollback();
			throw new \ebi\exception\InvalidQueryException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').' : #requery');
		}
		return $this;
	}
	/**
	 * 結果セットから次の行を取得する
	 * @param string $name 特定のカラム名
	 * @return string/arrray
	 */
	public function next_result($name=null){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
		
		if($this->resultset !== false){
			if($name === null){
				return $this->resultset;
			}
			return (isset($this->resultset[$name])) ? $this->resultset[$name] : null;
		}
		return null;
	}
	/**
	 * @see \Iterator
	 */
	public function rewind(){
		$this->resultset_counter = 0;
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
	/**
	 * @see \Iterator
	 */
	public function current(){
		return $this->resultset;
	}
	/**
	 * @see \Iterator
	 */
	public function key(){
		return $this->resultset_counter++;
	}
	/**
	 * @see \Iterator
	 */
	public function valid(){
		return ($this->resultset !== false);
	}
	/**
	 * @see \Iterator
	 */
	public function next(){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
}

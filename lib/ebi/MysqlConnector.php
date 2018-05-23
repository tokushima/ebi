<?php
namespace ebi;
/**
 * DB接続クラス(MySQL)
 * @author tokushima
 */
class MysqlConnector extends \ebi\DbConnector{
	protected $order_random_str = 'rand()';
	
	/**
	 * @param string $name
	 * @param string $host
	 * @param number $port
	 * @param string $user
	 * @param string $password
	 * @param string $sock
	 * @param boolean $autocommit
	 */
	public function connect($name,$host,$port,$user,$password,$sock,$autocommit){
		if(!extension_loaded('pdo_mysql')){
			throw new \ebi\exception\ConnectionException('pdo_mysql not supported');
		}
		$con = null;
		if(empty($name)){
			throw new \ebi\exception\ConnectionException('undef connection name');
		}
		if(empty($host)){
			$host = 'localhost';
		}
		if(!isset($user) && !isset($password)){
			$user = 'root';
			$password = 'root';
		}
		$dsn = empty($sock) ?
			sprintf('mysql:dbname=%s;host=%s;port=%d',$name,$host,((empty($port) ? 3306 : $port))) :
			sprintf('mysql:dbname=%s;unix_socket=%s',$name,$sock);
		
		try{
			$con = new \PDO($dsn,$user,$password);
			$con->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
			
			if(!$autocommit){
				$this->prepare_execute($con,'set autocommit=0');
				$this->prepare_execute($con,'set session transaction isolation level read committed');
			}
			if(!empty($this->encode)){
				$this->prepare_execute($con,'set names \''.$this->encode.'\'');
			}
			if(!empty($this->timezone)){
				$this->prepare_execute($con,'set time_zone=\''.$this->timezone.'\'');
			}			
		}catch(\PDOException $e){
			throw new \ebi\exception\ConnectionException((strpos($e->getMessage(),'SQLSTATE[HY000]') === false) ? $e->getMessage() : __CLASS__.' connect failed');
		}
		return $con;
	}
	protected function prepare_execute($con,$sql){
		$st = $con->prepare($sql);
		$st->execute();
		
		$errors = $st->errorInfo();
		if(isset($errors[1])){
			throw new \ebi\exception\InvalidArgumentException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').PHP_EOL.'( '.$sql.' )');
		}
	}
	public function last_insert_id_sql(){
		return new \ebi\Daq('select last_insert_id() as last_insert_id');
	}
	
	private function to_column_type($dao,$type,$name){
		switch($type){
			case '':
			case 'mixed':
			case 'string':
				return $this->quotation($name).' VARCHAR('.$dao->prop_anon($name,'max',255).') BINARY';
			case 'alnum':
			case 'text':
				return $this->quotation($name).(($dao->prop_anon($name,'max') !== null) ? ' VARCHAR('.$dao->prop_anon($name,'max').')' : ' MEDIUMTEXT').' BINARY';
			case 'number':
				return $this->quotation($name).' '.(($dao->prop_anon($name,'decimal_places') !== null) ? sprintf('NUMERIC(%d,%d)',26-$dao->prop_anon($name,'decimal_places'),$dao->prop_anon($name,'decimal_places')) : 'DOUBLE');
			case 'serial': return $this->quotation($name).' INT AUTO_INCREMENT';
			case 'boolean': return $this->quotation($name).' INT(1)';
			case 'timestamp': return $this->quotation($name).' DATETIME';
			case 'date': return $this->quotation($name).' DATE';
			case 'time': return $this->quotation($name).' INT';
			case 'intdate':
			case 'integer': return $this->quotation($name).' INT';
			case 'email': return $this->quotation($name).' VARCHAR(255)';
			default:
				throw new \ebi\exception\InvalidArgumentException('undefined type `'.$type.'`');
		}
	}
	/**
	 * create table
	 */
	public function create_table_sql(\ebi\Dao $dao){
		$columndef = $primary = [];
		$sql = 'CREATE TABLE '.$this->quotation($dao->table()).'('.PHP_EOL;

		foreach($dao->columns(true) as $prop_name => $column){
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$this->to_column_type($dao,$dao->prop_anon($prop_name,'type'),$column->column()).' NULL ';
				$columndef[] = $column_str;
				
				if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial'){
					$primary[] = $this->quotation($column->column());
				}
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		if(!empty($primary)){
			$sql .= ' ,PRIMARY KEY ( '.implode(',',$primary).' ) '.PHP_EOL;
		}
		$sql .= ' ) ROW_FORMAT=DYNAMIC ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'.PHP_EOL;
		return $sql;
	}
	public function exists_table_sql(\ebi\Dao $dao){
		$dbc = \ebi\Dao::connection(get_class($dao));
		return sprintf('select count(*) from information_schema.tables where table_name=\'%s\' and table_schema=\'%s\'',$dao->table(),$dbc->name());
	}
	protected function date_format($column_map,$dao,$column,$require){
		$fmt = [];
		$sql = ['Y'=>'%Y','m'=>'%m','d'=>'%d','H'=>'%H','i'=>'%i','s'=>'%s'];
		
		foreach(['Y'=>'2000','m'=>'01','d'=>'01','H'=>'00','i'=>'00','s'=>'00'] as $f => $d){
			$fmt[] = (strpos($require,$f) === false) ? $d : $sql[$f];
		}
		$f = $fmt[0].'-'.$fmt[1].'-'.$fmt[2].'T'.$fmt[3].':'.$fmt[4].':'.$fmt[5];
		return 'DATE_FORMAT('.$column_map.',\''.$f.'\')';
	}
	/**
	 * SQLエラーを解析し適切なExceptionをthrowする
	 * @param mixed[] $error_info 0: SQLSTATE エラーコード, 1:ドライバ固有のエラーコード, 2:ドライバ固有のエラーメッセージ
	 */
	public function parse_invalid_query_exception(array $error_info){
		if($error_info[0] == 23000 && $error_info[1] == 1062){
			throw new \ebi\exception\UniqueException('Duplicate entry');
		}
	}
}
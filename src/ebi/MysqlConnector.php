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
			if(!$autocommit){
				$this->prepare_execute($con,'set autocommit=0');
				$this->prepare_execute($con,'set session transaction isolation level read committed');
			}			
			if(!empty($this->encode)) $this->prepare_execute($con,'set names \''.$this->encode.'\'');
			if(!empty($this->timezone)) $this->prepare_execute($con,'set time_zone=\''.$this->timezone.'\'');
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
	/**
	 * create table
	 */
	public function create_table_sql(\ebi\Dao $dao){
		$quote = function($name){
			return '`'.$name.'`';
		};
		$to_column_type = function($dao,$type,$name) use($quote){
			switch($type){
				case '':
				case 'mixed':
				case 'string':
					return $quote($name).' varchar('.$dao->prop_anon($name,'max',255).')';
				case 'alnum':
				case 'text':
					return $quote($name).(($dao->prop_anon($name,'max') !== null) ? ' varchar('.$dao->prop_anon($name,'max').')' : ' text');
				case 'number':
					return $quote($name).' '.(($dao->prop_anon($name,'decimal_places') !== null) ? sprintf('numeric(%d,%d)',26-$dao->prop_anon($name,'decimal_places'),$dao->prop_anon($name,'decimal_places')) : 'double');
				case 'serial': return $quote($name).' int auto_increment';
				case 'boolean': return $quote($name).' int(1)';
				case 'timestamp': return $quote($name).' timestamp';
				case 'date': return $quote($name).' date';
				case 'time': return $quote($name).' int';
				case 'intdate': 
				case 'integer': return $quote($name).' int';
				case 'email': return $quote($name).' varchar(255)';
				default: throw new \ebi\exception\InvalidArgumentException('undefined type `'.$type.'`');
			}
		};
		$columndef = $primary = array();
		$sql = 'create table '.$quote($dao->table()).'('.PHP_EOL;
		foreach(array_keys($dao->props()) as $prop_name){
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$to_column_type($dao,$dao->prop_anon($prop_name,'type'),$prop_name).' null ';
				$columndef[] = $column_str;
				if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial') $primary[] = $quote($prop_name);
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		if(!empty($primary)) $sql .= ' ,primary key ( '.implode(',',$primary).' ) '.PHP_EOL;
		$sql .= ' ) engine = InnoDB character set utf8 collate utf8_general_ci;'.PHP_EOL;
		return $sql;
	}
	public function exists_table_sql(\ebi\Dao $dao){
		$dbc = \ebi\Dao ::connection(get_class($dao));		
		return sprintf('select count(*) from information_schema.tables where table_name=\'%s\' and table_schema=\'%s\'',$dao->table(),$dbc->name());
	}
}
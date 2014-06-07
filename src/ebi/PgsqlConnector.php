<?php
namespace ebi;
/**
 * DB接続クラス(PostgreSQL)
 * @author tokushima
 */
class PgsqlConnector extends \ebi\DbConnector{
	protected $quotation = '"';
	protected $order_random_str = 'rand()';
	
	/**
	 * @param string $name
	 * @param string $host
	 * @param number $port
	 * @param string $user
	 * @param string $password
	 * @param string $sock
	 */
	public function connect($name,$host,$port,$user,$password,$sock){
		if(!extension_loaded('pdo_pgsql')) throw new \RuntimeException('pdo_pgsql not supported');
		$con = null;
		if(empty($name)) throw new \ebi\exception\InvalidArgumentException('undef connection name');
		if(empty($host)) $host = 'localhost';
		
		$dsn = empty($sock) ?
					sprintf("pgsql:dbname=%s host=%s port=%d",$name,$host,((empty($port) ? 5432 : $port))) :
					sprintf("pgsql:dbname=%s unix_socket=%s",$name,$sock);
		try{
			$con = new \PDO($dsn,$user,$password);
			if(!empty($this->encode)) $this->prepare_execute($con,sprintf("set names '%s'",$this->encode));
		}catch(\PDOException $e){
			throw new \ebi\exception\ConnectionException(__CLASS__.' connect failed');
		}
		return $con;
	}
	private function prepare_execute($con,$sql){
		$st = $con->prepare($sql);
		$st->execute();
		$error = $st->errorInfo();
		if((int)$error[0] !== 0) throw new \ebi\exception\InvalidArgumentException($error[2]);
	}
	public function last_insert_id_sql(){
		return new \ebi\Daq('select lastval() as last_insert_id');
	}
	public function exists_table_sql(\ebi\Dao $dao){
		$dbc = \ebi\Dao ::connection(get_class($dao));
		return sprintf('select * from pg_tables where not tablename like \'%s\'',$dao->table());
	}	
	
	
	
	
	
	
	
	
	/**
	 * create table
	 */
	public function create_table_sql(\ebi\Dao $dao){
		$quote = function($name){
			return '"'.$name.'"';
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
				case 'serial': return $quote($name).' serial';
				case 'boolean': return $quote($name).' int(1)';
				case 'timestamp': return $quote($name).' timestamp';
				case 'date': return $quote($name).' date';
				case 'time': return $quote($name).' int';
				case 'intdate': 
				case 'integer': return $quote($name).' int';
				case 'email': return $quote($name).' varchar(255)';
				default: throw new exception\InvalidArgumentException('undefined type `'.$type.'`');
			}
		};
		$columndef = $primary = array();
		$sql = 'create table '.$quote($dao->table()).'('.PHP_EOL;
		foreach(array_keys($dao->props(false)) as $prop_name){
			$type = $dao->prop_anon($prop_name,'type');
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$to_column_type($dao,$type,$prop_name).($type != 'serial' ? ' null ' : '');
				$columndef[] = $column_str;
				if($dao->prop_anon($prop_name,'primary') === true || $type != 'serial'){
					$primary[] = $quote($prop_name);
				}
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		if(!empty($primary)) $sql .= ' ,primary key ( '.implode(',',$primary).' ) '.PHP_EOL;
		$sql .= ' );'.PHP_EOL;
		return $sql;
	}
	protected function select_option_sql($paginator,$order){
		return ' '
				.(empty($order) ? '' : ' order by '.implode(',',$order))
				.(($paginator instanceof \ebi\Paginator) ? sprintf(" offset %d limit %d ",$paginator->offset(),$paginator->limit()) : '')
				;
	}
	public function create_sql(\ebi\Dao $dao){
		$insert = $vars = array();
		$autoid = null;
		foreach($dao->self_columns() as $column){
			if(!$column->auto()){
				$insert[] = $this->quotation($column->column());
				$vars[] = $this->update_value($dao,$column->name());
			}
		}
		return new \ebi\Daq('insert into '.$this->quotation($column->table()).' ('.implode(',',$insert).') values ('.implode(',',array_fill(0,sizeof($insert),'?')).');'
				,$vars
		);
	}
}
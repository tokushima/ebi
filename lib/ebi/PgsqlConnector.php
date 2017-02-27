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
	 * @param boolean $autocommit
	 */
	public function connect($name,$host,$port,$user,$password,$sock,$autocommit){
		if(!extension_loaded('pdo_pgsql')){
			throw new \ebi\exception\ConnectionException('pdo_pgsql not supported');
		}
		$con = null;
		if(empty($name)){
			throw new \ebi\exception\InvalidArgumentException('undef connection name');
		}
		if(empty($host)){
			$host = 'localhost';
		}
		
		$dsn = empty($sock) ?
					sprintf("pgsql:dbname=%s host=%s port=%d",$name,$host,((empty($port) ? 5432 : $port))) :
					sprintf("pgsql:dbname=%s unix_socket=%s",$name,$sock);
		try{
			$con = new \PDO($dsn,$user,$password);
			$con->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
			
			if(!empty($this->encode)){
				$this->prepare_execute($con,sprintf("set names '%s'",$this->encode));
			}
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
		$dbc = \ebi\Dao::connection(get_class($dao));
		return sprintf('select * from pg_tables where not tablename like \'%s\'',$dao->table());
	}	

	private function to_column_type($dao,$type,$name){
		switch($type){
			case '':
			case 'mixed':
			case 'string':
				return $this->quotation($name).' varchar('.$dao->prop_anon($name,'max',255).')';
			case 'alnum':
			case 'text':
				return $this->quotation($name).(($dao->prop_anon($name,'max') !== null) ? ' varchar('.$dao->prop_anon($name,'max').')' : ' text');
			case 'number':
				return $this->quotation($name).' '.(($dao->prop_anon($name,'decimal_places') !== null) ? sprintf('numeric(%d,%d)',26-$dao->prop_anon($name,'decimal_places'),$dao->prop_anon($name,'decimal_places')) : 'double');
			case 'serial': return $this->quotation($name).' serial';
			case 'boolean': return $this->quotation($name).' int(1)';
			case 'timestamp': return $this->quotation($name).' timestamp';
			case 'date': return $this->quotation($name).' date';
			case 'time': return $this->quotation($name).' int';
			case 'intdate':
			case 'integer': return $this->quotation($name).' int';
			case 'email': return $this->quotation($name).' varchar(255)';
			default: throw new exception\InvalidArgumentException('undefined type `'.$type.'`');
		}	
	}
	/**
	 * create table
	 */
	public function create_table_sql(\ebi\Dao $dao){
		$columndef = $primary = [];
		$sql = 'CREATE TABLE '.$this->quotation($dao->table()).'('.PHP_EOL;
		
		foreach($dao->columns(true) as $prop_name => $column){
			$type = $dao->prop_anon($prop_name,'type');
			
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$this->to_column_type($dao,$type,$column->column()).($type != 'serial' ? ' NULL ' : '');
				$columndef[] = $column_str;
				
				if($dao->prop_anon($prop_name,'primary') === true || $type != 'serial'){
					$primary[] = $this->quotation($column->column());
				}
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		if(!empty($primary)){
			$sql .= ' ,PRIMARY KEY ( '.implode(',',$primary).' ) '.PHP_EOL;
		}
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
		$insert = $vars = [];
		$autoid = null;
		
		foreach($dao->columns(true) as $column){
			if(!$column->auto()){
				$insert[] = $this->quotation($column->column());
				$vars[] = $this->column_value($dao,$column->name(),$dao->{$column->name()}());
			}
		}
		return new \ebi\Daq('insert into '.$this->quotation($column->table()).' ('.implode(',',$insert).') values ('.implode(',',array_fill(0,sizeof($insert),'?')).');'
			,$vars
		);
	}
	protected function date_format($column_map,$dao,$column,$require){
		$fmt = [];
		$sql = ['Y'=>'%Y','m'=>'%m','d'=>'%d','H'=>'%H','i'=>'%i','s'=>'%s'];
	
		foreach(['Y'=>'2000','m'=>'01','d'=>'01','H'=>'00','i'=>'00','s'=>'00'] as $f => $d){
			$fmt[] = (strpos($require,$f) === false) ? $d : $sql[$f];
		}
		$f = $fmt[0].'-'.$fmt[1].'-'.$fmt[2].'T'.$fmt[3].':'.$fmt[4].':'.$fmt[5];
		return 'DATE_FORMAT('.$table_column.',\''.$f.'\')';
	}
}
<?php
namespace ebi;
/**
 * DB接続クラス(SQlite)
 */
class SqliteConnector extends \ebi\DbConnector{
	protected $order_random_str = 'random()';
	private $timezone_offset = 0;
	
	public function connect(?string $name, ?string $host, ?int $port, ?string $user, ?string $password, ?string $sock, bool $autocommit): \PDO{
		unset($port,$user,$password,$sock);
	
		if(!extension_loaded('pdo_sqlite')){
			throw new \ebi\exception\ConnectionException('pdo_sqlite not supported');
		}
		$con = null;
	
		if(empty($name)){
			$name = getcwd().'/data.sqlite3';
		}
		if($host != ':memory:'){
			if(strpos($name,'.') === false){
				$name = $name.'.sqlite3';
			}
			$host = str_replace('\\','/',$host ?? '');
			if(substr($host,-1) != '/'){
				$host = $host.'/';
			}
			$path = \ebi\Util::path_absolute($host,$name);
			\ebi\Util::mkdir(dirname($path));
		}
		try{
			$con = new \PDO(sprintf('sqlite:%s',($host == ':memory:') ? ':memory:' : $path));
			$con->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		}catch(\PDOException $e){
			throw new \ebi\exception\ConnectionException($e->getMessage());
		}
		if(!empty($this->timezone)){
			$this->timezone_offset = (new \DateTimeZone($this->timezone))->getOffset(
				new \DateTime('now',new \DateTimeZone('UTC'))
			);
		}
		return $con;
	}

	public function last_insert_id_sql(): \ebi\Daq{
		return new \ebi\Daq('select last_insert_rowid() as last_insert_id;');
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function column_value(\ebi\Dao $dao, string $name, $value){
		if($value === null){
			return null;
		}
		try{		
			switch($dao->prop_anon($name,'type')){
				case 'datetime':
				case 'timestamp':
					if(!ctype_digit((string)$value)){
						$value = strtotime($value);
					}
					// UTCとして扱う
					return date('Y-m-d H:i:s',$value - $this->timezone_offset);
				case 'date':
					if(!ctype_digit((string)$value)){
						$value = strtotime($value);
					}
					return date('Y-m-d',$value);
				case 'bool':
				case 'boolean':
					return (int)$value;
			}
		}catch(\Exception $e){
		}
		return $value;
	}

	protected function select_column_format(string $column_map, \ebi\Dao $dao, \ebi\Column $column, array $info): string{
		if(isset($info['date_format'][$column->name()])){
			return $this->date_format($column_map,$dao,$column,$info['date_format'][$column->name()]);
		}
		if($dao->prop_anon($column->name(),'type') === 'timestamp'){
			return 'datetime('.$column_map.',\''.$this->timezone_offset.' seconds\')';
		}
		return $column_map;
	}

	protected function date_format(string $column_map, \ebi\Dao $dao, \ebi\Column $column, string $require): string{
		$fmt = [];
		$sql = ['Y'=>'%Y','m'=>'%m','d'=>'%d','H'=>'%H','i'=>'%M','s'=>'%S'];
	
		foreach(['Y'=>'2000','m'=>'01','d'=>'01','H'=>'00','i'=>'00','s'=>'00'] as $f => $d){
			$fmt[] = (strpos($require,$f) === false) ? $d : $sql[$f];
		}
		$f = $fmt[0].'-'.$fmt[1].'-'.$fmt[2].'T'.$fmt[3].':'.$fmt[4].':'.$fmt[5];
	
		if($dao->prop_anon($column->name(),'type') === 'timestamp'){
			return 'strftime(\''.$f.'\',datetime('.$column_map.',\''.$this->timezone_offset.' seconds\'))';
		}
		return 'strftime(\''.$f.'\','.$column_map.')';
	}

	protected function for_update(bool $bool): string{
		return ''; // 使えないので無視する
	}
}


<?php
namespace ebi;

class SqliteConnector extends \ebi\DbConnector{
	protected string $order_random_str = 'random()';
	private int $timezone_offset = 0;
	
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
			// 並列テスト(testman -p)では worker 毎に DB ファイルを分離する。
			// TESTMAN_WORKER_ID がある時だけ拡張子直前へ _w<id> を挿入（例 data.main.sqlite3 → data.main_w3.sqlite3）。
			// 通常実行/本番は未設定なので無影響。core を汚さないよう env を直読みする（\ebi\Dt には依存しない）。
			$__wid = getenv('TESTMAN_WORKER_ID');
			if($__wid !== false && (int)$__wid > 0){
				$name = preg_replace('/(\.[^.\/]+)$/', '_w'.(int)$__wid.'$1', $name, 1);
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
			// 書き込み中心ワークロード(テスト等)の高速化。WAL+synchronous=NORMAL はクラッシュ耐性を
			// 保ちつつ commit 毎の fsync を大幅削減。busy_timeout でロック時に即エラーせず待つ。
			if($host != ':memory:'){
				$con->exec('PRAGMA journal_mode=WAL');
			}
			$con->exec('PRAGMA synchronous=NORMAL');
			$con->exec('PRAGMA temp_store=MEMORY');
			$con->exec('PRAGMA busy_timeout=5000');
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
		$type = $dao->prop_anon($column->name(),'type');
		if($type === 'timestamp' || $type === 'datetime'){
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
	
		$type = $dao->prop_anon($column->name(),'type');
		if($type === 'timestamp' || $type === 'datetime'){
			return 'strftime(\''.$f.'\',datetime('.$column_map.',\''.$this->timezone_offset.' seconds\'))';
		}
		return 'strftime(\''.$f.'\','.$column_map.')';
	}

	protected function for_update(bool $bool): string{
		return ''; // 使えないので無視する
	}
}


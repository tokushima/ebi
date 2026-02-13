<?php
namespace ebi;

abstract class Dao extends \ebi\Obj{
	private static array $_dao_ = [];
	private static int $_cnt_ = 0;
	private static array $_con_ = [];

	private ?string $_class_id_ = null;
	private array $_saving_ = [false,false];

	private static array $_co_anon_ = [];
	private static array $_connections_ = [];
	private static array $_connection_settings_ = [];
	private static bool $recording_query = false;
	private static array $record_query = [];

	/**
	 * 接続情報一覧
	 * @return \ebi\Db[]
	 */
	public static function connections(): array{
		$connections = [];
		foreach(self::$_connections_ as $n => $con){
			$connections[$n] = $con;
		}
		return $connections;
	}
	/**
	 * 接続情報
	 */
	public static function connection(string $class): \ebi\Db{
		if(!isset(self::$_co_anon_[$class][0]) || !isset(self::$_connections_[self::$_co_anon_[$class][0]])){
			throw new \ebi\exception\ConnectionException('unable to connect to '.$class);
		}
		return self::$_connections_[self::$_co_anon_[$class][0]];
	}
	/**
	 * すべての接続でロールバックする
	 */
	public static function rollback_all(): void{
		foreach(self::connections() as $con){
			try{
				$con->rollback();
			}catch(\ebi\exception\ConnectionException $e){
			}
		}
	}
	/**
	 * すべての接続でコミットする
	 */
	public static function commit_all(): void{
		foreach(self::connections() as $con){
			try{
				$con->commit();
			}catch(\ebi\exception\ConnectionException $e){
			}
		}
	}
	private static function get_db_settings(string $database, string $class){
		if(!isset(self::$_connections_[$database])){
			if(isset(self::$_connection_settings_[$database]['con'])){
				self::get_db_settings(self::$_connection_settings_[$database]['con'],$class);
				self::$_connections_[$database] = self::$_connections_[self::$_connection_settings_[$database]['con']];
				return self::$_connection_settings_[$database];
			}
			self::$_connections_[$database] = new \ebi\Db(self::$_connection_settings_[$database]);
		}
		if(!isset(self::$_connections_[$database])){
			throw new \ebi\exception\ConnectionException('connection fail '.$class);
		}
		return self::$_connection_settings_[$database];
	}
	public function __toString(){
		$props = $this->props();
		
		foreach(['name','label','id'] as $n){
			if(array_key_exists($n,$props)){
				return $props[$n];
			}
		}
		return get_class($this);
	}
	public function __construct(){
		parent::__construct();

		if(func_num_args() == 1){
			foreach(func_get_arg(0) as $n => $v){
				switch($n){
					case '_class_id_':
						$this->{$n} = $v;
						break;
					default:
				}
			}
		}
		$p = get_class($this);
		if(!isset($this->_class_id_)){
			$this->_class_id_ = $p;
		}
		if(isset(self::$_dao_[$this->_class_id_])){
			return;
		}
		$annotation = \ebi\AttributeReader::get_class($p,['readonly','table']);
		$anon = [
			null // con name
			,(isset($annotation['table']['name']) ? $annotation['table']['name'] : null)
			,($annotation['readonly'] !== null)
		];
		
		if(empty(self::$_connection_settings_)){
			/**
			 * @var array
			 * Daoクラスごとのデータベース接続情報
			 * キーにDaoクラス名、値に接続パラメータ(type,host,name,port,user,password,sock,encode,timezone)を指定
			 * キーはnamespace単位でも指定可能
			 * '*'をキーにするとデフォルトの接続先になる
			 * 'ebi\SessionDao'=>[
			 *   'type'=>'ebi\SqliteConnector',
			 *   'name'=>'/tmp/data.session.sqlite3',
			 *   'timezone'=>'+9:00',
			 * ]
			 */
			self::$_connection_settings_ = \ebi\Conf::gets('connection');
			
			if(empty(self::$_connection_settings_)){
				self::$_connection_settings_ = ['*'=>['host'=>getcwd(),]];
			}
		}
		// find connection settings
		$findns = explode('\\',$p);
		while(!array_key_exists(implode('\\',$findns),self::$_connection_settings_) && !empty($findns)){
			array_pop($findns);
		}
		if(empty($findns) && !isset(self::$_connection_settings_['*'])){
			throw new \ebi\exception\ConnectionException('could not find the connection settings `'.$p.'`');
		}
		$anon[0] = empty($findns) ? '*' : implode('\\',$findns);
		
		if(empty($anon[1])){
			$table_class = $p;
			$parent_class = get_parent_class($p);
			$ref = new \ReflectionClass($parent_class);
			
			while(true){
				$ref = new \ReflectionClass($parent_class);
				if(__CLASS__ == $parent_class || $ref->isAbstract()){
					break;
				}
				$table_class = $parent_class;
				$parent_class = get_parent_class($parent_class);
			}
			$anon[1] = \ebi\Util::camel2snake($table_class);
		}
		self::get_db_settings($anon[0],$p);		
		self::$_con_[get_called_class()] = self::$_connections_[$anon[0]]->connector();
		
		self::$_co_anon_[$p] = $anon;
		self::$_co_anon_[$p][1] = self::$_co_anon_[$p][1];
		
		$root_table_alias = 't'.self::$_cnt_++;
		$_self_columns_ = $_where_columns_ = $_conds_ = $_alias_ = [];
		
		$props = $last_cond_column = [];
		$ref = new \ReflectionClass($this);
		foreach($ref->getProperties(\ReflectionProperty::IS_PUBLIC|\ReflectionProperty::IS_PROTECTED) as $prop){
			if($prop->getName()[0] != '_' && $this->prop_anon($prop->getName(),'extra') !== true){
				$props[] = $prop->getName();
			}
		}
		while(!empty($props)){
			$name = array_shift($props);
			$anon_cond = $this->prop_anon($name,'cond');
			$column_type = $this->prop_anon($name,'type');
			if(empty($column_type)){
				if($name == 'id'){
					$this->prop_anon($name,'type','serial',true);
				}else if($name == 'created_at' || $name == 'create_date' || $name == 'created'){
					$this->prop_anon($name,'type','datetime',true);
					$this->prop_anon($name,'auto_now_add',true,true);
				}else if($name == 'updated_at' || $name == 'update_date' || $name == 'modified'){
					$this->prop_anon($name,'type','datetime',true);
					$this->prop_anon($name,'auto_now',true,true);
				}else if($name == 'code'){
					$this->prop_anon($name,'type','string',true);
					$this->prop_anon($name,'auto_code_add',true,true);
				}
				$column_type = $this->prop_anon($name,'type','string');
			}
			if($this->prop_anon($name,'type') == 'serial'){
				$this->prop_anon($name,'primary',true,true);
			}
			$column = new \ebi\Column();
			$column->name($name);
			$column->column($this->prop_anon($name,'column',$name));
			$column->column_alias('c'.self::$_cnt_++);

			if($anon_cond === null){
				if(ctype_upper($column_type[0]) && class_exists($column_type) && is_subclass_of($column_type,__CLASS__)){
					throw new \ebi\exception\InvalidQueryException('undef '.$name.' annotation `cond`');
				}
				$column->table($this->dao_table());
				$column->table_alias($root_table_alias);
				$column->primary($this->prop_anon($name,'primary',false) || $column_type === 'serial');
				$column->auto($column_type === 'serial');
				$_alias_[$column->column_alias()] = $name;
				
				$_self_columns_[$name] = $column;
			}else if(false !== strpos($anon_cond,'(')){
				$matches = [];
				
				if(preg_match("/^(.+)\((.*)\)(.*)$/",$anon_cond,$matches)){
					[, $self_var, $conds_string, $has_var] = $matches;
					$conds = [];
					$ref_table = $ref_table_alias = null;
					
					if(!empty($conds_string)){
						foreach(explode(',',$conds_string) as $cond){
							$tcc = explode('.',$cond,3);
							switch(sizeof($tcc)){
								case 1:
									$conds[] = \ebi\Column::cond_instance($tcc[0],'c'.self::$_cnt_++,$this->dao_table(),$root_table_alias);
									break;
								case 2:
									[$t, $c1] = $tcc;
									$ref_table = $t;
									$ref_table_alias = 't'.self::$_cnt_++;
									$conds[] = \ebi\Column::cond_instance($c1,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
									break;
								case 3:
									[$t, $c1, $c2] = $tcc;
									$ref_table = $t;
									$ref_table_alias = 't'.self::$_cnt_++;
									$conds[] = \ebi\Column::cond_instance($c1,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
									$conds[] = \ebi\Column::cond_instance($c2,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
									break;
								default:
									throw new \ebi\exception\InvalidAnnotationException('annotation error : `'.$name.'`');
							}
						}
					}
					if($self_var[0] == '@'){
						$cond_var = null;
						$cond_name = substr($self_var,1);
						if(strpos($cond_name,'.') !== false){
							[$cond_name, $cond_var] = explode('.',$cond_name);
						}
						if(!isset($last_cond_column[$cond_name])){
							throw new \ebi\exception\InvalidAnnotationException('annotation error : `'.$cond_name.'`');
						}
						if(in_array($cond_name,$props)){
							$props[] = $name;
							continue;
						}
						$cond_column = clone($last_cond_column[$cond_name]);
						if(isset($cond_var)){
							$cond_column->column($cond_var);
							$cond_column->column_alias('c'.self::$_cnt_++);
						}
						array_unshift($conds,$cond_column);
					}else{
						array_unshift($conds,
							\ebi\Column::cond_instance($self_var,'c'.self::$_cnt_++,$this->dao_table(),$root_table_alias)
						);
					}
					$column->table($ref_table);
					$column->table_alias($ref_table_alias);
					$_alias_[$column->column_alias()] = $name;
					
					if(sizeof($conds) % 2 != 0){
						throw new \ebi\exception\InvalidQueryException($name.'['.$column_type.'] is illegal condition');
					}
					for($i=0;$i<sizeof($conds);$i+=2){
						$_conds_[] = [$conds[$i],$conds[$i+1]];
					}
					$_where_columns_[$name] = $column;

					if(!empty($conds)){
						$cond_column = clone($conds[sizeof($conds)-1]);
						$cond_column->column($column->column());
						$cond_column->column_alias('c'.self::$_cnt_++);
					
						$last_cond_column[$name] = $cond_column;
					}
				}
			}else if($anon_cond[0] === '@'){
				$cond_name = substr($anon_cond,1);
				if(in_array($cond_name,$props)){
					$props[] = $name;
					continue;
				}
				if(isset($_self_columns_[$cond_name])){
					$column->table($_self_columns_[$cond_name]->table());
					$column->table_alias($_self_columns_[$cond_name]->table_alias());
				}else if(isset($_where_columns_[$cond_name])){
					$column->table($_where_columns_[$cond_name]->table());
					$column->table_alias($_where_columns_[$cond_name]->table_alias());
				}else{
					throw new \ebi\exception\InvalidQueryException('undef var `'.$name.'`');
				}
				$_alias_[$column->column_alias()] = $name;
				$_where_columns_[$name] = $column;
			}
		}
		self::$_dao_[$this->_class_id_] = (object)[
			'_self_columns_'=>$_self_columns_,
			'_where_columns_'=>$_where_columns_,
			'_conds_'=>$_conds_,
			'_alias_'=>$_alias_,
		];
	}

	/**
	 * Columnの一覧を取得する
	 */
	public function dao_columns(bool $self_only=false): array{
		if($self_only){
			return self::$_dao_[$this->_class_id_]->_self_columns_;
		}
		return array_merge(self::$_dao_[$this->_class_id_]->_where_columns_,self::$_dao_[$this->_class_id_]->_self_columns_);
	}
	/**
	 * primaryのColumnの一覧を取得する
	 */
	public function dao_primary_columns(): array{
		$result = [];
		foreach(self::$_dao_[$this->_class_id_]->_self_columns_ as $column){
			if($column->primary()){
				$result[$column->name()] = $column;
			}
		}
		return $result;
	}
	/**
	 * 必須の条件を取得する
	 */
	public function dao_conds(): array{
		return self::$_dao_[$this->_class_id_]->_conds_;
	}
	/**
	 * 結果配列から値を自身にセットする
	 */
	protected function dao_cast_resultset(array $resultset): void{
		foreach($resultset as $alias => $value){
			if(isset(self::$_dao_[$this->_class_id_]->_alias_[$alias])){
				if(self::$_dao_[$this->_class_id_]->_alias_[$alias] == 'ref1'){
					$this->prop_anon(self::$_dao_[$this->_class_id_]->_alias_[$alias],'has',true);
				}
				if($this->prop_anon(self::$_dao_[$this->_class_id_]->_alias_[$alias],'has') === true){
					$this->{self::$_dao_[$this->_class_id_]->_alias_[$alias]}()->dao_cast_resultset([$alias=>$value]);
				}else{
					$this->{self::$_dao_[$this->_class_id_]->_alias_[$alias]}($value);
				}
			}
		}
	}
	/**
	 * テーブル名を取得
	 */
	public function dao_table(): string{
		return self::$_co_anon_[get_class($this)][1];
	}

	protected function __find_conds__(): \ebi\Q{
		return Q::b();
	}
	protected function __before_save__(bool $is_update): void{
	}
	protected function __after_save__(bool $is_update): void{
	}
	
	/**
	 * 発行したSQLの記録を開始する
	 */
	public static function start_record(): void{
		self::$recording_query = true;
		self::$record_query = [];
	}
	/**
	 * 発行したSQLの記録を終了する
	 */
	public static function stop_record(): array{
		self::$recording_query = false;
		return self::$record_query;
	}
	/**
	 * クエリを実行する
	 */
	public function query(\ebi\Daq $daq): \PDOStatement{
		if(self::$recording_query){
			self::$record_query[] = [$daq->sql(),$daq->ar_vars()];
		}
		try{
			$statement = self::connection(get_class($this))->prepare($daq->sql());
		}catch(\PDOException $e){
			throw new \ebi\exception\InvalidQueryException($e->getMessage());
		}
		
		try{
			$statement->execute($daq->ar_vars());
		}catch(\PDOException $e){
			if($statement->errorCode() == 22001){
				throw new \ebi\exception\LengthException('Data too long: '.$statement->errorCode());
			}
			self::$_con_[get_called_class()]->error_info($statement->errorInfo());
			
			throw new \ebi\exception\InvalidQueryException($e->getMessage());
		}
		return $statement;
	}

	private function update_query(\ebi\Daq $daq): int{
		$statement = $this->query($daq);
		
		return $statement->rowCount();
	}

	/**
	 * @return mixed
	 */
	private function func_query(\ebi\Daq $daq,bool $is_list=false){
		try{
			$statement = $this->query($daq);
		}catch(\PDOException $e){
			throw new \ebi\exception\InvalidQueryException($e->getMessage());
		}
		if($statement->columnCount() == 0){
			return ($is_list) ? [] : null;
		}
		return ($is_list) ? $statement->fetchAll(\PDO::FETCH_ASSOC) : $statement->fetchAll(\PDO::FETCH_COLUMN,0);
	}
	/**
	 * 値の妥当性チェックを行う
	 */
	public function validate(): void{
		foreach($this->dao_columns(true) as $name => $column){
			if(!\ebi\Exceptions::has($name)){
				$value = $this->{$name}();
				
				\ebi\Validator::value($name, $value, [
					'type'=>$this->prop_anon($name,'type'),
					'min'=>$this->prop_anon($name,'min'),
					'max'=>$this->prop_anon($name,'max'),
					'require'=>$this->prop_anon($name,'require'),
				]);
				
				$unique_together = $this->prop_anon($name,'unique_together');
				if($value !== '' && $value !== null && ($this->prop_anon($name,'unique') === true || !empty($unique_together))){
					$uvalue = $value;
					$q = [\ebi\Q::eq($name,$uvalue)];
					if(!empty($unique_together)){
						foreach((is_array($unique_together) ? $unique_together : [$unique_together]) as $c){
							$q[] = Q::eq($c,$this->{$c}());
						}
					}
					foreach($this->dao_primary_columns() as $primary){
						if(null !== $this->{$primary->name()}) $q[] = Q::neq($primary->name(),$this->{$primary->name()});
					}
					if(0 < call_user_func_array([get_class($this),'find_count'],$q)){
						\ebi\Exceptions::add(new \ebi\exception\UniqueException($name.' unique'),$name);
					}
				}
				try{
					if(method_exists($this,'__verify_'.$column->name().'__') && call_user_func([$this,'__verify_'.$column->name().'__']) === false){
						\ebi\Exceptions::add(
							new \ebi\exception\VerifyException(
								$column->name().' verification failed'
							),
							$column->name()
						);
					}
				}catch(\ebi\Exceptions $e){
				}catch(\Exception $e){
					\ebi\Exceptions::add($e,$column->name());
				}
			}
		}
		\ebi\Exceptions::throw_over();
	}

	/**
	 * @return mixed
	 */
	private static function exec_aggregator_result_cast(self $dao, $target_name, ?string $value, ?string $cast){
		switch($cast){
 			case 'float': return (float)$value;
 			case 'int': return (int)$value;
			default:
				$dao->{$target_name}($value);
				return $dao->{$target_name}();	
		}
	}
	public static function find_distinct($target_prop, ...$args): array{
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$results = $dao->call_aggregator('distinct', $target_prop, null, $args);
		return $results;
	}
	private function call_aggregator(string $exe, ?string $target_prop, ?string $group_prop, array $args, bool $is_list=false){
		$query = new \ebi\Q();
		if(!empty($args)){
			call_user_func_array([$query,'add'], $args);
		}
		$daq = self::$_con_[get_called_class()]->{$exe.'_sql'}($this, $target_prop, $group_prop, $query);
		return $this->func_query($daq,$is_list);
	}

	private static function exec_aggregator(string $exec, ?string $target_prop, array $args, ?string $cast=null){
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$result = $dao->call_aggregator($exec, $target_prop, null, $args);
		return static::exec_aggregator_result_cast($dao, $target_prop, current($result), $cast);
	}
	public static function find_count(...$args): int{
		$target_prop = (isset($args[0]) && is_string($args[0])) ? array_shift($args) : null;
		return (int)static::exec_aggregator('count', $target_prop, $args, 'int');
	}
	public static function find_avg(string $target_prop, ...$args): float{
		return static::exec_aggregator('avg', $target_prop, $args, 'float');
	}
	public static function find_sum(string $target_prop, ...$args): float{
		return static::exec_aggregator('sum', $target_prop, $args, 'float');
	}
	/**
	 * @return mixed
	 */
	public static function find_max(string $target_prop, ...$args){
		return static::exec_aggregator('max', $target_prop, $args);
	}
	/**
	 * @return mixed
	 */
	public static function find_min(string $target_prop, ...$args){
		return static::exec_aggregator('min', $target_prop, $args);
	}

	private static function exec_aggregator_by(string $exec, string $target_prop, string $group_prop, array $args, $cast=null): array{
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$results = [];
		
		foreach($dao->call_aggregator($exec, $target_prop, $group_prop, $args, true) as $value){
			$dao->{$group_prop}($value['key_column']);
			$results[$dao->{$group_prop}()] = static::exec_aggregator_result_cast($dao, $target_prop, $value['target_column'], $cast);
		}
		ksort($results);
		return $results;
	}
	public static function find_count_by(string $target_prop, string $group_props, ...$args): array{
		return static::exec_aggregator_by('count', $target_prop, $group_props, $args, 'int');
	}
	public static function find_sum_by(string $target_prop, string $group_props, ...$args): array{
		return static::exec_aggregator_by('sum', $target_prop, $group_props, $args);
	}
	public static function find_max_by(string $target_prop, string $group_props, ...$args): array{
		return static::exec_aggregator_by('max', $target_prop, $group_props, $args);
	}
	public static function find_min_by(string $target_prop, string $group_props, ...$args): array{
		return static::exec_aggregator_by('min', $target_prop, $group_props, $args);
	}
	public static function find_avg_by(string $target_prop, string $group_props, ...$args): array{
		return static::exec_aggregator_by('avg', $target_prop, $group_props, $args,'float');
	}


	/**
	 * １件取得する
	 * @return static
	 */
	public static function find_get(){
		$args = func_get_args();
		$dao = new static();
		$query = new \ebi\Q();
		$query->add($dao->__find_conds__());
		$query->add(new \ebi\Paginator(1,1));
		
		if(!empty($args)){
			call_user_func_array([$query,'add'],$args);
		}
		foreach(self::get_statement_iterator($dao,$query) as $d){
			return $d;
		}
		throw new \ebi\exception\NotFoundException('not found');
	}

	/**
	 * サブクエリを取得する
	 */
	public static function find_sub($target_prop, ...$args): \ebi\Daq{
		$dao = new static();
		$query = new \ebi\Q();
		$query->add($dao->__find_conds__());

		if(!empty($args)){
			call_user_func_array([$query,'add'],$args);
		}
		if(!$query->is_order_by()){
			$query->order($target_prop);
		}
		if($query->paginator() instanceof \ebi\Paginator){
			if($query->is_order_by()){
				$query->paginator()->order(
					$query->in_order_by(0)->ar_arg1(),
					$query->in_order_by(0)->type() == Q::ORDER_ASC
				);
			}else if($query->paginator()->has_order()){
				$query->add(Q::order($query->paginator()->order()));
			}
			$query->paginator()->total(call_user_func_array([get_called_class(),'find_count'], $args));
		}
		return self::$_con_[get_called_class()]->select_sql($dao, $query, $query->paginator(), $target_prop);
	}

	private static function get_statement_iterator(self $dao, \ebi\Q $query): \Generator{
		if(!$query->is_order_by()){
			foreach($dao->dao_primary_columns() as $column){
				$query->order($column->name());
			}
		}
		$daq = self::$_con_[get_called_class()]->select_sql($dao,$query,$query->paginator());
		try{
			$statement = $dao->query($daq);
			
			while(true){
				$resultset = $statement->fetch(\PDO::FETCH_ASSOC);
				if($resultset === false){
					break;
				}
				$obj = clone($dao);
				$obj->dao_cast_resultset($resultset);
				
				yield $obj;
			}
		}catch(\PDOException $e){
			throw new \ebi\exception\InvalidQueryException($e);
		}
	}

	/**
	 * 検索を実行する
	 * @return iterable<static>
	 */
	public static function find(): \Generator{
		$args = func_get_args();
		$dao = new static();
		$query = new \ebi\Q();
		$query->add($dao->__find_conds__());
		
		if(!empty($args)){
			call_user_func_array([$query,'add'],$args);
		}
		if($query->paginator() instanceof \ebi\Paginator){
			if($query->is_order_by()){
				$query->paginator()->order(
					$query->in_order_by(0)->ar_arg1(),
					$query->in_order_by(0)->type() == Q::ORDER_ASC
				);
			}else if($query->paginator()->has_order()){
				$query->add(Q::order($query->paginator()->order()));
			}
			$query->paginator()->total(call_user_func_array([get_called_class(),'find_count'],$args));
		}
		return static::get_statement_iterator($dao,$query);
	}

	/**
	 * 検索結果をすべて取得する
	 */
	public static function find_all(): array{
		$args = func_get_args();
		$result = [];
		foreach(call_user_func_array([get_called_class(),'find'],$args) as $p){
			$result[] = $p;
		}
		return $result;
	}

	/**
	 * コミットする
	 */
	public static function commit(): void{
		self::connection(get_called_class())->commit();
	}

	/**
	 * ロールバックする
	 */
	public static function rollback(): void{
		self::connection(get_called_class())->rollback();
	}
	
	/**
	 * 複数レコードを一括登録する
	 * before/after/verifyは実行されない
	 */
	public static function insert_multiple(array $data_objects): int{
		foreach($data_objects as $obj){
			if(!($obj instanceof static)){
				throw new \ebi\exception\InvalidArgumentException('must be an '.get_class($obj));
			}
		}
		$dao = new static();
		$daq = self::$_con_[get_called_class()]->insert_multiple_sql($dao,$data_objects);
		return $dao->update_query($daq);
	}
	
	/**
	 * 条件により削除する
	 * before/after/verifyは実行されない
	 * @return 実行した件数
	 */
	public static function find_delete(): int{
		$args = func_get_args();
		$dao = new static();
		if(self::$_co_anon_[get_class($dao)][2]){
			throw new \ebi\exception\BadMethodCallException('delete is not permitted');
		}
		$query = new \ebi\Q();
		if(!empty($args)){
			call_user_func_array([$query,'add'],$args);
		}
		$daq = self::$_con_[get_called_class()]->find_delete_sql($dao,$query);
		return $dao->update_query($daq);
	}

	/**
	 * DBから削除する
	 */
	public function delete(): void{
		if(self::$_co_anon_[get_class($this)][2]){
			throw new \ebi\exception\BadMethodCallException('delete is not permitted');
		}
		$daq = self::$_con_[get_called_class()]->delete_sql($this);
		if($this->update_query($daq) == 0){
			throw new \ebi\exception\NotFoundException('delete failed');
		}
	}

	/**
	 * 指定のプロパティにユニークコードをセットする
	 * auto_code_addアノテーションで呼ばれる
	 */
	public function set_unique_code(string $prop_name, ?int $size=null): void{
		/**
		 * ユニークコードで利用する文字
		 * 
		 * @param string $base ex. ABCDEFGHJKLMNPQRSTUWXY0123456789
		 */
		$base = $this->prop_anon($prop_name,'base');
		
		if(empty($base)){
			/**
			 * ユニークコードで利用する文字パターン
			 * unique_code_baseが未定義の場合のみ有効
			 * 
			 * 0: 数字 0123456789
			 * a: 小文字 abcdefghjkmnprstuvwxy
			 * A: 大文字 ABCDEFGHJKLMNPQRSTUVWXY
			 * t: トークン token68
			 * 
			 * @param string $unique_code_ctype 0aAの組み合わせ
			 */
			$ctype = $this->prop_anon($prop_name,'ctype','0a');
			
			if(strpos($ctype,'A') !== false){
				$base .= 'ABCDEFGHJKLMNPQRSTUVWXY';
			}
			if(strpos($ctype,'a') !== false){
				$base .= 'abcdefghjkmnprstuvwxy';
			}
			if(strpos($ctype,'0') !== false){
				$base .= '0123456789';
			}
			if(strpos($ctype,'t') !== false){
				$base .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
						'abcdefghijklmnopqrstuvwxyz'.
						'0123456789-._~+/';
			}
			if(empty($base)){
				throw new \ebi\exception\IllegalDataTypeException('unexpected ctype');
			}
		}
		
		$code = '';
		$challenge = 0;
		$challenge_max = 10;
		$verify_func = method_exists($this,'__verify_'.$prop_name.'__');
		$prefix = '';
		$length = (!empty($size)) ? $size : $this->prop_anon($prop_name,'length');
		
		if(empty($length)){
			$length = $this->prop_anon($prop_name,'max',32);
		}
		if(method_exists($this,'__prefix_'.$prop_name.'__')){
			$prefix = call_user_func_array([$this,'__prefix_'.$prop_name.'__'],[$base]);
			$length = $length - strlen($prefix);
		}		
		while($code == ''){
			for($i=0;$i<=$challenge_max;$i++){
				$code = $prefix.\ebi\Code::rand($base,$length);
				call_user_func_array([$this,$prop_name],[$code]);
				
				if((!$verify_func || call_user_func([$this,'__verify_'.$prop_name.'__']) !== false) && 
					static::find_count(Q::eq($prop_name,$code)) === 0
				){
					break 2;
				}
			}
			if($challenge++ > $challenge_max){
				throw new \ebi\exception\GenerateUniqueCodeRetryLimitOverException($prop_name.': generate unique code retry limit over');
			}
			usleep(1000);
			$code = '';
		}
	}

	/**
	 * DBへ保存する
	 * @return static
	 */
	public function save(){
		if($this->_saving_[0]){
			throw new \ebi\exception\BadMethodCallException('save can not be used during __before_save__');
		}
		$primary_q = new \ebi\Q();
		$new = false;
		
		foreach($this->dao_primary_columns() as $column){
			$value = $this->{$column->name()}();
			
			if($this->prop_anon($column->name(),'type') === 'serial' && empty($value)){
				$new = true;
				break;
			}
			$primary_q->add(Q::eq($column->name(),$value));
		}
		if(!$new && !$primary_q->none() && static::find_count($primary_q) === 0){
			$new = true;
		}
		
		$auto_update_prop = [];
		foreach($this->dao_columns(true) as $column){
			if($this->prop_anon($column->name(),'auto_now') === true){
				$auto_update_prop[] = $column->name();
				
				switch($this->prop_anon($column->name(),'type')){
					case 'datetime':
					case 'timestamp':
					case 'date':
						$this->{$column->name()}(time());
						break;
					case 'intdate':
						$this->{$column->name()}(date('Ymd'));
						break;
				}
			}else if($new && ($this->{$column->name()}() === null || $this->{$column->name()}() === '')){
				if($this->prop_anon($column->name(),'type') == 'string' && $this->prop_anon($column->name(),'auto_code_add') === true){
					$this->set_unique_code($column->name());
				}else if($this->prop_anon($column->name(),'auto_now_add') === true){
					switch($this->prop_anon($column->name(),'type')){
						case 'datetime':
						case 'timestamp':
						case 'date':
							$this->{$column->name()}(time()); 
							break;
						case 'intdate':
							$this->{$column->name()}(date('Ymd'));
							break;
					}
				}
			}
		}
		
		if($new){
			if(self::$_co_anon_[get_called_class()][2]){
				throw new \ebi\exception\BadMethodCallException('create save is not permitted');
			}
			if(!$this->_saving_[1]){ // after中は実行しない
				$this->_saving_[0] = true;
				$this->__before_save__(false);
				$this->_saving_[0] = false;
			}
			
			$this->validate();
			$daq = self::$_con_[get_called_class()]->create_sql($this);
			
			if($this->update_query($daq) == 0){
				throw new \ebi\exception\NoRowsAffectedException('create failed');
			}
			if($daq->is_id()){
				$result = $this->func_query(self::$_con_[get_called_class()]->last_insert_id_sql($this));
				if(empty($result)){
					throw new \ebi\exception\NoRowsAffectedException('create failed');
				}
				$this->{$daq->id()}($result[0]);
			}
			if(!$this->_saving_[1]){
				$this->_saving_[1] = true;
				$this->__after_save__(false);
				$this->_saving_[1] = false;
			}
		}else{
			if(self::$_co_anon_[get_called_class()][2]){
				throw new \ebi\exception\BadMethodCallException('update save is not permitted');
			}
			if(!$this->_saving_[1]){ // after中は実行しない
				$this->_saving_[0] = true;
				$this->__before_save__(true);
				$this->_saving_[0] = false;
			}
			
			$this->validate();
			$args = func_get_args();
			$target = [];
			$query = new \ebi\Q();
			if(!empty($args)){
				foreach($args as $arg){
					if(is_string($arg)){
						$target[] = $arg;
					}else if($arg instanceof \ebi\Q){
						$query->add($arg);
					}
				}
				if(!empty($target)){
					$target = array_merge($target,$auto_update_prop);
				}
			}
			$daq = self::$_con_[get_called_class()]->update_sql($this,$query,$target);
			$affected_rows = $this->update_query($daq);
			
			if($affected_rows === 0 && !empty($args)){
				throw new \ebi\exception\NoRowsAffectedException('update failed');
			}
			if(!$this->_saving_[1]){
				$this->_saving_[1] = true;
				$this->__after_save__(true);
				$this->_saving_[1] = false;
			}
		}
		return $this;
	}

	/**
	 * テーブルの作成
	 */
	public static function create_table(): bool{
		$dao = new static();
		$anon = \ebi\AttributeReader::get_class(get_class($dao),['table']);
		
		if(!self::$_co_anon_[get_class($dao)][2] && 
			(!isset($anon['table']['create']) || $anon['table']['create'] !== false)
		){
			$daq = new \ebi\Daq(self::$_con_[get_called_class()]->exists_table_sql($dao));
 			$count = current($dao->func_query($daq));
			
			if($count == 0){
				$daq = new \ebi\Daq(self::$_con_[get_called_class()]->create_table_sql($dao));
				$dao->func_query($daq);
				return true;
			}
		}
		return false;
	}
	/**
	 * テーブルの削除
	 */
	public static function drop_table(): bool{
		$dao = new static();
		$anon = \ebi\AttributeReader::get_class(get_class($dao),['table']);
		
		if(!self::$_co_anon_[get_class($dao)][2] &&
			(!isset($anon['table']['create']) || $anon['table']['create'] !== false)
		){
			$daq = new \ebi\Daq(self::$_con_[get_called_class()]->exists_table_sql($dao));
			$count = current($dao->func_query($daq));
			
			if($count == 1){
				$daq = new \ebi\Daq(self::$_con_[get_called_class()]->drop_table_sql($dao));
				$dao->func_query($daq);
				return true;
			}
		}
		return false;
	}
}
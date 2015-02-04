<?php
namespace ebi;
/**
 * DB接続クラス(SQLite)
 * @author tokushima
 *
 */
class DbConnector{
	protected $encode;
	protected $timezone;
	protected $quotation = '`';
	protected $order_random_str = 'random()';
	
	public function __construct($encode=null,$timezone=null){
		$this->encode = $encode;
		$this->timezone = $timezone;
	}	
	
	public static function type(){
		return get_called_class();
	}
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
		if(!extension_loaded('pdo_sqlite')){
			throw new \ebi\exception\ConnectionException('pdo_sqlite not supported');
		}
		if(empty($host) && empty($name)){
			throw new \ebi\exception\ConnectionException('undef connection name');
		}
		unset($port,$user,$password,$sock);
		$con = null;
		
		if(empty($host)){
			$host = \ebi\Conf::get('host');
			if(empty($host)){
				$host = empty($name) ? ':memory:' : getcwd();
			}
		}
		if($host != ':memory:'){
			if(empty($name)){
				$name = 'default';
			}
			if(strpos($name,'.') === false){
				$name = $name.'.sqlite3';
			}
			$host = str_replace('\\','/',$host);
			if(substr($host,-1) != '/') $host = $host.'/';
			$path = \ebi\Util::path_absolute($host,$name);
			\ebi\Util::mkdir(dirname($path));
		}
		try{
			$con = new \PDO(sprintf('sqlite:%s',($host == ':memory:') ? ':memory:' : $path));
		}catch(\PDOException $e){
			throw new \ebi\exception\ConnectionException($e->getMessage());
		}
		return $con;
	}
	public function last_insert_id_sql(){
		return new \ebi\Daq('select last_insert_rowid() as last_insert_id;');
	}
	/**
	 * insert文を生成する
	 * @param Dao $dao
	 * @return Daq
	 */
	public function create_sql(\ebi\Dao $dao){
		$insert = $vars = [];
		$autoid = null;
		foreach($dao->columns(true) as $column){
			if($column->auto()) $autoid = $column->name();
			$insert[] = $this->quotation($column->column());
			$vars[] = $this->update_value($dao,$column->name());
		}
		return new \ebi\Daq('insert into '.$this->quotation($column->table()).' ('.implode(',',$insert).') values ('.implode(',',array_fill(0,sizeof($insert),'?')).');'
				,$vars
				,$autoid
		);
	}
	/**
	 * update文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @return Daq
	 */
	public function update_sql(\ebi\Dao $dao,\ebi\Q $query){
		$where = $update = $wherevars = $updatevars = $from = [];
		foreach($dao->primary_columns() as $column){
			$where[] = $this->quotation($column->column()).' = ?';
			$wherevars[] = $this->update_value($dao,$column->name());
		}
		if(empty($where)){
			throw new \ebi\exception\LogicException('primary not found');
		}
		foreach($dao->columns(true) as $column){
			if(!$column->primary()){
				$update[] = $this->quotation($column->column()).' = ?';
				$updatevars[] = $this->update_value($dao,$column->name());
			}
		}
		if(empty($update)){
			throw new \ebi\exception\BadMethodCallException('no update column');
		}
		$vars = array_merge($updatevars,$wherevars);
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(true),null,false);
		return new \ebi\Daq(
				'update '.$this->quotation($column->table()).' set '.implode(',',$update).' where '.implode(' and ',$where).(empty($where_sql) ? '' : ' and '.$where_sql)
				,array_merge($vars,$where_vars)
		);
	}
	/**
	 * delete文を生成する
	 * @param Dao $dao
	 * @return Daq
	 */
	public function delete_sql(\ebi\Dao $dao){
		$where = $vars = [];
		foreach($dao->primary_columns() as $column){
			$where[] = $this->quotation($column->column()).' = ?';
			$vars[] = $dao->{$column->name()}();
		}
		if(empty($where)) throw new \ebi\exception\BadMethodCallException('not primary');
		return new \ebi\Daq(
				'delete from '.$this->quotation($column->table()).' where '.implode(' and ',$where)
				,$vars
		);
	}
	/**
	 * delete文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @return Daq
	 */
	public function find_delete_sql(\ebi\Dao $dao,\ebi\Q $query){
		$from = [];
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(true),null,false);
		return new \ebi\Daq(
				'delete from '.$this->quotation($dao->table()).(empty($where_sql) ? '' : ' where '.$where_sql)
				,$where_vars
		);
	}
	/**
	 * select文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @param Paginator $paginator
	 * @param string $name columnを指定する場合に対象の変数名
	 * @return Daq
	 */
	public function select_sql(\ebi\Dao $dao,\ebi\Q $query,$paginator,$name=null){
		$select = $from = [];

		if(empty($name)){
			foreach($dao->columns() as $column){
				$select[] = $column->table_alias().'.'.$this->quotation($column->column()).' '.$column->column_alias();
				$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
			}
		}else{
			foreach($dao->columns() as $column){
				if($column->name() == $name){
					$select[] = $column->table_alias().'.'.$this->quotation($column->column()).' '.$column->column_alias();
					$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
					break;
				}
			}
		}
		if(empty($select)){
			throw new \ebi\exception\BadMethodCallException('select invalid');
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(),$this->where_cond_columns($dao->conds(),$from));
		return new \ebi\Daq(('select '.implode(',',$select).' from '.implode(',',$from)
				.(empty($where_sql) ? '' : ' where '.$where_sql)
				.$this->select_option_sql($paginator,$this->select_order($query,$dao->columns()))
		)
				,$where_vars
		);
	}
	protected function select_order($query,array $self_columns){
		$order = [];
		if($query->is_order_by_rand()){
			$order[] = $this->order_random_str;
		}else{
			foreach($query->ar_order_by() as $q){
				foreach($q->ar_arg1() as $column_str){
					$order[] = $this->get_column($column_str,$self_columns)->column_alias().(($q->type() == Q::ORDER_ASC) ? ' asc' : ' desc');
				}
			}
		}
		return $order;
	}
	protected function select_option_sql($paginator,$order){
		return ' '
		.(empty($order) ? '' : ' order by '.implode(',',$order))
		.(($paginator instanceof \ebi\Paginator) ? sprintf(" limit %d,%d ",$paginator->offset(),$paginator->limit()) : '')
		;
	}
	/**
	 * count文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function count_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('count',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * sum文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function sum_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('sum',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * max文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function max_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('max',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * min文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function min_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('min',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * avg文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function avg_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('avg',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * distinct文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function distinct_sql(\ebi\Dao $dao,$target_column,$gorup_column,\ebi\Q $query){
		return $this->which_aggregator_sql('distinct',$dao,$target_column,$gorup_column,$query);
	}
	protected function which_aggregator_sql($exe,\ebi\Dao $dao,$target_name,$gorup_name,\ebi\Q $query){
		$select = $from = [];
		$target_column = $group_column = null;
		if(empty($target_name)){
			$self_columns = $dao->columns(true);
			$primary_columns = $dao->primary_columns();
			if(!empty($primary_columns)) $target_column = current($primary_columns);
			if(empty($target_column) && !empty($self_columns)) $target_column = current($self_columns);
		}else{
			$target_column = $this->get_column($target_name,$dao->columns());
		}
		if(empty($target_column)){
			throw new \ebi\exception\BadMethodCallException('undef primary');
		}
		if(!empty($gorup_name)){
			$group_column = $this->get_column($gorup_name,$dao->columns());
			$select[] = $group_column->table_alias().'.'.$this->quotation($group_column->column()).' key_column';
		}
		foreach($dao->columns() as $column){
			$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(),$this->where_cond_columns($dao->conds(),$from));
		return new \ebi\Daq(('select '.$exe.'('.$target_column->table_alias().'.'.$this->quotation($target_column->column()).') target_column'
					.(empty($select) ? '' : ','.implode(',',$select))
					.' from '.implode(',',$from)
					.(empty($where_sql) ? '' : ' where '.$where_sql)
					.(empty($group_column) ? '' : ' group by key_column')
					.' order by target_column'
					)
					,$where_vars
				);
	}
	protected function where_cond_columns(array $cond_columns,array &$from){
		$conds = [];
		foreach($cond_columns as $columns){
			$conds[] = $columns[0]->table_alias().'.'.$this->quotation($columns[0]->column())
			.' = '
			.$columns[1]->table_alias().'.'.$this->quotation($columns[1]->column());
			$from[$columns[0]->table_alias()] = $columns[0]->table().' '.$columns[0]->table_alias();
			$from[$columns[1]->table_alias()] = $columns[1]->table().' '.$columns[1]->table_alias();
		}
		return (empty($conds)) ? '' : implode(' and ',$conds);
	}
	protected function where_sql(\ebi\Dao $dao,&$from,\ebi\Q $q,array $self_columns,$require_where=null,$alias=true){
		if($q->is_block()){
			$vars = $and_block_sql = $or_block_sql = [];
			$where_sql = '';

			foreach($q->ar_and_block() as $qa){
				list($where,$var) = $this->where_sql($dao,$from,$qa,$self_columns,null,$alias);
				if(!empty($where)){
					$and_block_sql[] = $where;
					$vars = array_merge($vars,$var);
				}
			}
			if(!empty($and_block_sql)) $where_sql .= ' ('.implode(' and ',$and_block_sql).') ';
			foreach($q->ar_or_block() as $or_block){
				list($where,$var) = $this->where_sql($dao,$from,$or_block,$self_columns,null,$alias);
				if(!empty($where)){
					$or_block_sql[] = $where;
					$vars = array_merge($vars,$var);
				}
			}
			if(!empty($or_block_sql)) $where_sql .= (empty($where_sql) ? '' : ' and ').' ('.implode(' or ',$or_block_sql).') ';

			if(empty($where_sql)){
				$where_sql = $require_where;
			}else if(!empty($require_where)){
				$where_sql = '('.$require_where.') and ('.$where_sql.')';
			}
			return [$where_sql,$vars];
		}
		if($q->type() == Q::MATCH){
			$query = new \ebi\Q();
			foreach($q->ar_arg1() as $cond){
				if(strpos($cond,'=') !== false){
					list($column,$value) = explode('=',$cond);
					$not = (substr($value,0,1) == '!');
					$value = ($not) ? ((strlen($value) > 1) ? substr($value,1) : '') : $value;
					
					if($value === ''){
						$query->add(($not) ? Q::neq($column,'') : Q::eq($column,''));
					}else{
						$query->add(($not) ? Q::contains($column,$value,$q->param()|Q::NOT) : Q::contains($column,$value,$q->param()));
					}
				}else{
					$columns = [];
					foreach($self_columns as $column) $columns[] = $column->name();
					$query->add(Q::contains(implode(',',$columns),explode(' ',$cond),$q->param()));
				}
			}
			return $this->where_sql($dao,$from,$query,$self_columns,null,$alias);
		}
		$and = $vars = [];
		foreach($q->ar_arg2() as $base_value){
			$or = [];
			foreach($q->ar_arg1() as $column_str){
				$value = $base_value;
				$column = $this->get_column($column_str,$self_columns);
				$column_alias = $this->column_alias_sql($column,$q,$alias);
				$is_add_value = true;

				switch($q->type()){
					case Q::EQ:
						if($value === null){
							$is_add_value = false;
							$column_alias .= ' is null'; break;
						}
						$column_alias .= ' = '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::NEQ:
						if($value === null){
							$is_add_value = false;
							$column_alias .= ' is not null'; break;
						}
						$column_alias .= ' <> '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::GT: $column_alias .= ' > '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::GTE: $column_alias .= ' >= '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::LT: $column_alias .= ' < '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::LTE: $column_alias .= ' <= '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::CONTAINS:
					case Q::START_WITH:
					case Q::END_WITH:
						$column_alias = $this->format_column_alias_sql($column,$q,$alias);
						$column_alias .= ($q->not() ? ' not' : '').' like(?)';
						$value = (($q->type() == Q::CONTAINS || $q->type() == Q::END_WITH) ? '%' : '')
						.$value
						.(($q->type() == Q::CONTAINS || $q->type() == Q::START_WITH) ? '%' : '');
						break;
					case Q::IN:
						$column_alias .= ($q->not() ? ' not' : '')
						.(($value instanceof \ebi\Daq) ?
						' in('.$value->unique_sql().')' :
						' in('.substr(str_repeat('?,',sizeof($value)),0,-1).')'
						);
						break;
				}
				if($value instanceof \ebi\Daq){
					$is_add_value = false;
					$vars = array_merge($vars,$value->ar_vars());
				}
				$add_join_conds = $dao->join_conds($column->name());
				if(!empty($add_join_conds)) $column_alias .= ' and '.$this->where_cond_columns($add_join_conds,$from);
				$or[] = $column_alias;

				if($is_add_value){
					if(is_array($value)){
						$values = [];
						
						foreach($value as $v){
							$values[] = ($q->ignore_case()) ? strtoupper($this->column_value($dao,$column->name(),$v)) : $this->column_value($dao,$column->name(),$v);
						}
						$vars = array_merge($vars,$values);
					}else{
						$vars[] = ($q->ignore_case()) ? strtoupper($this->column_value($dao,$column->name(),$value)) : $this->column_value($dao,$column->name(),$value);
					}
				}
			}
			$and[] = ' ('.implode(' or ',$or).') ';
		}
		return [implode(' and ',$and),$vars];
	}
	protected function update_value(\ebi\Dao $dao,$name){
		return $this->column_value($dao,$name,$dao->{$name}());
	}
	protected function get_column($column_str,array $self_columns){
		if(isset($self_columns[$column_str])){
			return $self_columns[$column_str];
		}
		foreach($self_columns as $c){
			if($c->name() == $column_str) return $c;
		}
		throw new \ebi\exception\InvalidArgumentException('undef '.$column_str);
	}
	protected function column_alias_sql(Column $column,\ebi\Q $q,$alias=true){
		$column_str = ($alias) ? $column->table_alias().'.'.$this->quotation($column->column()) : $this->quotation($column->column());
		if($q->ignore_case()) return 'upper('.$column_str.')';
		return $column_str;
	}
	protected function format_column_alias_sql(Column $column,\ebi\Q $q,$alias=true){
		return $this->column_alias_sql($column,$q,$alias);
	}
	protected function quotation($name){
		return $this->quotation.$name.$this->quotation;
	}
	public function create_table_sql(\ebi\Dao $dao){
		$quote = function($name){ return '`'.$name.'`'; };
		$to_column_type = function($dao,$type,$name) use($quote){
			switch($type){
				case '':
				case 'mixed':
				case 'string':
				case 'alnum':
				case 'text':
					return $quote($name).' TEXT';
				case 'number':
					return $quote($name).' REAL';
				case 'serial': return $quote($name).' INTEGER PRIMARY KEY AUTOINCREMENT';
				case 'boolean':
				case 'timestamp':
				case 'date':
				case 'time':
				case 'intdate':
				case 'integer': return $quote($name).' INTEGER';
				case 'email':
				default: throw new \ebi\exception\InvalidArgumentException('undefined type `'.$type.'`');
			}
		};
		$columndef = $primary = [];
		$sql = 'create table '.$quote($dao->table()).'('.PHP_EOL;
		
		foreach(array_keys($dao->columns(true)) as $prop_name){
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$to_column_type($dao,$dao->prop_anon($prop_name,'type'),$prop_name).' null ';
				$columndef[] = $column_str;
				if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial'){
					$primary[] = $quote($prop_name);
				}
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		$sql .= ' );'.PHP_EOL;
		return $sql;
	}
	public function exists_table_sql(\ebi\Dao $dao){
		return sprintf('select count(*) from sqlite_master where type=\'table\' and name=\'%s\'',$dao->table());
	}
	protected function create_table_prop_cond(\ebi\Dao $dao,$prop_name){
		return ($dao->prop_anon($prop_name,'extra') !== true && $dao->prop_anon($prop_name,'cond') === null);
	}
	public function drop_table_sql(\ebi\Dao $dao){
		$quote = function($name){
			return '`'.$name.'`';
		};
		$sql = 'drop table '.$quote($dao->table());
		return $sql;
	}
	protected function column_value(\ebi\Dao $dao,$name,$value){
		if($value === null) return null;
		try{
			switch($dao->prop_anon($name,'type')){
				case 'timestamp': return date('Y/m/d H:i:s',$value);
				case 'date': return date('Y/m/d',$value);
				case 'boolean': return (int)$value;
			}
		}catch(\Exception $e){
		}
		return $value;
	}
}


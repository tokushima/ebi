<?php
namespace ebi;
/**
 * DB接続IF
 * @author tokushima
 *
 */
abstract class DbConnector{
	protected $encode;
	protected $timezone;
	protected $quotation = '`';
	protected $order_random_str;
	
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
	}
	public function last_insert_id_sql(){
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
			if($column->auto()){
				$autoid = $column->name();
			}
			$insert[] = $this->quotation($column->column());
			$vars[] = $this->column_value($dao,$column->name(),$dao->{$column->name()}());
		}
		return new \ebi\Daq(
			'insert into '.$this->quotation($column->table()).' ('.implode(',',$insert).') values ('.implode(',',array_fill(0,sizeof($insert),'?')).');'
			,$vars
			,$autoid
		);
	}
	/**
	 * update文を生成する
	 * @param \ebi\Dao $dao
	 * @param \ebi\Q $query
	 * @param string[] $target
	 * @return Daq
	 */
	public function update_sql(\ebi\Dao $dao,\ebi\Q $query,$target){
		$where = $update = $wherevars = $updatevars = $from = [];
		
		foreach($dao->primary_columns() as $column){
			$where[] = $this->quotation($column->column()).' = ?';
			$wherevars[] = $this->column_value($dao,$column->name(),$dao->{$column->name()}());
		}
		if(empty($where)){
			throw new \ebi\exception\InvalidQueryException('primary not found');
		}
		
		$target_all = empty($target);
		foreach($dao->columns(true) as $column){
			if(!$column->primary() && ($target_all || in_array($column->name(),$target))){
				$update[] = $this->quotation($column->column()).' = ?';
				$updatevars[] = $this->column_value($dao,$column->name(),$dao->{$column->name()}());
			}
		}
		if(empty($update) || (!$target_all && sizeof($target) != sizeof($update))){
			throw new \ebi\exception\InvalidQueryException('no update column');
		}
		$vars = array_merge($updatevars,$wherevars);
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(true),null,false);
		return new \ebi\Daq(
			'update '.$this->quotation($column->table()).' set '.
			implode(',',$update).' where '.implode(' and ',$where).
			(empty($where_sql) ? '' : ' and '.$where_sql),
			array_merge($vars,$where_vars)
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
		if(empty($where)){
			throw new \ebi\exception\BadMethodCallException('not primary');
		}
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
		$break = false;
		$date_format = $query->ar_date_format();
		
		foreach($dao->columns() as $column){
			if($name === null || ($break = ($column->name() == $name))){
				$column_map = $column->table_alias().'.'.$this->quotation($column->column());
				$column_map = $this->select_column_format($column_map,$dao,$column,['date_format'=>$date_format]);

				$select[] = $column_map.' '.$column->column_alias();
				$from[$column->table_alias()] = $this->quotation($column->table()).' '.$column->table_alias();
				
				if($break){
					break;
				}
			}
		}
		if(empty($select)){
			throw new \ebi\exception\BadMethodCallException('select invalid');
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(),$this->where_cond_columns($dao->conds(),$from));
		return new \ebi\Daq((
			'select '.implode(',',$select).' from '.implode(',',$from)
			.(empty($where_sql) ? '' : ' where '.$where_sql)
			.$this->select_option_sql($paginator,$this->select_order($query,$dao->columns()))
		),$where_vars);
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
		.(($paginator instanceof \ebi\Paginator) ? sprintf(' limit %d,%d ',$paginator->offset(),$paginator->limit()) : '')
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
			
			if(!empty($primary_columns)){
				$target_column = current($primary_columns);
			}
			if(empty($target_column) && !empty($self_columns)){
				$target_column = current($self_columns);
			}
		}else{
			$target_column = $this->get_column($target_name,$dao->columns());
		}
		if(empty($target_column)){
			throw new \ebi\exception\BadMethodCallException('undef primary');
		}
		$date_format = $query->ar_date_format();
		$exec_map = $target_column->table_alias().'.'.$this->quotation($target_column->column());
		
		if(isset($date_format[$target_column->name()])){
			$exec_map = $this->date_format($exec_map,$dao,$target_column,$date_format[$target_column->name()]);
		}
		if(!empty($gorup_name)){
			$group_column = $this->get_column($gorup_name,$dao->columns());
			$column_map = $group_column->table_alias().'.'.$this->quotation($group_column->column());
			
			if(isset($date_format[$group_column->name()])){
				$column_map = $this->date_format($column_map,$dao,$group_column,$date_format[$group_column->name()]);
			}
			$select[] = $column_map.' key_column';			
		}
		foreach($dao->columns() as $column){
			$from[$column->table_alias()] = $this->quotation($column->table()).' '.$column->table_alias();
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->columns(),$this->where_cond_columns($dao->conds(),$from));
		
		return new \ebi\Daq(('select '.$exe.'('.$exec_map.') target_column'
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
		if($q->type() == Q::MATCH && sizeof($q->ar_arg1()) > 0){
			$query = new \ebi\Q();
			$target = $q->ar_arg2();
			$ob = $columns = [];
			
			foreach($self_columns as $column){
				if(empty($target) || in_array($column->name(),$target)){
					$columns[$column->name()] = $dao->prop_anon($column->name(),'type');
				}
			}
			foreach($columns as $cn => $ct){
				$and = [];
				
				foreach($q->ar_arg1() as $cond){
					$op = null;
					if(substr($cond,0,1) == '-'){
						$cond = substr($cond,1);
						$op = Q::NOT;
					}
					switch($ct){
						case 'number':
						case 'serial': 
						case 'integer':							
						case 'timestamp':
						case 'date':
						case 'time':
						case 'intdate':
							$and[] = Q::eq($cn,$cond,$op);
							break;
						case 'string':
						case 'text':
						case 'email':
						case 'alnum':
							$and[] = Q::contains($cn,$cond,$op);
							break;
						case 'boolean':
						case 'mixed':
						default:
					}
				}
				if(!empty($and)){
					$ob[] = call_user_func_array(['\ebi\Q','b'],$and);
				}
			}
			if(sizeof($ob) == 1){
				$query->add($ob[0]);
			}else{
				$query->add(call_user_func_array(['\ebi\Q','ob'],$ob));
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
							$column_alias .= ' is null';
							break;
						}
						$column_alias .= ' = '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
					case Q::NEQ:
						if($value === null){
							$is_add_value = false;
							$column_alias .= ' is not null';
							break;
						}
						$column_alias .= ' <> '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
					case Q::GT:
						$column_alias .= ' > '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
					case Q::GTE:
						$column_alias .= ' >= '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
					case Q::LT:
						$column_alias .= ' < '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
					case Q::LTE:
						$column_alias .= ' <= '.(($value instanceof \ebi\Daq) ? '('.$value->unique_sql().')' : '?');
						break;
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
				if(!empty($add_join_conds)){
					$column_alias .= ' and '.$this->where_cond_columns($add_join_conds,$from);
				}
				$or[] = $column_alias;

				if($is_add_value){
					if(is_array($value)){
						$values = [];
						
						foreach($value as $v){
							$values[] = ($q->ignore_case()) ? 
								strtoupper($this->column_value($dao,$column->name(),$v)) : 
								$this->column_value($dao,$column->name(),$v);
						}
						$vars = array_merge($vars,$values);
					}else{
						$vars[] = ($q->ignore_case()) ? 
							strtoupper($this->column_value($dao,$column->name(),$value)) : 
							$this->column_value($dao,$column->name(),$value);
					}
				}
			}
			$and[] = ' ('.implode(' or ',$or).') ';
		}
		return [implode(' and ',$and),$vars];
	}
	protected function column_value(\ebi\Dao $dao,$name,$value){
		if($value === null){
			return null;
		}
		try{
			switch($dao->prop_anon($name,'type')){
				case 'timestamp':
					if(!ctype_digit($value)){
						$value = strtotime($value);
					}
					return date('Y-m-d H:i:s',$value);
				case 'date':
					if(!ctype_digit($value)){
						$value = strtotime($value);
					}
					return date('Y-m-d',$value);
				case 'boolean':
					return (int)$value;
			}
		}catch(\Exception $e){
		}
		return $value;
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
		if($q->ignore_case()){
			return 'upper('.$column_str.')';
		}
		return $column_str;
	}
	protected function format_column_alias_sql(Column $column,\ebi\Q $q,$alias=true){
		return $this->column_alias_sql($column,$q,$alias);
	}
	protected function quotation($name){
		return $this->quotation.$name.$this->quotation;
	}
	private function to_column_type($dao,$type,$name){
		switch($type){
			case '':
			case 'mixed':
			case 'string':
			case 'alnum':
			case 'text':
				return $this->quotation($name).' TEXT';
			case 'number':
				return $this->quotation($name).' REAL';
			case 'serial':
				return $this->quotation($name).' INTEGER PRIMARY KEY AUTOINCREMENT';
			case 'boolean':
			case 'timestamp':
			case 'date':
			case 'time':
			case 'intdate':
			case 'integer':
				return $this->quotation($name).' INTEGER';
			case 'email':
				return $this->quotation($name).' TEXT';
			default:
				throw new \ebi\exception\InvalidArgumentException('undefined type `'.$type.'`');
		}
	}
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
	protected function select_column_format($column_map,$dao,$column,$info){
		if(isset($info['date_format'][$column->name()])){
			return $this->date_format($column_map,$dao,$column,$info['date_format'][$column->name()]);
		}
		return $column_map;
	}
	protected function date_format($column_map,$dao,$column,$require){
		return $column_map;
	}
}


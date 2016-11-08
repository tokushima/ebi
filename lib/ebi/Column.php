<?php
namespace ebi;
/**
 * column定義モデル
 * @author tokushima
 */
class Column{
	private $name;
	private $column;
	private $column_alias;
	private $table;
	private $table_alias;
	private $primary = false;
	private $auto = false;
	private $base = true;

	public function name($v=null){
		return ($v !== null) ? ($this->name = $v) : $this->name;
	}
	public function column($v=null){
		return ($v !== null) ? ($this->column = $v) : $this->column;
	}
	public function column_alias($v=null){
		return ($v !== null) ? ($this->column_alias = $v) : $this->column_alias;
	}
	public function table($v=null){
		return ($v !== null) ? ($this->table = $v) : $this->table;
	}
	public function table_alias($v=null){
		return ($v !== null) ? ($this->table_alias = $v) : $this->table_alias;
	}
	public function primary($v=null){
		return ($v !== null) ? ($this->primary = $v) : $this->primary;
	}
	public function auto($v=null){
		return ($v !== null) ? ($this->auto = $v) : $this->auto;
	}
	public function base($v=null){
		return ($v !== null) ? ($this->base = $v) : $this->base;
	}
	public function is_base(){
		return ($this->base === true);
	}
	public static function cond_instance($column,$column_alias,$table,$table_alias){
		$self = new self();
		$self->column($column);
		$self->column_alias($column_alias);
		$self->table($table);
		$self->table_alias($table_alias);
		$self->base(false);
		return $self;
	}
}

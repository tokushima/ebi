<?php
namespace ebi;

class Column{
	private $name;
	private $column;
	private $column_alias;
	private $table;
	private $table_alias;
	private $primary = false;
	private $auto = false;
	private $base = true;

	public function name(?string $v=null): ?string{
		return ($v !== null) ? ($this->name = $v) : $this->name;
	}
	public function column(?string $v=null): ?string{
		return ($v !== null) ? ($this->column = $v) : $this->column;
	}
	public function column_alias(?string $v=null): ?string{
		return ($v !== null) ? ($this->column_alias = $v) : $this->column_alias;
	}
	public function table(?string $v=null): ?string{
		return ($v !== null) ? ($this->table = $v) : $this->table;
	}
	public function table_alias(?string $v=null): ?string{
		return ($v !== null) ? ($this->table_alias = $v) : $this->table_alias;
	}
	public function primary(?bool $v=null): bool{
		return ($v !== null) ? ($this->primary = $v) : $this->primary;
	}
	public function auto(?bool $v=null): ?bool{
		return ($v !== null) ? ($this->auto = $v) : $this->auto;
	}
	public function base(?bool $v=null): ?bool{
		return ($v !== null) ? ($this->base = $v) : $this->base;
	}
	public function is_base(): bool{
		return ($this->base === true);
	}
	
	public static function cond_instance(string $column, string $column_alias, string $table, string $table_alias): self{
		$self = new self();
		$self->column($column);
		$self->column_alias($column_alias);
		$self->table($table);
		$self->table_alias($table_alias);
		$self->base(false);
		return $self;
	}
}

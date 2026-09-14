<?php
namespace test\db;
use \ebi\Attribute\Prop;
class SaveLoopBeforeSave extends \ebi\Dao{
	#[Prop(type:'serial', expose:false)]
	protected ?int $id = null;
	protected ?string $value = null;

	protected function __before_save__(bool $is_update): void{
		$this->value('B'.$this->value());
		$this->save();
	}
	protected function __after_save__(bool $is_update): void{
		$this->value($this->value().'A');
		$this->save();
	}
}

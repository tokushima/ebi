<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var string $value
 * @author tokushima
 */
class SaveLoopBeforeSave extends \ebi\Dao{
	protected $id;
	protected $value;
	
	protected function __before_save__(): void{
		$this->value('B'.$this->value());
		$this->save();
	}
	protected function __after_save__(): void{
		$this->value($this->value().'A');
		$this->save();
	}
}

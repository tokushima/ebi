<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var string $value
 * @author tokushima
 */
class SaveLoop extends \ebi\Dao{
	protected $id;
	protected $value;
	
	protected function __before_save__(bool $is_update): void{
		$this->value('B'.$this->value());
	}
	protected function __after_save__(bool $is_update): void{
		$this->value($this->value().'A');
		$this->save();
	}
}

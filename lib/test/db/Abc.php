<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var string $value
 * @author tokushima
 */
class Abc extends \ebi\Dao{
	protected $id;
	protected $value;
	
	public function create(){
		$req = new \ebi\Request();
		$this->value($req->in_vars('value'));
		$this->save();
		
		return ['id'=>$this->id];
	}
}

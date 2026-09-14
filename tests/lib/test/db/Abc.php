<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Abc extends \ebi\Dao{
	#[Prop(type:'serial', expose:false)]
	protected ?int $id = null;
	protected ?string $value = null;

	public function create(){
		$req = new \ebi\Request();
		$this->value($req->in_vars('value'));
		$this->save();

		return ['id'=>$this->id];
	}
}

<?php
namespace test\db;
use ebi\Q;
/**
 * @var serial $id
 * @var string $value
 * @author tokushima
 */
class Abc extends \ebi\Dao{
	protected $id;
	protected $value;
	
	/**
	 * @http_method post
	 * @return multitype:NULL
	 */
	public function create(){
		$req = new \ebi\Request();
		$this->value($req->in_vars('value'));
		$this->save();
		
		return ['id'=>$this->id];
	}
}

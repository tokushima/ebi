<?php
namespace test\model;

class Form extends \ebi\Object{
	protected $id;
	protected $value;
	protected $category_id;
	
	public function __construct($id,$value,$category_id){
		$this->id = $id;
		$this->value = $value;
		$this->category_id = $category_id;
	}
}
<?php
namespace ebi;

class User{
	private $id;
	
	public function __construct($id){
		$this->id = $id;
	}
	public function id(){
		return $this->id;
	}
}

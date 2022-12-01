<?php
namespace ebi;

class User{
	private ?string $id = null;
	
	public function __construct(string $id){
		$this->id = $id;
	}
	public function id(): ?string{
		return $this->id;
	}
}

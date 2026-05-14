<?php
namespace test;

use ebi\Attribute\Parameter;
use ebi\Attribute\Response;

class ItemsFixture{
	#[Parameter(name: 'tags', type: 'array', items: 'string')]
	#[Parameter(name: 'ids', type: 'array', items: 'int')]
	#[Response(name: 'names', type: 'array', items: 'string')]
	#[Response(name: 'counts', type: 'array', items: 'int')]
	public function action(): void{
	}
}

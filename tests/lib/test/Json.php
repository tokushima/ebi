<?php
namespace test;
use \ebi\Attribute\Prop;
class Json extends \ebi\Obj{
	#[Prop(expose:false)]
	protected ?int $abc = 123;
	protected ?string $def = 'aaa';
	protected ?int $ghi = 100;

	protected $jkl;
}

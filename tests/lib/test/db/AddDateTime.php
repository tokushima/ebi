<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AddDateTime extends DateTime{
	#[Prop(type:'datetime', auto_now:true)]
	protected ?int $ts = null;
	#[Prop(type:'date', auto_now:true)]
	protected ?int $date = null;
	#[Prop(type:'intdate', auto_now:true)]
	protected ?int $idate = null;
}

<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AddNowDateTime extends DateTime{
	#[Prop(type:'datetime', auto_now_add:true)]
	protected ?int $ts = null;
	#[Prop(type:'date', auto_now_add:true)]
	protected ?int $date = null;
	#[Prop(type:'intdate', auto_now_add:true)]
	protected ?int $idate = null;
}

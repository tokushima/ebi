<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AutoNow extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'datetime', auto_now:true)]
	protected ?int $ts = null;
	#[Prop(type:'date', auto_now:true)]
	protected ?int $date = null;
	#[Prop(type:'intdate', auto_now:true)]
	protected ?int $idate = null;
	protected $value1;
	protected $value2;
}

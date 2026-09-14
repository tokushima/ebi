<?php
namespace test\db;
use \ebi\Attribute\Prop;
class DateFormat extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'datetime')]
	protected ?int $ts = null;
	protected ?int $num = null;
}

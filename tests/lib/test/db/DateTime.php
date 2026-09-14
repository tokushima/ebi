<?php
namespace test\db;
use \ebi\Attribute\Prop;
class DateTime extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'datetime')]
	protected ?int $ts = null;
	#[Prop(type:'date')]
	protected ?int $date = null;
	#[Prop(type:'intdate')]
	protected ?int $idate = null;
	#[Prop(type:'intdate', max:8)]
	protected ?int $birthday = null;
}

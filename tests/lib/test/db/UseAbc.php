<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UseAbc extends \ebi\Dao{
	use \test\db\TraitAbc;

	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'datetime', auto_now_add:true)]
	protected ?int $create_date = null;
}

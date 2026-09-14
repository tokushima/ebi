<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Data extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'datetime')]
	protected ?int $create_date = null;
	protected ?int $num = null;
	protected ?string $val1 = null;
	protected ?string $val2 = null;

	public static function sample(){
		$static = new static();
		$static->create_date(time() - rand(0,86400*365*3));
		$static->num(rand(1,100));
		$static->val1(\ebi\Code::rand('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',100));
		$static->val1(\ebi\Code::rand('abcdefghijklmnopqrstuvwxyz0123456789',100));
		$static->save();

		return $static;
	}
}

<?php
namespace test\db;
/**
 * @var serial $id
 * @var timestamp $create_date
 * $var integer $num
 * @var string $val1
 * @var string $val2
 * @author tokushima
 */
class Data extends \ebi\Dao{
	protected $id;
	protected $create_date;
	protected $num;
	protected $val1;
	protected $val2;
	
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

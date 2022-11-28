<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var string $value
 * @table @['name'=>'abc','create'=>false]
 */
class AbcNoCreate extends \ebi\Dao{
	protected $id;
	protected $value;
}

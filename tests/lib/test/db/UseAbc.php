<?php
namespace test\db;
/**
 * @var serial $id
 * @var datetime $create_date @['auto_now_add'=>true]
 */
class UseAbc extends \ebi\Dao{
	use \test\db\TraitAbc;
	
	protected $id;
	protected $create_date;
}

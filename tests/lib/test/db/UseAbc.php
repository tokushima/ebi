<?php
namespace test\db;

class UseAbc extends \ebi\Dao{
	use \test\db\TraitAbc;
	
	protected $id;
	protected $create_date;
}

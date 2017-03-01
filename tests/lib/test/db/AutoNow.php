<?php
namespace test\db;
/**
 * @var timestamp $ts @['auto_now'=>true]
 * @var date $date @['auto_now'=>true]
 * @var intdate $idate @['auto_now'=>true]
 */
class AutoNow extends \ebi\Dao{
	protected $id;
	protected $ts;
	protected $date;
	protected $idate;
	protected $value1;
	protected $value2;
}

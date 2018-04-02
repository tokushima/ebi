<?php
namespace test\db;
/**
 * @var serial $id
 * @var timestamp $ts
 * @var date $date
 * @var intdate $idate
 * @var intdate $birthday @['max'=>8]
 */
class DateTime extends \ebi\Dao{
	protected $id;
	protected $ts;
	protected $date;
	protected $idate;
	protected $birthday;
}

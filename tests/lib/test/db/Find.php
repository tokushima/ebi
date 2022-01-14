<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var int $order
 * @var timestamp $updated
 * @var string $value1
 * @var string $value2
 */
class Find extends \ebi\Dao{
	protected $id;
	protected $order;
	protected $value1;
	protected $value2;
	protected $updated;
}

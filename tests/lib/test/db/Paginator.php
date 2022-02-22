<?php
namespace test\db;
/**
 * @var serial $id
 * @var int $order
 * @var timestamp $updated @['auto_now'=>true]
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'0']
 * @var string $code2 @['auto_code_add'=>true,'ctype'=>'a']
 */
class Paginator extends \ebi\Dao{
	protected $id;
	protected $order;
	protected $code1;
	protected $code2;
	protected $updated;
}

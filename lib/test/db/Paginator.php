<?php
namespace test\db;
/**
 * @var serial $id
 * @var number $order
 * @var timestamp $updated @['auto_add'=>true]
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit']
 * @var string $code2 @['auto_code_add'=>true,'ctype'=>'alpha']
 */
class Paginator extends \ebi\Dao{
	protected $id;
	protected $order;
	protected $code1;
	protected $code2;
	protected $updated;
}

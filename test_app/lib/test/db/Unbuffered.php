<?php
namespace test\db;
use ebi\Q;
/**
 * create table はされるはず
 * @var serial $id
 * @var string $value
 * @author tokushima
 */
class Unbuffered extends \ebi\Dao{
	protected $id;
	protected $value;
}

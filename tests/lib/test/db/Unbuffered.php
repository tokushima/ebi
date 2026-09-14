<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * create table はされるはず
 */
class Unbuffered extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
}

<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * Findが先に必要
 */
class RefFind extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $parent_id = null;
	#[Prop(from: [['parent_id', Find::class, 'id']], column:'value1')]
	protected ?string $value = null;
	#[Prop(via:'value')]
	protected ?string $value2 = null;

	private $private_value;
}

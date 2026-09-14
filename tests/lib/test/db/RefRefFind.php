<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * RefFindテーブル, Findテーブルが先に必要
 */
class RefRefFind extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $parent_id = null;
	#[Prop(from: [['parent_id', RefFind::class, 'id'], ['parent_id', Find::class, 'id']], column:'value1')]
	protected ?string $value = null;
}

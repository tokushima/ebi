<?php
namespace ebi\Attribute;

/**
 * パラメータ横断の必須制約：列挙したうち「1つ以上」が必須（OpenAPI parameters には
 * 表現手段が無いため、operation に x-required-any を出し、body がある場合は
 * JSON Schema の anyOf(required) でも表現する）。
 *
 * @example
 *   #[Parameter(name:'id',   type:'int')]
 *   #[Parameter(name:'code', type:'string')]
 *   #[Parameter(name:'email',type:'string')]
 *   #[RequiredAny(['id', 'code', 'email'])]   // id / code / email のいずれか1つ以上
 *   public function search() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RequiredAny{
	/** @param string[] $props 対象パラメータ名（1つ以上が必須） */
	public function __construct(
		public array $props,
	){}
}

<?php
namespace ebi\Attribute;

/**
 * リクエストパラメータを定義するAttribute（OpenAPI parameters相当）
 *
 * @example
 * #[Parameter(name: 'email', type: 'string', require: true)]
 * #[Parameter(name: 'age', type: 'int', min: 0, max: 150)]
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Parameter{
	public function __construct(
		public string $name,
		public string $type='string',
		public ?string $summary=null,
		public bool $require=false,
		public int|float|null $min=null,
		public int|float|null $max=null,
	){}
}

<?php
namespace ebi\Attribute;

/**
 * レスポンス変数を定義するAttribute（OpenAPI responses相当）
 *
 * @example
 * #[Response(name: 'user', type: 'App\Model\User')]
 * public function show() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Response{
	public function __construct(
		public string $name,
		public string $type='mixed',
		public ?string $summary=null,
	){}
}

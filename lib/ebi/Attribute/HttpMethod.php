<?php
namespace ebi\Attribute;

/**
 * HTTPメソッド制限を定義するAttribute
 *
 * @example
 * #[HttpMethod('POST')]
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class HttpMethod{
	public function __construct(
		public string $method='GET',
	){}
}

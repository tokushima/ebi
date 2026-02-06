<?php
namespace ebi\Attribute;

/**
 * URLルーティングを定義するAttribute
 *
 * @example
 * #[Automap(suffix: '.json', name: 'user_list')]
 * public function index() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Automap{
	public function __construct(
		public ?string $suffix=null,
		public ?string $name=null,
		public ?bool $secure=null,
		public ?string $after=null,
		public ?string $post_after=null,
	){}
}

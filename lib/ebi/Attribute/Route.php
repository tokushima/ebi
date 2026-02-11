<?php
namespace ebi\Attribute;

/**
 * URLルーティングを定義するAttribute
 *
 * @example
 * #[Route(suffix: '.json', name: 'user_list')]
 * public function index() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route{
	public function __construct(
		public ?string $suffix=null,
		public ?string $name=null,
		public ?bool $secure=null,
		public ?string $after=null,
		public ?string $post_after=null,
	){}
}

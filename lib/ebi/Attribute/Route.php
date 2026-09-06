<?php
namespace ebi\Attribute;

/**
 * URLルーティングを定義するAttribute
 *
 * @example
 * #[Route(suffix: '.json', name: 'user_list')]
 * public function index() {}
 *
 * query … after/post_after のリダイレクト先へ引き継ぐクエリ文字列（[パラメータ名 => 値]）。
 *   値の '@xxx' 記法は result 変数 xxx を参照する（旧 @automap @['query'=>[...]] と同義）。
 *   例: #[Route(after: 'item_info', query: ['client_id' => '@client_id'])]
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route{
	public function __construct(
		public ?string $suffix=null,
		public ?string $name=null,
		public ?bool $secure=null,
		public ?string $after=null,
		public ?string $post_after=null,
		public ?array $query=null,
		public ?string $redirect=null,
	){}
}

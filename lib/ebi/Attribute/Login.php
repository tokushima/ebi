<?php
namespace ebi\Attribute;

/**
 * 認証要件を定義するAttribute
 *
 * @example
 * #[Login(type: 'ebi\User')]
 * class UserApi extends \ebi\app\Request {}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Login{
	public function __construct(
		public ?string $type=null,
		public string|array|null $user_role=null,
	){}
}

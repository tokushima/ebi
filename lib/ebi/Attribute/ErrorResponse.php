<?php
namespace ebi\Attribute;

/**
 * エラーレスポンスを定義するAttribute（OpenAPI error responses相当）
 *
 * @example
 * #[ErrorResponse(status: 404, description: '紹介コードが不正な場合')]
 * #[ErrorResponse(status: 409, description: '既に紹介コードが登録されている場合')]
 * public function set_referral_code(): void {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ErrorResponse{
	public function __construct(
		public int $status,
		public string $description='',
	){}
}

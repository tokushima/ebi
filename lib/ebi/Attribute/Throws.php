<?php
namespace ebi\Attribute;

/**
 * 例外を定義するAttribute（@throws相当）
 * ステータスコードは例外クラス名から自動推定される
 *
 * @example
 * #[Throws(exception: \ebi\exception\NotFoundException::class, summary: '紹介コードが不正な場合')]
 * #[Throws(exception: \tolot\service\exception\AlreadyMemberException::class, summary: '既に紹介コードが登録されている場合')]
 * public function set_referral_code(): void {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Throws{
	public function __construct(
		public string $exception,
		public ?string $summary=null,
	){}
}

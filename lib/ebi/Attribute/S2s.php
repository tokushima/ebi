<?php
namespace ebi\Attribute;

/**
 * サーバー間通信（S2S）エンドポイントを示すAttribute
 *
 * @example
 * #[S2s]
 * class PaymentWebhook extends \ebi\app\Request {}
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class S2s{
	public function __construct(){}
}

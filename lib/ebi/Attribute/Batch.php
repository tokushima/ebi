<?php
namespace ebi\Attribute;

/**
 * バッチ（cron/daemon/キューワーカー/CLI 等、HTTP でも user 呼び出しでもない）handler を示すAttribute。
 * #[Requires]/#[Produces] を併用することで flow に「別アクター（batch）による状態遷移」として参加する。
 * 呼び出し可能ではない（callable:false）。起動機構（cron/daemon 等）や時刻は環境依存でソースに持たない。
 * Dt は Conf `ebi\Dt\OpenApi@flow_batch_classes` に登録されたクラスの静的メソッドを走査し、
 * #[Batch] を持つものを x-flow-batches として収集する。
 *
 * @example
 * #[Batch]
 * #[Requires('payment.authorized')]
 * #[Produces('payment.settled', via:'effect')]
 * public static function order_payment(): bool { ... }
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Batch{
	public function __construct(
		/** 表示名（任意, 既定はメソッド名） */
		public ?string $name=null,
	){}
}

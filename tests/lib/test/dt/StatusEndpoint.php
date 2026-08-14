<?php
namespace test\dt;
class StatusEndpoint extends \ebi\app\Request{
	/**
	 * 405を直接返す（例外なし）
	 * @context string $v 値
	 */
	public function methodcheck(): array{
		\ebi\HttpHeader::send_status(405);
		return ['v' => 'x'];
	}
}

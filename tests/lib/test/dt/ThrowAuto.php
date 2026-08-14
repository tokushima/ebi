<?php
namespace test\dt;

class ThrowAuto extends \ebi\app\Request{
	/**
	 * 自動検出テスト（@throwsは書かない）
	 * @context string $v 値
	 */
	public function pick(): array{
		throw new \ebi\exception\NotFoundException('not found');
	}

	/**
	 * 明示@throws（例外の実http_status=503にマップされることを検証）
	 * @throws \ebi\exception\ConnectionException 接続不可
	 * @context string $v 値
	 */
	public function conn(): array{
		return ['v' => 'x'];
	}
}

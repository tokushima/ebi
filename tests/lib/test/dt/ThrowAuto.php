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
}

<?php
namespace test\dt;
use \ebi\Attribute\Response;

class ThrowAuto extends \ebi\app\Request{
	/**
	 * 自動検出テスト（@throwsは書かない）
	 */
	#[Response(name:'v', type:'string', summary:'値')]
	public function pick(): array{
		throw new \ebi\exception\NotFoundException('not found');
	}

	/**
	 * 明示@throws（例外の実http_status=503にマップされることを検証）
	 * @throws \ebi\exception\ConnectionException 接続不可
	 */
	#[Response(name:'v', type:'string', summary:'値')]
	public function conn(): array{
		return ['v' => 'x'];
	}
}

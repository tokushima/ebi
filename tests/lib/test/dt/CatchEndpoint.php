<?php
namespace test\dt;
class CatchEndpoint extends \ebi\app\Request{
	/**
	 * 呼び先をtry/catchで包む（catch-awareで404が消えるはず）
	 * @context string $v 値
	 */
	public function guarded(): array{
		try{
			$s = new \test\dt\CatchService();
			$s->work();
		}catch(\ebi\exception\NotFoundException $e){
		}
		return ['v' => 'x'];
	}

	/**
	 * 包まない（404が残るはず）
	 * @context string $v 値
	 */
	public function unguarded(): array{
		$s = new \test\dt\CatchService();
		$s->work();
		return ['v' => 'x'];
	}
}

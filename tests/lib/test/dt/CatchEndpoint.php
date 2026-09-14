<?php
namespace test\dt;
use \ebi\Attribute\Response;
class CatchEndpoint extends \ebi\app\Request{
	/**
	 * 呼び先をtry/catchで包む（catch-awareで404が消えるはず）
	 */
	#[Response(name:'v', type:'string', summary:'値')]
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
	 */
	#[Response(name:'v', type:'string', summary:'値')]
	public function unguarded(): array{
		$s = new \test\dt\CatchService();
		$s->work();
		return ['v' => 'x'];
	}
}

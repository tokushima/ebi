<?php
namespace test\dt;
use \ebi\Attribute\Response;
class StatusEndpoint extends \ebi\app\Request{
	/**
	 * 405を直接返す（例外なし）
	 */
	#[Response(name:'v', type:'string', summary:'値')]
	public function methodcheck(): array{
		\ebi\HttpHeader::send_status(405);
		return ['v' => 'x'];
	}
}

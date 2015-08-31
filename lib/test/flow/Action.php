<?php
namespace test\flow;

class Action{
	public function abc(){
		/**
		 * Confのダミー
		 * @param string $aa ダミー
		 */
		$value = \ebi\Conf::get('value');
		$var = isset($_GET['abc']) ? $_GET['abc'] : null;
		return ['abc'=>$var];
	}
	public function raise(){
		throw new \LogicException('raise test');
	}
	public function exceptions_group(){
		\ebi\Exceptions::add(new \InvalidArgumentException('invalid argument'),'newgroup');
		\ebi\Exceptions::add(new \LogicException('logic'),'newgroup');
		\ebi\Exceptions::throw_over();
	}
	public function exceptions(){
		\ebi\Exceptions::add(new \InvalidArgumentException('invalid argument'));
		\ebi\Exceptions::add(new \LogicException('logic'));
		\ebi\Exceptions::throw_over();
	}
	
	public function exceptions405(){
		\ebi\HttpHeader::send_status(405);
		\ebi\Exceptions::add(new \LogicException('Method Not Allowed'));
		\ebi\Exceptions::throw_over();
	}	
	
	public function get_method(){
		$req = new \ebi\Request();
		
		ob_start();
			var_dump($req->ar_vars());
		$data = ob_get_clean();
		
		return ['method'=>($req->is_post() ? 'POST' : 'GET'),'data'=>$data];
	}
	public function log(){
		\ebi\Log::error('ERROR');
		\ebi\Log::warn('WARN');
		\ebi\Log::info('INFO');
		\ebi\Log::debug('DEBUG');
		\ebi\Log::trace('TRACE');
	}
}
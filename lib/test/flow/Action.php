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
	public function file_upload(){
		$req = new \ebi\Request();
		
		$file = $req->in_files('file1');
		
		return [
			'vars'=>$req->ar_vars(),
			'files'=>$req->ar_files(),
		];
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
		\ebi\Log::warning('WARN');
		\ebi\Log::info('INFO');
		\ebi\Log::debug('DEBUG');
		\ebi\Log::trace('TRACE');
	}
	public function form_obj(){
		$req = new \ebi\Request();
		return array_merge($req->ar_vars(),['object'=>new \test\model\Form(10,'ABC',999)]);
	}
	public function select(){
		return [
			'data_value'=>20,
			'data_list'=>[
				10=>'AAA',
				20=>'BBB',
				30=>'CCC',
			]
		];
	}
}
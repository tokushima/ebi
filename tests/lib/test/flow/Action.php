<?php
namespace test\flow;
/**
 * Sample Action
 * @author tokushima
 *
 */
class Action{
	/**
	 * 入力された文字を返す
	 * @request string $abc 返す文字列
	 * @context string $abc 入力された文字列
	 */
	public function abc(){
		$var = isset($_GET['abc']) ? $_GET['abc'] : null;
		
		return [
			'abc'=>$var,
		];
	}
	/**
	 * @request string $rrrrr らららら
	 * @return array
	 * @compatibility
	 */
	public function get_after_vars(){
		return [];
	}
	/**
	 * 常にLogicException
	 * @param string $aaa あああ
	 * @param \ebi\Dao $bbb いいい
	 * @request string $ccc メメメめ
	 * @context \ebi\Dao $ssss カカカカか
	 * @throws \LogicException 常に例外
	 * @version 20160102
	 */
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
	public function select_obj(){
		return [
			'data_value'=>20,
			'data_list'=>[
				new \test\model\Form(10,'AAA'),
				new \test\model\Form(20,'BBB'),
				new \test\model\Form(30,'CCC'),
			]
		];
	}
	
	/**
	 * 推奨しない
	 * @deprecated 2017-03-05 aaaa
	 * @context \test\model\DeprecatedModel $model 
	 */
	public function deprecated(){
		
	}
	
	/**
	 * リクエストだけdeprecated
	 * @request string $hoge 使わない @deprecated 2017-03-04 早めに
	 */
	public function request_deprecated(){
		$this->in_vars('hoge');
	}
	/**
	 * @context string $hoge 使わない　@deprecated 2017-03-04 早めに
	 */
	public function context_deprecated(){
		return ['hoge'=>1];
	}
	
	public function working_storage(){
		\ebi\WorkingStorage::tmpfile('AAA');
		$path = \ebi\WorkingStorage::tmpdir('TEMPDIR');
		
		\ebi\Util::file_write($path.'/BBB','BBB');
	}
}


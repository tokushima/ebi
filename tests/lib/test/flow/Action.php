<?php
namespace test\flow;
use \ebi\Attribute\Parameter;
use \ebi\Attribute\Response;
/**
 * Sample Action
 */
class Action{
	/**
	 * 入力された文字を返す
	 */
	#[Parameter(name:'abc', type:'string', summary:'返す文字列')]
	#[Response(name:'abc', type:'string', summary:'入力された文字列')]
	public function abc(){
		$var = isset($_GET['abc']) ? $_GET['abc'] : null;
		
		return [
			'abc'=>$var,
		];
	}
	#[Parameter(name:'rrrrr', type:'string', summary:'らららら')]
	public function get_after_vars(): array{
		return [];
	}
	/**
	 * 常にLogicException
	 * @param string $aaa あああ
	 * @param \ebi\Dao $bbb いいい
	 * @throws \LogicException 常に例外
	 * @version 20160102
	 */
	#[Parameter(name:'ccc', type:'string', summary:'メメメめ')]
	#[Response(name:'ssss', type:\ebi\Dao::class, summary:'カカカカか')]
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
	 */
	#[Response(name:'model', type:\test\model\DeprecatedModel::class)]
	public function deprecated(){
		
	}
	
	/**
	 * リクエストだけdeprecated
	 */
	#[Parameter(name:'hoge', type:'string', summary:'使わない', deprecated:true)]
	public function request_deprecated(){
		$this->in_vars('hoge');
	}
	#[Response(name:'hoge', type:'string', summary:'使わない', deprecated:true)]
	public function context_deprecated(){
		return ['hoge'=>1];
	}
	
	public function working_storage(){
		\ebi\WorkingStorage::tmpfile('AAA');
		$path = \ebi\WorkingStorage::tmpdir('TEMPDIR');
		
		\ebi\Util::file_write($path.'/BBB','BBB');
	}
}


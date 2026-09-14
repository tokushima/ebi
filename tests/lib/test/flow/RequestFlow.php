<?php
namespace test\flow;
use \ebi\Attribute\Parameter;
use \ebi\Attribute\HttpMethod;
use \ebi\Attribute\Response;
use \ebi\Attribute\OneOf;

/**
 * リクエストフロー
 * 様々な
 * @see https://google.com
 * @see \test\flow\RequestFlow::sendmail
 * @see \test\flow\RequestFlow
 */
class RequestFlow extends \ebi\flow\Request{	
	/**
	 * aaa
	 * bbb
	 */
	#[Parameter(name:'abc', type:'string', require:true)]
	#[Parameter(name:'def', type:'string', require:true)]
	#[Parameter(name:'ghi', type:'int')]
	public function require_vars(){
		$this->is_post();
	}
	
	#[HttpMethod('POST')]
	public function require_post(){
		$this->in_vars('abc');
	}
	#[HttpMethod('GET')]
	public function require_get(){
	
	}
	
	#[Parameter(name:'email', type:'email')]
	public function require_var_type(){

	}

	// #[OneOf]: id / code / email のうち「ちょうど1つ」が必須（0個でも2個以上でもエラー）。
	#[Parameter(name:'id', type:'int')]
	#[Parameter(name:'code', type:'string')]
	#[Parameter(name:'email', type:'string')]
	#[OneOf(['id', 'code', 'email'])]
	public function require_one(){
		return ['ok'=>1];
	}
	
	#[Response(name:'abc', type:'int')]
	#[Response(name:'def', type:'int')]
	public function get_vars(){
		return ['abc'=>123,'def'=>456];
	}
	
	#[Parameter(name:'file1', type:'file', require:true, max:0.001)]
	public function file_upload(){
		$req = new \ebi\Request();
		
		return [
			'vars'=>$req->ar_vars(),
			'files'=>$req->ar_files(),
		];
	}
}
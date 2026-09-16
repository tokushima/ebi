<?php
namespace test\flow;

class RequestAction{
	public function index(){
		$req = new \ebi\Request();
		$req->vars('get_file',$req->in_files('file'));
		
		$req->vars('get_file_base64',$req->in_files('filebase64'));
		$req->vars('get_file_base64_fail',$req->in_files('filebase64_fail'));
						
		return $req->ar_vars();
	}
	
	/**
	 * 自ホストの /index/request へリダイレクトする（worker 毎にポートが変わるため動的に解決する）
	 */
	public function redirect(){
		header('Location: '.\ebi\Dt::base_url('/index/request'));
		exit;
	}
	
	public function plain(){
		$req = new \ebi\Request();
		return $req->ar_vars();
	}
}
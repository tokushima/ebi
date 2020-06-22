<?php
namespace test\flow;

class RequestAction{
	public function index(){
		$req = new \ebi\Request();
		$req->vars('get_file',$req->in_files('file'));
		
		$req->vars('get_file_base64',$req->in_files('filebase64'));
		$req->vars('get_file_base64_fail',$req->in_files('filebase64_fail'));
		
		$req->vars('get_cookie',$req->read_cookie('cookiedata',0));
		
		$req->write_cookie('cookiedata',$req->in_vars('get_cookie') + 1);
		
		return $req->ar_vars();
	}
	
	/**
	 * http://localhost:8000/index/request へリダイレクトする
	 */
	public function redirect(){
		header('Location: http://localhost:8000/index/request');
		exit;
	}
	
	public function plain(){
		$req = new \ebi\Request();
		return $req->ar_vars();
	}
}
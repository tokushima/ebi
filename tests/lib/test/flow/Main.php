<?php
namespace test\flow;

class Main extends \ebi\flow\Request{
	/**
	 * @automap
	 */
	public function index(){
		$paginator = \ebi\Paginator::request($this,10);
		$paginator->total(1000);
		
		return $this->ar_vars([
			'paginator'=>$paginator,
		]);
	}
}

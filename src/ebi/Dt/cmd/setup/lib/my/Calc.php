<?php
namespace my;

class Calc{
	/**
	 * 足し算
	 * @request integer $a 計算値A
	 * @request integer $b 計算値B
	 * 
	 * @context integer $a 計算値A
	 * @context integer $b 計算値B
	 * @context integer $sum 計算結果
	 */
	public function add(){
		$req = new \ebi\Request();
		
		if($req->is_post()){
			return [
				'a'=>$req->in_vars('a'),
				'b'=>$req->in_vars('b'),
				'sum'=>($req->in_vars('a') + $req->in_vars('b')),
			];
		}
	}
}

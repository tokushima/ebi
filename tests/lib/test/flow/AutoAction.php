<?php
namespace test\flow;

class AutoAction{
	/**
	 * @automap
	 */
	public function index(){
		return ['aaaa'=>'index'];
	}
	
	public function abc(){
		return ['aaaa'=>'abc'];		
	}
	/**
	 * @automap
	 */
	public function def(){
		return ['aaaa'=>'def'];
	}
	/**
	 * アイウエオカキクケコ
	 * @automap
	 * @param string $ghi AAAA
	 * @request string $abc 入力１ @['require'=>true]
	 * @request integer $def 入力２
	 * @context \test\db\AutoCodeNumberPrefix $prefix DBモデル
	 * @context string $aaaa アイウエオ
	 * @context integer $bbbb 1234
	 * @context string $dep もう利用しないで欲しい @deprecated 2016/12/15
	 * @throws \ebi\exception\GenerateUniqueCodeRetryLimitOverException ユニークコードエクセプション
	 */
	public function ghi($a){
		$req = new \ebi\Request();
		unset($a);
		
		$abc = $req->in_vars('abc');
		$def = $req->in_vars(' def');
		
		if(false){
			throw new \ebi\exception\GenerateUniqueCodeRetryLimitOverException();
		}
		
		$address = "test@email.address";
		$vars = [
			'aaa'=>'ABC',
			'bbb'=>'XYZ',
			'ccc'=>new \test\db\AutoCodeNumberPrefix(),
		];
		$mail = new \ebi\Mail();
		$mail->to($address);
		
		/**
		 *
		 * @param string $aaa ABCが出せる
		 * @param string $bbb XYZが出せる
		 * @param \test\db\AutoCodeNumberPrefix $ccc DBモデル
		 */
		$mail->send_template('auto_action_send.xml',$vars);
		
		/**
		 * メソッド　プラグイン
		 * @param string $address
		 */
		self::call_class_plugin_funcs('plguin_auto_action_ghi',$address);
		
		return [
			'aaaa'=>'ghi',
			'bbbb'=>1234,
			'prefix'=>new \test\db\AutoCodeNumberPrefix(),
			'dep'=>'Depricated Value',
		];
	}
	/**
	 * @automap
	 * @param string $a
	 * @param strgin $b
	 * @param string $c
	 */
	public function jkl($a,$b,$c=null){
		unset($a,$b,$c);
		return ['aaaa'=>'jkl'];
	}
	
	/**
	 * @automap @['secure'=>false]
	 */
	public function nosecure(){
		
	}
}
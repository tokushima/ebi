<?php
namespace test\flow;

class AutoAction{
	use \ebi\Plugin;
	
	/**
	 * @automap
	 */
	public function index(){
		$address = "test@email.address";
		$mail = new \ebi\Mail();
		$mail->to($address);
		/**
		 * indexで送信される
		 * @param integer $aaa 数値の変数B
		 */
		$mail->send_template('auto_action/index.xml',['aaa'=>1]);
		
		return ['aaaa'=>'index'];
	}
	
	public function abc(){
		$address = "test@email.address";
		$mail = new \ebi\Mail();
		$mail->to($address);
		/**
		 * abcで送信される
		 * @param integer $bbb 数値の変数B
		 */
		$mail->send_template('auto_action/abc.xml',['bbb'=>1]);
		
		return ['aaaa'=>'abc'];		
	}
	/**
	 * @automap
	 */
	public function def(){
		$address = "test@email.address";
		$mail = new \ebi\Mail();
		$mail->to($address);
		/**
		 * defで送信される
		 * @param integer $ccc 数値の変数B
		 */
		$mail->send_template('auto_action/def.xml',['ccc'=>1]);
		
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
	 * @param string $b
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
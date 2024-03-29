<?php
namespace test\flow;

class AutoAction{
	/**
	 * @automap
	 */
	public function index(){
		$address = "test@email.address";
		$mail = new \ebi\Mail();
		$mail->from($address);		
		$mail->to($address);
		/**
		 * indexで送信される
		 * @param int $aaa 数値の変数B
		 */
		$mail->send_template('auto_action/index.xml',['aaa'=>1]);
		
		return ['aaaa'=>'index'];
	}
	
	public function abc(){
		$address = "test@email.address";
		$mail = new \ebi\Mail();
		$mail->from($address);
		$mail->to($address);
		/**
		 * abcで送信される
		 * @param int $bbb 数値の変数B
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
		$mail->from($address);
		$mail->to($address);
		/**
		 * defで送信される
		 * @param int $ccc 数値の変数B
		 */
		$mail->send_template('auto_action/def.xml',['ccc'=>1]);
		
		return ['aaaa'=>'def'];
	}
	/**
	 * アイウエオカキクケコ
	 * @automap
	 * @param string $ghi AAAA
	 * @request string $abc 入力１ @['require'=>true]
	 * @request int $def 入力２
	 * @context \test\db\AutoCodeNumberPrefix $prefix DBモデル
	 * @context string $aaaa アイウエオ
	 * @context int $bbbb 1234
	 * @context string $dep もう利用しないで欲しい @deprecated 2016/12/15
	 * @throws \ebi\exception\GenerateUniqueCodeRetryLimitOverException ユニークコードエクセプション
	 */
	public function ghi($a){
		if(false){
			throw new \ebi\exception\GenerateUniqueCodeRetryLimitOverException();
		}
		
		/**
		 * コメントコメント
		 * @var string $address
		 */
		
		$address = "test@email.address";
		$vars = [
			'aaa'=>'ABC',
			'bbb'=>'XYZ',
			'ccc'=>new \test\db\AutoCodeNumberPrefix(),
		];
		$mail = new \ebi\Mail();
		$mail->from($address);
		$mail->to($address);
		
		/**
		 * 
		 * @param string $aaa ABCが出せる
		 * @param string $bbb XYZが出せる
		 * @param \test\db\AutoCodeNumberPrefix $ccc DBモデル
		 * @real auto_action_send.xml
		 */
		$mail->send_template(sprintf('auto_action_%s.xml','send'),$vars);
				
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
	 * @automap
	 * @param string $a
	 * @param string $b
	 */
	public function mno($a,$b){
		return [
			'A'=>$a,
			'B'=>$b,
		];
	}
	
	/**
	 * @automap @['secure'=>false]
	 */
	public function nosecure(){
		
	}

	/**
	 * @automap @['after'=>'after_a', 'post_after'=>'after_b']
	 */
	public function after(){
	}

	/**
	 * @automap
	 */
	public function after_a(){
	}
	/**
	 * @automap
	 */
	public function after_b(){
	}
}
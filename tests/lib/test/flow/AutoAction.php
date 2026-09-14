<?php
namespace test\flow;
use \ebi\Attribute\Route;
use \ebi\Attribute\Parameter;
use \ebi\Attribute\Response;

class AutoAction{
	#[Route]
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
	#[Route]
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
	 * @param string $ghi AAAA
	 * @throws \ebi\exception\GenerateUniqueCodeRetryLimitOverException ユニークコードエクセプション
	 */
	#[Route]
	#[Parameter(name:'abc', type:'string', summary:'入力１', require:true)]
	#[Parameter(name:'def', type:'int', summary:'入力２')]
	#[Response(name:'prefix', type:\test\db\AutoCodeNumberPrefix::class, summary:'DBモデル')]
	#[Response(name:'aaaa', type:'string', summary:'アイウエオ')]
	#[Response(name:'bbbb', type:'int', summary:'1234')]
	#[Response(name:'dep', type:'string', summary:'もう利用しないで欲しい', deprecated:true)]
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
	 * @param string $a
	 * @param string $b
	 * @param string $c
	 */
	#[Route]
	public function jkl($a,$b,$c=null){
		unset($a,$b,$c);
		return ['aaaa'=>'jkl'];
	}
	
	/**
	 * @param string $a
	 * @param string $b
	 */
	#[Route]
	public function mno($a,$b){
		return [
			'A'=>$a,
			'B'=>$b,
		];
	}
	
	#[Route(secure:false)]
	public function nosecure(){
		
	}

	#[Route(after:'after_a', post_after:'after_b')]
	public function after(){
	}

	#[Route]
	public function after_a(){
	}
	#[Route]
	public function after_b(){
	}
}
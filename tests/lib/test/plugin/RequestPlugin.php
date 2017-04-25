<?php
namespace test\plugin;

class RequestPlugin{
	/**
	 * メールの拡張
	 * @param string $address
	 * @version 1.3.4
	 */
	public function plguin_sendmail($address){
		if(true){
			$mail = new \ebi\Mail();
			$mail->to($address);
			$vars = [
				'abc'=>123,
				'def'=>'XYZ',
			];
			
			/**
			 * 拡張で送信されるA
			 * @param integer $abc 数値の変数A
			 * @param string $def 文字列の変数A
			 */
			$mail->send_template('plugin/send.xml',$vars);
		}else{
			$mail = new \ebi\Mail();
			$mail->to($address);
			$vars = [
				'abc'=>123,
				'def'=>'XYZ',
				'ghi'=>true,
			];
			
			/**
			 * 拡張で送信されるB
			 * @param integer $abc 数値の変数B
			 * @param string $def 文字列の変数B
			 * @param boolean $ghi 真偽値の変数B
			 */
			$mail->send_template('plugin/nosend.xml',$vars);
		}
	}
}
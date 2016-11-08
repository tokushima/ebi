<?php
namespace test\plugin;

class RequestPlugin{
	/**
	 * メールの拡張
	 * @param string $address
	 */
	public function plguin_sendmail($address){
		$mail = new \ebi\Mail();
		$mail->to($address);
		$mail->send_template('plugin/send.xml',$vars);
	}
}
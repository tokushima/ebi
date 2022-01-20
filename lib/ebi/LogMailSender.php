<?php
namespace ebi;

class LogMailSender{
	public function log_output(\ebi\Log $log){
		$mail = new \ebi\Mail();
		
		/**
		 * @param mixed{} $arg1 メールにバインドする変数
		 */
		$conf_vars = \ebi\Conf::gets('vars');
		/**
		 * @param string $arg1 fromのメールアドレス
		 */
		$from = \ebi\Conf::get('from');
		/**
		 * @param string $arg1 toのメールアドレス
		 */
		$to = \ebi\Conf::get('to');
		
		if(!empty($from)){
			if(is_string($from)){
				$mail->from($from);
			}else if(isset($from['address'])){
				$mail->from($from['address'],(isset($from['name']) ? $from['name'] : null));
			}
		}
		if(!empty($to)){
			if(is_string($to)){
				$mail->to($to);
			}else if(isset($to['address'])){
				$mail->to($to['address'],(isset($to['name']) ? $to['name'] : null));
			}else if(is_array($to)){
				foreach($to as $t){
					if(isset($t['address'])){
						$mail->to($t['address'],(isset($t['name']) ? $t['name'] : null));
					}
				}
			}
		}
		
		$vars = [
			'log'=>$log,
			'env'=>new \ebi\Env($conf_vars),
		];
		
		try{
			switch($log->level()){
				case 0:
					/**
					 * Sending Emergency Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/emergency.xml',$vars);
					break;
				case 1:
					/**
					 * Sending Alert Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/alert.xml',$vars);
					break;
				case 2:
					/**
					 * Sending Critical Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/critical.xml',$vars);
					break;
				case 3:
					/**
					 * Sending Error Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/error.xml',$vars);
					break;
				case 4:
					/**
					 * Sending Warning Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/warning.xml',$vars);
					break;
				case 5:
					/**
					 * Sending Notice Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/notice.xml',$vars);
					break;
				case 6:
					/**
					 * Sending Infomation Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/info.xml',$vars);
					break;
				case 7:
					/**
					 * Sending Debug Mail
					 * @param \ebi\Log $log
					 * @param \ebi\Env $env
					 */
					$mail->send_template('logs/debug.xml',$vars);
					break;
			}
		}catch(\ebi\exception\InvalidArgumentException $e){
		}
	}
}
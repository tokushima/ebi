<?php
namespace ebi;
/**
 * ログをメール送信する
 *
 * 以下パスにテンプレートファイルがあれば送信
 *
 * @author tokushima
 *
 */
class LogMailSender{
	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 */
	public function log_output(\ebi\Log $log){
		$mail = new \ebi\Mail();			
		
		/**
		 * メールにバインドする変数
		 * @param mixed{} $arg1
		 */
		$vars = \ebi\Conf::gets('vars');
		/**
		 * fromのメールアドレス
		 * @param string $arg1
		 */
		$from = \ebi\Conf::get('from');
		/**
		 * toのメールアドレス
		 * @param string $arg1
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
		if(!is_array($vars)){
			$vars = [];
		}
		$template = '';
		
		switch($log->level()){
			case 0:
				$template = 'logs/emergency.xml';
				break;
			case 1:
				$template = 'logs/alert.xml';
				break;
			case 2:
				$template = 'logs/critical.xml';
				break;
			case 3:
				$template = 'logs/error.xml';
				break;
			case 4:
				$template = 'logs/warning.xml';
				break;
			case 5:
				$template = 'logs/notice.xml';
				break;
			case 6:
				$template = 'logs/info.xml';
				break;
			case 7:
				$template = 'logs/debug.xml';
				break;					
		}
		
		try{
			$mail->send_template(
				$template,
				array_merge(
					$vars,
					[
						'log'=>$log,
						'env'=>new \ebi\Env(),
					]
				)
			);
		}catch(\ebi\exception\InvalidArgumentException $e){
		}
	}
}

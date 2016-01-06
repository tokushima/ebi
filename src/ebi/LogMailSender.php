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
	private $template_base;

	public function log_output(\ebi\Log $log){
		if(empty($this->template_base)){
			/**
			 * mailテンプレートのディレクトリ
			 * ファイルあった場合のみ送信する
			 */
			$this->template_base = \ebi\Conf::get('template_dir',\ebi\Conf::resource_path('log_mail'));
		}
		$template = \ebi\Util::path_absolute($this->template_base,$log->fm_level().'.xml');
		
		if(is_file($template)){
			$mail = new \ebi\Mail();			
			
			/**
			 * メールにバインドする変数
			 * @param mixed{} $arg1
			 */
			$vars = \ebi\Conf::get('vars',[]);
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
		}
	}
}

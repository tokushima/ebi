<?php
namespace ebi;
/**
 * ログをメール送信する
 *
 * 以下パスにテンプレートファイルがあれば送信
 * [template_path]/debug.xml
 * [template_path]/info.xml
 * [template_path]/warn.xml
 * [template_path]/error.xml
 *
 * @author tokushima
 *
 */
class LogMailSender{
	private $template_base;

	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 * @param string $id
	 */
	public function debug(\ebi\Log $log){
		$this->send('debug',$log);
	}
	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 * @param string $id
	 */
	public function info(\ebi\Log $log){
		$this->send('info',$log);
	}
	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 * @param string $id
	 */
	public function warn(\ebi\Log $log){
		$this->send('warn',$log);
	}
	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 * @param string $id
	 */
	public function error(\ebi\Log $log){
		$this->send('error',$log);
	}
	protected function send($level,\ebi\Log $log){
		if(empty($this->template_base)){
			/**
			 * mailテンプレートのディレクトリ (debug.xml, info.xml, warn.xml, error.xml)
			 * ファイルあった場合のみ送信する
			 */
			$this->template_base = \ebi\Conf::get('template_dir',\ebi\Conf::resource_path('log_mail'));
		}
		$template = \ebi\Util::path_absolute($this->template_base,$level.'.xml');
		
		if(is_file($template)){
			$vars = \ebi\Conf::get('vars',[]);
			if(!is_array($vars)){
				$vars = [];
			}			
			$mail = new \ebi\Mail();
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

<?php
namespace ebi;
/**
 * ログを標準出力に送信する
 * 
 * @author tokushima
 *
 */
class LogStdout{
	/**
	 * @plugin \ebi\Log
	 * @param \ebi\Log $log
	 */
	public function log_output(\ebi\Log $log){
		if($log->level() > 3 || $log->level() == -1){
			$fp = fopen('php://stdout','rb');
				fwrite($fp,(string)$log);
			fclose($fp);
		}else{
			$fp = fopen('php://stderr','rb');
				fwrite($fp,(string)$log);
			fclose($fp);
		}
	}
}

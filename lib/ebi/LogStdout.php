<?php
namespace ebi;

class LogStdout{
	public function log_output(\ebi\Log $log){
		$msg = ((string)$log);
		
		/**
		 * @param bool $color 出力にカラーコードを適用する
		 */
		if(\ebi\Conf::get('color',true) === true){
			
			$color = [-1=>0,0=>'1;35',1=>'1;35',2=>'0;31',3=>'0;31',4=>'0;33',5=>'0;36',6=>'0;36',7=>0];
			
			if(!empty($color[$log->level()])){
				$msg = "\033[0;".$color[$log->level()]."m".$msg."\033[0m";
			}
		}
		if($log->level() > 3 || $log->level() == -1){
			file_put_contents('php://stdout',$msg.PHP_EOL);
		}else{
			file_put_contents('php://stderr',$msg.PHP_EOL);
		}
	}
}

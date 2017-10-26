<?php
namespace ebi;
/**
 * 
 * @author tokushima
 *
 */
class Benchmark{
	private static $record_info = [];
	private static $shutdowninfo = [];
	
	/**
	 * スクリプトの終了時に結果を書き出す
	 * @param string $record_file 書き出し先ファイルパス
	 * @param boolean $avg 平均にまとめる
	 */
	public static function register_shutdown($record_file,$avg=true){
		if(empty(self::$shutdowninfo)){
			if(!is_file($record_file)){
				if($avg){
					\ebi\Util::file_write($record_file,sprintf("%s\t%s\t%s\t%s".PHP_EOL,'Path','Time','Mem','Peak Mem'));
				}
			}
			self::$shutdowninfo = [
				'm'=>memory_get_usage(),
				't'=>microtime(true),
			];
			
			register_shutdown_function(function() use ($record_file,$avg){
				$mem = memory_get_usage() - self::$shutdowninfo['m'];
				$path = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
				
				if(empty($path)){
					$path = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
				}
				$exe_time = round((microtime(true) - (float)self::$shutdowninfo['t']),4);
				$values = [$path,$exe_time,$mem,memory_get_peak_usage()];
				
				if($avg){
					$report = [];
					
					foreach(file($record_file) as $line){
						$exp = explode("\t",trim($line));
						
						if(sizeof($exp) == 5){
							$report[$exp[0]] = $exp;
						}
					}
					if(isset($report[$path])){
						$report[$path] = [
							$path,
							(($values[1]+$report[$path][1])/2),
							(($values[2]+$report[$path][2])/2),
							(($values[3]+$report[$path][3])/2),
							($report[$path][4]+1)
						];
					}else{
						$report[$path] = [$path,$values[1],$values[2],$values[3],1];
					}
					\ebi\Util::file_write($record_file,sprintf("%s\t%s\t%s\t%s\t%s".PHP_EOL,'Path','Time','Mem','Peak Mem','Req'));
					
					unset($report['Path']);
					foreach($report as $p => $v){
						\ebi\Util::file_append($record_file,implode("\t",$v).PHP_EOL);
					}
				}else{
					\ebi\Util::file_append($record_file,implode("\t",$values).PHP_EOL);
				}
			});
		}		
	}
	/**
	 * 開始する
	 * @return mixed[]
	 */
	public static function start_record(){
		if(empty(self::$record_info)){
			self::$record_info = [
				'm'=>memory_get_usage(),
				't'=>microtime(true),
			];
		}
	}
	/**
	 * 終了する
	 * @return mixed[]
	 */
	public static function stop_record(){
		self::start_record();
		
		$info = [
			'memory'=>(memory_get_usage() - self::$record_info['m']),
			'memory_peak'=>memory_get_peak_usage(),
			'time'=>round((microtime(true) - (float)self::$record_info['t']),4),
		];
		self::$record_info = [];
		
		return $info;
	}
}

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
	 */
	public static function register_shutdown($record_file){
		if(empty(self::$shutdowninfo)){
			self::$shutdowninfo = [
				'm'=>memory_get_usage(),
				't'=>microtime(true),
			];
			
			register_shutdown_function(function() use ($record_file){
				$report = [];
				$path = $_SERVER['PATH_INFO'] ?? ($_SERVER['PHP_SELF'] ?? '');
				
				if(is_file($record_file)){
					foreach(file($record_file) as $line){
						$exp = explode("\t",trim($line));
						
						if(sizeof($exp) == 5){
							$report[$exp[0]] = $exp;
						}
					}
					if(isset($report['Path'])){
						unset($report['Path']);
					}
				}
				$report[$path][0] = $path;
				$report[$path][1] = round((microtime(true) - (float)self::$shutdowninfo['t']),4);
				$report[$path][2] = memory_get_usage() - self::$shutdowninfo['m'];
				$report[$path][3] = memory_get_peak_usage();
				$report[$path][4] = 1 + ($report[$path][4] ?? 0);
				
				\ebi\Util::file_write($record_file,sprintf("%s\t%s\t%s\t%s\t%s".PHP_EOL,'Path','Time','Mem','Peak Mem','Req'));
				
				foreach($report as $p => $v){
					\ebi\Util::file_append($record_file,implode("\t",$v).PHP_EOL);
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
			'time'=>round((microtime(true) - (float)self::$record_info['t']),4),
			'memory'=>ceil(memory_get_usage() - self::$record_info['m']),
			'memory_peak'=>ceil(memory_get_peak_usage()),
		];
		self::$record_info = [];
		
		return $info;
	}
}

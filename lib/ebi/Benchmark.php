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
	
	public static function register_shutdown($record_file){
		if(empty(self::$shutdowninfo)){
			\ebi\Util::file_append($record_file,'');
			
			self::$shutdowninfo = [
				'm'=>memory_get_usage(),
				't'=>microtime(true),
			];
			
			register_shutdown_function(function() use ($record_file){
				$mem = memory_get_usage() - self::$shutdowninfo['m'];
				$path = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
				
				if(empty($path)){
					$path = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
				}
				$exe_time = round((microtime(true) - (float)self::$shutdowninfo['t']),4);
				
				$value = sprintf(
					"%s\t%s\t%s\t%s".PHP_EOL,
					$path,
					$exe_time,
					$mem,
					memory_get_peak_usage()
				);
				
				\ebi\Util::file_append($record_file,$value);
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

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
			if(is_file($record_file) && filemtime($record_file) < (time() - 600)){
				\ebi\Util::rm($record_file);
			}
			if(!$avg && !is_file($record_file)){
				\ebi\Util::file_write($record_file,sprintf("%s\t%s\t%s\t%s".PHP_EOL,'Path','Time','Mem','Peak Mem'));
			}
			self::$shutdowninfo = [
				'm'=>memory_get_usage(),
				't'=>microtime(true),
			];
			
			register_shutdown_function(function() use ($record_file,$avg){
				$path = (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
				
				if(empty($path)){
					$path = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
				}
				$mem = memory_get_usage() - self::$shutdowninfo['m'];
				$peak = memory_get_peak_usage();
				$exe_time = round((microtime(true) - (float)self::$shutdowninfo['t']),4);
				$values = [$path,$exe_time,$mem,$peak];
				
				if($avg){
					$report = [];
					
					if(is_file($record_file)){
						foreach(file($record_file) as $line){
							$exp = explode("\t",trim($line));
							
							if(sizeof($exp) == 5){
								$report[$exp[0]] = $exp;
							}
						}
					}
					if(isset($report[$path])){
						$report[$path] = [
							$path,
							round((($values[1]+$report[$path][1])/2),4),
							ceil((($values[2]+$report[$path][2])/2)),
							ceil((($values[3]+$report[$path][3])/2)),
							($report[$path][4]+1)
						];
					}else{
						$report[$path] = $values;
						$report[$path][4] = 1; // req
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
			'time'=>round((microtime(true) - (float)self::$record_info['t']),4),
			'memory'=>ceil(memory_get_usage() - self::$record_info['m']),
			'memory_peak'=>ceil(memory_get_peak_usage()),

		];
		self::$record_info = [];
		
		return $info;
	}
}

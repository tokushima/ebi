<?php
namespace ebi;
/**
 * ログ処理
 *
 * @author tokushima
 * @var string $level ログのレベル
 * @var timestamp $time 発生時間
 * @var string $file 発生したファイル名
 * @var integer $line 発生した行
 * @var mixed $value 内容
 */
class Log{
	use \ebi\Plugin;
	
	private static $level_strs = ['none','error','warn','info','debug'];
	private static $logs = [];
	private static $id;
	private static $current_level;
	private static $disp = true;

	private $level;
	private $time;
	private $file;
	private $line;
	private $value;
		
	private static function cur_level(){
		if(self::$current_level === null){
			register_shutdown_function([__CLASS__,'flush']);
			/**
			 * ログレベル (none,error,warn,info,debug)
			 */
			self::$current_level = array_search(\ebi\Conf::get('level','none'),self::$level_strs);
		}
		return self::$current_level;
	}
	public function __construct($level,$value,$file=null,$line=null,$time=null){
		if($file === null){
			$db = debug_backtrace(false);
			array_shift($db);
			
			foreach($db as $d){
				if(isset($d['file']) && strpos($d['file'],'eval()') === false){
					$file = $d['file'];
					$line = $d['line'];
					break;
				}
			}
		}
		$this->level = $level;
		$this->file = $file;
		$this->line = intval($line);
		$this->time = ($time === null) ? time() : $time;
		$this->value = (is_object($value)) ? 
							(($value instanceof \Exception) ? 
								(string)$value
								: clone($value)
							)
							: $value;
	}
	public function fm_value(){
		if(!is_string($this->value)){
			ob_start();
				var_dump($this->value);
			return trim(ob_get_clean());
		}
		return $this->value;
	}
	public function fm_level(){
		return ($this->level >= 0) ? self::$level_strs[$this->level] : 'trace';
	}
	public function level(){
		return $this->level;
	}
	public function time($format='Y/m/d H:i:s'){
		return (empty($format)) ? $this->time : date($format,$this->time);
	}
	public function file(){
		return $this->file;
	}
	public function line(){
		return $this->line;
	}
	public function value(){
		return $this->value;
	}
	public function __toString(){
		return '['.date('Y-m-d H:i:s',$this->time).']'.'['.sprintf('%s',$this->fm_level()).']'.':['.str_replace(getcwd().DIRECTORY_SEPARATOR,'',$this->file).':'.$this->line.']'.' '.$this->fm_value();
	}
	/**
	 * 格納されたログを出力する
	 */
	public static function flush(){
		if(!empty(self::$logs)){
			/**
			 * boolean 標準出力に表示するか
			 */
			$stdout = \ebi\Conf::get('stdout',false);
			/**
			 * ログを出力するファイルを指定する
			 */
			$file = \ebi\Conf::get('file');
			
			if(!empty($file)){
				if(!is_dir($dir = dirname($file))){
					@mkdir($dir,0777,true);
				}
				@file_put_contents($file,'',FILE_APPEND);
			}
			foreach(self::$logs as $log){
				if(self::cur_level() >= $log->level()){
					$level = $log->fm_level();
					
					if(is_file($file) && is_writable($file)){
						file_put_contents($file,
										((\ebi\Conf::get('nl2str') !== null) ? 
											str_replace(["\r\n","\r","\n"],\ebi\Conf::get('nl2str'),((string)$log)) :
											(string)$log
										).PHP_EOL,
										FILE_APPEND
						);
					}
					if(self::$disp === true && $stdout){
						print(((string)$log).PHP_EOL);
					}
					static::call_class_plugin_funcs($level,$log);
				}
			}
		}
		self::$logs = [];
	}
	/**
	 * 一時的に無効にされた標準出力へのログ出力を有効にする
	 * ログのモードに依存する
	 */
	public static function enable_display(){
		self::debug('log stdout on');
		self::$disp = true;
	}
	
	/**
	 * 標準出力へのログ出力を一時的に無効にする
	 */
	public static function disable_display(){
		self::debug('log stdout off');
		self::$disp = false;
	}
	/**
	 * errorを生成
	 * @param mixed $value 内容
	 */
	public static function error(){
		if(self::cur_level() >= 1){
			foreach(func_get_args() as $value){
				self::$logs[] = new self(1,$value);
			}
		}
	}
	/**
	 * warnを生成
	 * @param mixed $value 内容
	 */
	public static function warn($value){
		if(self::cur_level() >= 2){
			foreach(func_get_args() as $value){
				self::$logs[] = new self(2,$value);
			}
		}
	}
	/**
	 * infoを生成
	 * @param mixed $value 内容
	 */
	public static function info($value){
		if(self::cur_level() >= 3){
			foreach(func_get_args() as $value){
				self::$logs[] = new self(3,$value);
			}
		}
	}
	/**
	 * debugを生成
	 * @param mixed $value 内容
	 */
	public static function debug($value){
		if(self::cur_level() >= 4){
			foreach(func_get_args() as $value){
				self::$logs[] = new self(4,$value);
			}
		}
	}
	/**
	 * traceを生成
	 * @param mixed $value 内容
	 */
	public static function trace($value){
		if(self::cur_level() >= -1){
			foreach(func_get_args() as $value){
				self::$logs[] = new self(-1,$value);
			}
		}
	}
}
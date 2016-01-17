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
 * @var mixed $message 内容
 */
class Log{
	use \ebi\Plugin;
	
	private static $level_strs = ['emergency','alert','critical','error','warning','notice','info','debug'];
	private static $current_level;
	private static $fpout;

	private $level;
	private $time;
	private $file;
	private $line;
	private $message;
		
	private static function cur_level(){
		if(self::$current_level === null){
			/**
			 * エラーレベル ('emergency','alert','critical','error','warning','notice','info','debug')
			 */
			self::$current_level = array_search(\ebi\Conf::get('level','critical'),self::$level_strs);
		}
		return self::$current_level;
	}
	public function __construct($level,$message,$file=null,$line=null,$time=null){
		if(!isset(self::$fpout)){
			/**
			 * ログを出力するファイルを指定する
			 */
			self::$fpout = \ebi\Conf::get('file','');
		
			if(!empty(self::$fpout)){
				if(!is_dir($dir = dirname(self::$fpout))){
					@mkdir($dir,0777,true);
				}
				@file_put_contents(self::$fpout,'',FILE_APPEND);
		
				if(!is_file(self::$fpout)){
					throw new \ebi\exception\InvalidArgumentException('Write failure: '.self::$fpout);
				}
			}
		}
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
		$this->message = (is_object($message)) ? 
		(
			($message instanceof \Exception) ? 
			(string)$message : clone($message)
		) : $message;
		
		if(!empty(self::$fpout)){
			file_put_contents(
				self::$fpout,
				((string)$this).PHP_EOL,
				FILE_APPEND
			);
		}
		/**
		 * ログ出力
		 * @param \ebi\Log $arg1
		 */
		static::call_class_plugin_funcs('log_output',$this);
	}
	public function fm_message(){
		if(!is_string($this->message)){
			ob_start();
				var_dump($this->message);
			return trim(ob_get_clean());
		}
		return $this->message;
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
	public function file_relative(){
		return str_replace(getcwd().DIRECTORY_SEPARATOR,'',$this->file);
	}
	public function line(){
		return $this->line;
	}
	public function message(){
		return $this->message;
	}
	public function __toString(){
		return '['.$this->time().']'.'['.$this->fm_level().']'.':['.$this->file_relative().':'.$this->line().']'.' '.$this->fm_message();
	}
	
	/**
	 * System is unusable.
	 * @param mixed $message
	 */
	public static function emergency(){
		if(self::cur_level() >= 0){
			foreach(func_get_args() as $message){
				new self(0,$message);
			}
		}
	}
	/**
	 * Action must be taken immediately.
	 * @param mixed $message
	 */
	public static function alert(){
		if(self::cur_level() >= 1){
			foreach(func_get_args() as $message){
				new self(1,$message);
			}
		}
	}
	/**
	 * Critical conditions.
	 * @param mixed $message
	 */
	public static function critical(){
		if(self::cur_level() >= 2){
			foreach(func_get_args() as $message){
				new self(2,$message);
			}
		}
	}
	
	/**
	 * Runtime errors that do not require immediate action but should typically
	 * @param mixed $message
	 */
	public static function error(){
		if(self::cur_level() >= 3){
			foreach(func_get_args() as $message){
				new self(3,$message);
			}
		}
	}
	/**
	 * Exceptional occurrences that are not errors.
	 * @param mixed $message
	 */
	public static function warning($message){
		if(self::cur_level() >= 4){
			foreach(func_get_args() as $message){
				new self(4,$message);
			}
		}
	}
	/**
	 * Normal but significant events.
	 * @param mixed $message
	 */
	public static function notice($message){
		if(self::cur_level() >= 5){
			foreach(func_get_args() as $message){
				new self(5,$message);
			}
		}
	}
	/**
	 * Interesting events.
	 * @param mixed $message
	 */
	public static function info($message){
		if(self::cur_level() >= 6){
			foreach(func_get_args() as $message){
				new self(6,$message);
			}
		}
	}
	/**
	 * Detailed debug information.
	 * @param mixed $message
	 */
	public static function debug($message){
		if(self::cur_level() >= 7){
			foreach(func_get_args() as $message){
				new self(7,$message);
			}
		}
	}
	
	/**
	 * traceを生成
	 * @param mixed $message 内容
	 */
	public static function trace($message){
		if(self::cur_level() >= -1){
			foreach(func_get_args() as $message){
				new self(-1,$message);
			}
		}
	}
}
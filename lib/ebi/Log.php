<?php
namespace ebi;

class Log{
	use \ebi\Plugin;
	
	private static $level_str = ['emergency','alert','critical','error','warning','notice','info','debug'];
	private static $current_level;
	private static $filename;

	private $level;
	private $time;
	private $file;
	private $line;
	private $message;
		
	private static function cur_level(): int{
		if(self::$current_level === null){
			/**
			 * @param string $level エラーレベル emergency/alert/critical/error/warning/notice/info/debug
			 */
			self::$current_level = array_search(\ebi\Conf::get('level','critical'),self::$level_str);
		}
		return self::$current_level;
	}

	/**
	 * @param mixed $message
	 */
	public function __construct(string $level, $message, ?string $file=null, ?int $line=null, ?int $time=null){
		if(!isset(self::$filename)){
			/**
			 * @param string $path ログを出力するファイルを指定する
			 */
			self::$filename = \ebi\Conf::get('file','');
		
			if(!empty(self::$filename)){
				if(!is_dir($dir = dirname(self::$filename))){
					@mkdir($dir,0777,true);
				}
				@file_put_contents(self::$filename,'',FILE_APPEND);
		
				if(!is_file(self::$filename)){
					throw new \ebi\exception\InvalidArgumentException('Write failure: '.self::$filename);
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
			(($message instanceof \Exception) || ($message instanceof \Error)) ?
			(string)$message : clone($message)
		) : $message;
		
		if(!empty(self::$filename)){
			file_put_contents(
				self::$filename,
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

	public function fm_message(): string{
		if(!is_string($this->message)){
			ob_start();
				var_dump($this->message);
			return trim(ob_get_clean());
		}
		return $this->message;
	}

	public function fm_level(): string{
		return ($this->level >= 0) ? self::$level_str[$this->level] : 'trace';
	}

	public function level(): int{
		return $this->level;
	}

	public function time(?string $format=null): string{
		if(empty($format)){
			$format = \ebi\Conf::timestamp_format();
		}
		return date($format,$this->time);
	}

	public function file(): string{
		return $this->file;
	}

	public function file_relative(): string{
		return str_replace(getcwd().DIRECTORY_SEPARATOR,'',$this->file);
	}

	public function line(): int{
		return $this->line;
	}

	/**
	 * @return mixed
	 */
	public function message(){
		return $this->message;
	}

	public function __toString(){
		return '['.$this->time().']'.
				'['.$this->fm_level().']'.
				':['.$this->file_relative().
				':'.$this->line().']'.
				' '.$this->fm_message();
	}
	
	/**
	 * System is unusable.
	 */
	public static function emergency(...$args){
		if(self::cur_level() >= 0){
			foreach($args as $message){
				new self(0,$message);
			}
		}
	}

	/**
	 * Action must be taken immediately.
	 */
	public static function alert(...$args){
		if(self::cur_level() >= 1){
			foreach($args as $message){
				new self(1,$message);
			}
		}
	}
	/**
	 * Critical conditions.
	 */
	public static function critical(...$args){
		if(self::cur_level() >= 2){
			foreach($args as $message){
				new self(2,$message);
			}
		}
	}
	
	/**
	 * Runtime errors that do not require immediate action but should typically
	 */
	public static function error(...$args){
		if(self::cur_level() >= 3){
			foreach($args as $message){
				new self(3,$message);
			}
		}
	}

	/**
	 * Exceptional occurrences that are not errors.
	 */
	public static function warning(...$args){
		if(self::cur_level() >= 4){
			foreach($args as $message){
				new self(4,$message);
			}
		}
	}

	/**
	 * Normal but significant events.
	 */
	public static function notice(...$args){
		if(self::cur_level() >= 5){
			foreach($args as $message){
				new self(5,$message);
			}
		}
	}
	/**
	 * Interesting events.
	 */
	public static function info(...$args){
		if(self::cur_level() >= 6){
			foreach($args as $message){
				new self(6,$message);
			}
		}
	}
	/**
	 * Detailed debug information.
	 */
	public static function debug(...$args){
		if(self::cur_level() >= 7){
			foreach($args as $message){
				new self(7,$message);
			}
		}
	}
	
	public static function trace(...$args){
		if(self::cur_level() >= -1){
			foreach($args as $message){
				new self(-1,$message);
			}
		}
	}
}
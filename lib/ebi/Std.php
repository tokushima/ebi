<?php
namespace ebi;

class Std{
	/**
	 * 標準入力からの入力を取得する
	 * @param string $msg 入力待ちのメッセージ
	 * @param string $default 入力が空だった場合のデフォルト値
	 * @param string[] $choice 入力を選択式で求める
	 * @param bool $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 * @param bool $silently 入力を非表示にする(Windowsでは非表示になりません)
	 * @return string
	 */
	public static function read($msg,$default=null,$choice=[],$multiline=false,$silently=false){
		while(true){
			$result = $b = null;
			print($msg.(empty($choice) ? '' : ' ('.implode(' / ',$choice).')').(empty($default) ? '' : ' ['.$default.']').': ');
			if($silently && substr(PHP_OS,0,3) != 'WIN') `tty -s && stty -echo`;
			while(true){
				fscanf(STDIN,'%s',$b);
				if($multiline && $b == '.') break;
				$result .= $b."\n";
				if(!$multiline) break;
			}
			if($silently && substr(PHP_OS,0,3) != 'WIN') `tty -s && stty echo`;
			$result = substr(str_replace(["\r\n","\r","\n"],"\n",$result),0,-1);
			if(empty($result)) $result = $default;
			if(empty($choice) || in_array($result,$choice)) return $result;
		}
	}
	/**
	 * readのエイリアス、入力を非表示にする
	 * Windowsでは非表示になりません
	 * @param string $msg 入力待ちのメッセージ
	 * @param string $default 入力が空だった場合のデフォルト値
	 * @param string[] $choice 入力を選択式で求める
	 * @param bool $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 * @return string
	 */
	public static function silently($msg,$default=null,$choice=[],$multiline=false){
		return self::read($msg,$default,$choice,$multiline,true);
	}
	/**
	 * 色装飾
	 * @param string $value
	 * @param mixed $fmt
	 */
	public static function color($value,$fmt=null){
		if(substr(PHP_OS,0,3) == 'WIN'){
			$value = mb_convert_encoding($value,'UTF-8','SJIS');
		}else if($fmt !== null){
			$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
			$value = "\033[".$fmt.'m'.$value."\033[0m";
		}
		return $value;
	}
	/**
	 * バックスペース
	 * @param int $len
	 */
	public static function backspace($len){
		print("\033[".$len.'D'."\033[0K");
	}
	/**
	 * プリント
	 * @param string $msg
	 * @param string $color ANSI Colors
	 */
	public static function p($msg,$color=0){
		print(self::color($msg,$color));
	}
	
	/**
	 * 色付きでプリント
	 * @param string $msg
	 * @param string $color ANSI Colors
	 */
	public static function println($msg='',$color='0'){
		print(self::color($msg,$color).PHP_EOL);
	}
	/**
	 * Default
	 * @param string $msg
	 */
	public static function println_default($msg){
		self::println($msg);
	}
	/**
	 * White
	 * @param string $msg
	 */
	public static function println_white($msg){
		self::println($msg,'37');
	}
	/**
	 * Blue
	 * @param string $msg
	 */
	public static function println_primary($msg){
		self::println($msg,'34');
	}
	/**
	 * Green
	 * @param string $msg
	 */
	public static function println_success($msg){
		self::println($msg,'32');
	}
	/**
	 * Cyan
	 * @param string $msg
	 */
	public static function println_info($msg){
		self::println($msg,'36');
	}
	/**
	 * Yellow
	 * @param string $msg
	 */
	public static function println_warning($msg){
		self::println($msg,'33');
	}
	/**
	 * Red
	 * @param string $msg
	 */
	public static function println_danger($msg){
		self::println($msg,'31');
	}
}
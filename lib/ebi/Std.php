<?php
namespace ebi;

class Std{
	/**
	 * 標準入力からの入力を取得する
	 * @param $msg 入力待ちのメッセージ
	 * @param $default 入力が空だった場合のデフォルト値
	 * @param $choice 入力を選択式で求める
	 * @param $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 * @param $silently 入力を非表示にする(Windowsでは非表示になりません)
	 * @return string
	 */
	public static function read(string $msg, ?string $default=null, array $choice=[], bool $multiline=false, bool $silently=false): string{
		while(true){
			$result = $b = null;
			print($msg.(empty($choice) ? '' : ' ('.implode(' / ',$choice).')').(empty($default) ? '' : ' ['.$default.']').': ');

			if($silently && substr(PHP_OS,0,3) != 'WIN'){
				`tty -s && stty -echo`;
			}
			while(true){
				fscanf(STDIN,'%s',$b);
				if($multiline && $b == '.'){
					break;
				}
				$result .= $b."\n";

				if(!$multiline){
					break;
				}
			}
			if($silently && substr(PHP_OS,0,3) != 'WIN'){
				`tty -s && stty echo`;
			}
			$result = substr(str_replace(["\r\n","\r","\n"],"\n",$result),0,-1);

			if(empty($result)){
				$result = $default;
			}
			if(empty($choice) || in_array($result,$choice)){
				return $result;
			}
		}
	}

	/**
	 * readのエイリアス、入力を非表示にする
	 * Windowsでは非表示になりません
	 * @param $msg 入力待ちのメッセージ
	 * @param $default 入力が空だった場合のデフォルト値
	 * @param $choice 入力を選択式で求める
	 * @param $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 */
	public static function silently(string $msg, ?string $default=null, array $choice=[], bool $multiline=false): string{
		return self::read($msg,$default,$choice,$multiline,true);
	}

	/**
	 * 色装飾
	 */
	public static function color(string $value, bool|string $ansi_color=null): string{
		if(substr(PHP_OS,0,3) == 'WIN'){
			$value = mb_convert_encoding($value,'UTF-8','SJIS');
		}else if($ansi_color !== null){
			$fmt = ($ansi_color === true) ? '1;34' : (($ansi_color === false) ? '1;31' : $ansi_color);
			$value = "\033[".$fmt.'m'.$value."\033[0m";
		}
		return $value;
	}
	/**
	 * バックスペース
	 */
	public static function backspace(int $len): void{
		print("\033[".$len.'D'."\033[0K");
	}
	/**
	 * プリント
	 */
	public static function p(string $msg, bool|string $ansi_color=null): void{
		print(self::color($msg, $ansi_color));
	}
	
	/**
	 * 色付きでプリント
	 */
	public static function println(string $msg='', bool|string $ansi_color='0'): void{
		print(self::color($msg,$ansi_color).PHP_EOL);
	}
	/**
	 * Default
	 */
	public static function println_default(string $msg): void{
		self::println($msg);
	}
	/**
	 * White
	 */
	public static function println_white(string $msg): void{
		self::println($msg,'37');
	}
	/**
	 * Blue
	 */
	public static function println_primary(string $msg): void{
		self::println($msg,'34');
	}
	/**
	 * Green
	 */
	public static function println_success(string $msg): void{
		self::println($msg,'32');
	}
	/**
	 * Cyan
	 */
	public static function println_info(string $msg): void{
		self::println($msg,'36');
	}
	/**
	 * Yellow
	 */
	public static function println_warning(string $msg): void{
		self::println($msg,'33');
	}
	/**
	 * Red
	 */
	public static function println_danger(string $msg): void{
		self::println($msg,'31');
	}
}
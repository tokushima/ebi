<?php
namespace ebi;

class FlowHelper{
	private $name;
	private $req;
	
	public function __construct(?string $name=null, ?\ebi\flow\Request $obj=null){
		$this->name = $name;
		$this->req = $obj;
	}

	/**
	 * handlerのマップ名を呼び出しているURLを生成する
	 */
	public function map_url(string $map_name, ...$args): string{
		if(!isset(\ebi\Flow::url_pattern()[$map_name])){
			if(isset(\ebi\Flow::url_pattern()[$map_name.'/index'])){
				$map_name = $map_name.'/index';
			}
		}
		if(isset(\ebi\Flow::url_pattern()[$map_name][sizeof($args)])){
			return vsprintf(\ebi\Flow::url_pattern()[$map_name][sizeof($args)],$args);
		}
	}

	public function is_post(): bool{
		return (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * ログイン済みか
	 */
	public function is_user_logged_in(): bool{
		return (isset($this->req)) ? $this->req->is_user_logged_in() : false;
	}

	/**
	 * ログインユーザを返す
	 * @return mixed
	 */
	public function user(){
		return (isset($this->req)) ? $this->req->user() : null;
	}
	
	/**
	 * リクエストに含まれているか
	 */
	public function is_vars(string $name): bool{
		return (isset($this->req)) ? $this->req->is_vars($name) : false;
	}
	
	/**
	 * handlerでpackageを呼び出してる場合にメソッド名でURLを生成する
	 */
	public function package_method_url(string $name, ...$args): string{
		if(isset(\ebi\Flow::selected_class_pattern()[$name][sizeof($args)])){
			return vsprintf(\ebi\Flow::selected_class_pattern()[$name][sizeof($args)]['format'],$args);
		}
	}
	/**
	 * handlerでpackageを呼び出してる場合にメソッド名が実行されている場合に$trueを、違うなら$falseを返す
	 * @param $name 対象のメソッド名
	 * @param $true 一致した場合に返す文字列
	 * @param $false 一致しなかった場合に返す文字列
	 */
	public function match_package_method_switch(string $name, string $true='on', string $false=''): string{
		$method = explode('/',$this->name(),2);
		return ($name == (isset($method[1]) ? $method[1] : $method[0])) ? $true : $false;
	}
	/**
	 * 現在のURLを返す
	 */
	public function current_url(): string{
		return \ebi\Request::current_url();
	}	
	/**
	 * マッチしたパターン（名）を返す
	 */
	public function name(): ?string{
		return $this->name;
	}
	/**
	 * マッチしたパターンと$patternが同じなら$trueを、違うなら$falseを返す
	 */
	public function match_pattern_switch(string $pattern, string $true='on', string $false=''): string{
		return ($this->name() == $pattern) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで前方一致なら$trueを、違うなら$falseを返す
	 */
	public function startswith_pattern_switch(string $pattern, string $true='on', string $false=''): string{
		return (strpos($this->name(),$pattern) === 0) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで後方一致なら$trueを、違うなら$falseを返す
	 */
	public function endswith_pattern_switch(string $pattern, string $true='on', string $false=''): string{
		return (strrpos($this->name(),$pattern) === (strlen($this->name())-strlen($pattern))) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで部分一致なら$trueを、違うなら$falseを返す
	 */
	public function contains_pattern_switch(string $pattern, string $true='on', string $false=''): string{
		return (strrpos($this->name(),$pattern) !== false) ? $true : $false;
	}	
	
	/**
	 * 真偽値により$trueまたは$falseを返す
	 * @param mixed $cond
	 */
	public function cond_switch($cond, string $true='on', string $false=''): string{
		return ($cond !== false && !empty($cond)) ? $true : $false;
	}
	/**
	 * $a == $bが真なら$true偽なら$falseを返す
	 * @param mixed $a
	 * @param mixed $b
	 */
	public function cond_eq_switch($a, $b, string $true='on', string $false=''): string{
		return ($a == $b) ? $true : $false;
	}
	/**
	 * アプリケーションのメディアのURLを返す
	 */
	public function media(?string $path=null): string{
		return \ebi\Util::path_absolute(\ebi\Flow::media_url(), $path);
	}
	/**
	 * アプリケーションのURLを返す
	 */
	public function app_url(?string $path=null): string{
		return \ebi\Util::path_absolute(\ebi\Flow::app_url(), $path);
	}
	/**
	 * ゼロを桁数分前に埋める
	 */
	public function zerofill(int $int, int $dig=0): string{
		return sprintf('%0'.$dig.'d', $int);
	}
	/**
	 * 数字を千位毎にグループ化してフォーマットする
	 */
	public function number_format(float $number, int $dec=0): string{
		return number_format($number, $dec, '.', ',');
	}
	/**
	 * フォーマットした日付を返す
	 * @see http://jp2.php.net/manual/ja/function.date.php
	 */
	public function date_format(?string $format=null, ?int $time=null): string{
		if(empty($format)){
			$format = \ebi\Conf::timestamp_format();
		}
		if(empty($time)){
			$time = time();
		}
		return date($format, $time);
	}
	/**
	 * タイムスタンプを返す
	 */
	public function time(int $add_sec=0, ?int $time=null){
		if(empty($time)){
			$time = time();
		}
		return ($time + $add_sec);
	}
	/**
	 * 改行を削除(置換)する
	 */
	public function one_liner(string $value, $replace=" "){
		return str_replace(["\r\n","\r","\n","<br>","<br />"], $replace, $value);
	}
	/**
	 * 文字列を丸める
	 * @deprecated
	 */
	public function trim_width(string $str, int $width, string $postfix=''){
		return \ebi\Util::trim_width($str, $width, $postfix);
	}
	/**
	 * 何もしない
	 * @param mixed $var そのまま返す値
	 * @return mixed
	 */
	public function noop($var){
		return $var;
	}
	/**
	 * HTMLエスケープされた文字列を返す
	 */
	public function html(string $value, int $length=0, int $lines=0, ?string $postfix=null, bool $nl2br=true): string{
		$value = str_replace(["\r\n","\r","\n"],PHP_EOL,$value);
		if($length > 0){
			$det = mb_detect_encoding($value);
			$value = mb_substr($value,0,$length,$det);

			if(mb_strlen($value,$det) == $length){
				$value = $value.$postfix;
				$postfix = '';
			}
		}
		if($lines > 0){
			$l = explode(PHP_EOL,$value);
			$value = implode(PHP_EOL,array_slice($l,0,$lines)).((sizeof($l) > $lines) ? $postfix : null);
		}
		$value = str_replace(["<",">","'","\""],["&lt;","&gt;","&#039;","&quot;"],$value);
		return ($nl2br) ? nl2br($value,true) : $value;
	}
	/**
	 * 特殊文字を HTML エンティティに変換する
	 */
	public function htmlspecialchars(string $val): string{
		return htmlspecialchars($val);
	}
	/**
	 * 改行文字の前に HTML の改行タグを挿入する
	 */
	public function nl2br(string $value): string{
		return nl2br($value,true);
	}
	/**
	 * 全てのタグを削除した文字列を返す
	 */
	public function text(string $value, int $length=0, int $lines=0, ?string $postfix=null): string{
		return self::html(preg_replace("/<.+?>/","",$value),$length,$lines,$postfix);
	}
	/**
	 * Json文字列にして返す
	 * @param mixed $value
	 */
	public function json($value): string{
		return json_encode($value);
	}
	/**
	 * クエリ文字列を生成する
	 */
	public function build_url(): string{
		$args = func_get_args();
		$list = [];
		$url = array_shift($args);
		
		if(is_array($url)){
			$list = $url;
			$url = '';
		}
		foreach($args as $v){
			if(is_array($v)){
				$list = array_merge($list,$v);
			}else if(is_string($v)){
				$url = $url.$v;
			}
		}
		$query = http_build_query($list);
		
		return $url.((strpos($url,'?') === false) ? '?' : '&').$query;
	}
	
	/**
	 * URL エンコードを行う
	 */
	public function urlencode(string $str): string{
		return rawurlencode($str);
	}
	
	/**
	 * !
	 * @param mixed $a
	 */
	public function not($a): bool{
		return !($a);
	}
	/**
	 * ==
	 * @param mixed $a
	 * @param mixed $b
	 */
	public function eq($a,$b): bool{
		$bool = ($a == $b);
		
		if($bool && ($a === '' || $b === '')){
			return ($a === $b);
		}
		return $bool;
	}
	/**
	 * !=
	 * @param mixed $a
	 * @param mixed $b
	 */
	public function neq($a,$b): bool{
		return !($this->eq($a,$b));
	}
	/**
	 * aがbより小さい
	 */
	public function lt(float $a, float $b): bool{
		return ($a < $b);
	}
	/**
	 * aがbより小さいか等しい
	 */
	public function lte(float $a, float $b): bool{
		return ($a <= $b);
	}
	/**
	 * aがbより大きい
	 */
	public function gt(float $a, float $b): bool{
		return ($a > $b);
	}
	/**
	 * aがbより大きいか等しい
	 */
	public function gte(float $a, float $b): bool{
		return ($a >= $b);
	}
	/**
	 * 剰余
	 */
	public function remainder(float $a, float $b): float{
		return ($a % $b);
	}
	/**
	 * ある範囲の整数を有する配列を作成します。
	 * @param mixed  $start
	 * @param mixed  $end
	 * @return mixed
	 */
	public function range($start, $end, float $step=1){
		$array = range($start,$end,$step);
		return array_combine($array,$array);
	}
	/**
	 * 配列を逆順にして返す
	 */
	public function reverse(array $array): array{
		if(is_object($array) && ($array instanceof \Traversable)){
			$list = [];
			foreach($array as $v) $list[] = $v;
			$array = $list;
		}
		if(is_array($array)){
			rsort($array);
			return $array;
		}
		return [];
	}
	/**
	 * FLowで例外が発生しているか
	 */
	public function has_invalid(?string $group=null): bool{
		return \ebi\FlowInvalid::has($group);
	}
	/**
	 * 引数が空ではないか
	 * 一つまたは複数の値で一つでも空でなければtrue
	 * @param mixed $arg
	 */
	public function has($arg): bool{
		foreach(func_get_args() as $arg){
			if(!empty($arg)){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 対象がtrue / 1 / 'true' ならtrue
	 * @param  mixed $bool
	 */
	public function is_true($bool): bool{
		return call_user_func_array([\ebi\Util::class,'is_true'],func_get_args());
	}
		
	/**
	 * varがarg1,arg2,arg3,,,に含まれるか
	 * @param mixed $var
	 * @param mixed $arg1
	 */
	public function in($var, $arg1): bool{
		$args = func_get_args();
		array_shift($args);

		foreach($args as $arg){
			if(is_array($arg)){
				if(in_array($var, $arg)){
					return true;
				}
			}else if($var == $arg){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * オブジェクトのクラス名を返す
	 */
	public function get_class(object $obj): string{
		if(is_object($obj)){
			return get_class($obj);
		}
		return '';
	}
	
	/**
	 * size
	 * @param mixed $var
	 */
	public function sizeof($var): int{
		return is_array($var) ? sizeof($var) : 1;
	}
	
	/**
	 * 加算
	 */
	public function sum(float $a, float $b): float{
		return $a + $b;
	}
}
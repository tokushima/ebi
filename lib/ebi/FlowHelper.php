<?php
namespace ebi;
/**
 * テンプレートで利用するヘルパ
 * @author tokushima
 */
class FlowHelper{
	private $name;
	
	private $is_user_logged_in = false;
	private $user;
	
	public function __construct($name=null,$obj=null){
		$this->name = $name;
		
		if($obj instanceof \ebi\flow\Request){
			$this->is_user_logged_in = $obj->is_user_logged_in();
			$this->user = $obj->user();
		}
	}
	/**
	 * handlerのマップ名を呼び出しているURLを生成する
	 * 引数を与える事も可能
	 * @param string $name マップ名
	 * @return string
	 */
	public function map_url($name){
		$args = func_get_args();
		array_shift($args);
	
		if(!isset(\ebi\Flow::url_pattern()[$name])){
			if(isset(\ebi\Flow::url_pattern()[$name.'/index'])){
				$name = $name.'/index';
			}
		}
		if(isset(\ebi\Flow::url_pattern()[$name][sizeof($args)])){
			return vsprintf(\ebi\Flow::url_pattern()[$name][sizeof($args)],$args);
		}
	}
	/**
	 * POSTされたか
	 * @return boolean
	 */
	public function is_post(){
		return  (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function is_user_logged_in(){
		return $this->is_user_logged_in;
	}
	/**
	 * ログインユーザを返す
	 * @return mixed
	 */
	public function user(){
		return $this->user;
	}
	/**
	 * handlerでpackageを呼び出してる場合にメソッド名でURLを生成する
	 * 引数を与える事も可能
	 * @param string $name メソッド名
	 * @return string
	 */
	public function package_method_url($name){
		$args = func_get_args();
		array_shift($args);
		
		if(isset(\ebi\Flow::selected_class_pattern()[$name][sizeof($args)])){
			return vsprintf(\ebi\Flow::selected_class_pattern()[$name][sizeof($args)]['format'],$args);
		}
	}
	/**
	 * handlerでpackageを呼び出してる場合にメソッド名が実行されている場合に$trueを、違うなら$falseを返す
	 * @param string $name 対象のメソッド名
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 */
	public function match_package_method_switch($name,$true='on',$false=''){
		$method = explode('/',$this->name(),2);
		return ($name == (isset($method[1]) ? $method[1] : $method[0])) ? $true : $false;
	}	
	/**
	 * 現在のURLを返す
	 * @return string
	 */
	public function current_url(){
		return \ebi\Request::current_url();
	}	
	/**
	 * マッチしたパターン（名）を返す
	 * @return string
	 */
	public function name(){
		return $this->name;
	}
	/**
	 * マッチしたパターンと$patternが同じなら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function match_pattern_switch($pattern,$true='on',$false=''){
		return ($this->name() == $pattern) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで前方一致なら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function startswith_pattern_switch($pattern,$true='on',$false=''){
		return (strpos($this->name(),$pattern) === 0) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで後方一致なら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function endswith_pattern_switch($pattern,$true='on',$false=''){
		return (strrpos($this->name(),$pattern) === (strlen($this->name())-strlen($pattern))) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで部分一致なら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function contains_pattern_switch($pattern,$true='on',$false=''){
		return (strrpos($this->name(),$pattern) !== false) ? $true : $false;
	}	
	
	/**
	 * 真偽値により$trueまたは$falseを返す
	 * @param boolean $cond 真偽値
	 * @param string $true 真の場合に返す文字列
	 * @param string $false 偽の場合に返す文字列
	 * @return string
	 */
	public function cond_switch($cond,$true='on',$false=''){
		return ($cond !== false && !empty($cond)) ? $true : $false;
	}
	/**
	 * $a == $bが真なら$true偽なら$falseを返す
	 * @param boolean $cond 真偽値
	 * @param string $true 真の場合に返す文字列
	 * @param string $false 偽の場合に返す文字列
	 * @return string
	 */
	public function cond_eq_switch($a,$b,$true='on',$false=''){
		return ($a == $b) ? $true : $false;
	}
	/**
	 * アプリケーションのメディアのURLを返す
	 * @param string $url ベースのURLに続く相対パス
	 * @return string
	 */
	public function media($url=null){
		return \ebi\Util::path_absolute(\ebi\Flow::media_url(),$url);
	}
	/**
	 * アプリケーションのURLを返す
	 * @param string $url ベースのURLに続く相対パス
	 * @retunr string
	 */
	public function app_url($url=null){
		return \ebi\Util::path_absolute(\ebi\Flow::app_url(),$url);
	}
	/**
	 * ゼロを桁数分前に埋める
	 * @param integer $int 対象の値
	 * @param integer $dig 0埋めする桁数
	 * @return string
	 */
	public function zerofill($int,$dig=0){
		return sprintf('%0'.$dig.'d',$int);
	}
	/**
	 * 数字を千位毎にグループ化してフォーマットする
	 * @param number $number 対象の値
	 * @param integer $dec 小数点以下の桁数
	 * @return string
	 */
	public function number_format($number,$dec=0){
		return number_format($number,$dec,'.',',');
	}
	/**
	 * フォーマットした日付を返す
	 * @param integer $value 時間
	 * @param string $format フォーマット文字列 ( http://jp2.php.net/manual/ja/function.date.php )
	 * @return string
	 */
	public function date_format($format=null,$value=null){
		if(empty($format)){
			$format = \ebi\Conf::timestamp_format();
		}
		if(empty($value)){
			$value = time();
		}
		return date($format,$value);
	}
	/**
	 * タイムスタンプを返す
	 * @param number $add 加算する秒数
	 * @return number
	 */
	public function time($add=0,$time=null){
		if(empty($time)){
			$time = time();
		}
		return ($time + $add);
	}
	/**
	 * 改行を削除(置換)する
	 *
	 * @param string $value 対象の文字列
	 * @param string $glue 置換後の文字列
	 * @return string
	 */
	public function one_liner($value,$glue=" "){
		return str_replace(["\r\n","\r","\n","<br>","<br />"],$glue,$value);
	}
	/**
	 * 文字列を丸める
	 * @param string $str 対象の文字列
	 * @param integer $width 指定の幅
	 * @param string $postfix 文字列がまるめられた場合に末尾に接続される文字列
	 * @return string
	 */
	public function trim_width($str,$width,$postfix=''){
		return \ebi\Util::trim_width($str,$width,$postfix);
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
	 * @param string $value 対象の文字列
	 * @param integer $length 取得する文字列の最大長
	 * @param integer $lines 取得する文字列の最大行数
	 * @param string $postfix 文字列が最大長または最大行数を超えた場合に末尾に接続される文字列
	 * @param boolean $nl2br 改行コードを<br />にするか
	 * @return string
	 */
	public function html($value,$length=0,$lines=0,$postfix=null,$nl2br=true){
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
	 * @param string $val
	 * @return string
	 */
	public function htmlspecialchars($val){
		return htmlspecialchars($val);
	}
	/**
	 * 改行文字の前に HTML の改行タグを挿入する
	 * @param string $value
	 * @return string
	 */
	public function nl2br($value){
		return nl2br($value,true);
	}
	/**
	 * 全てのタグを削除した文字列を返す
	 * @param string $value 対象の文字列
	 * @param integer $length 取得する文字列の最大長
	 * @param integer $lines 取得する文字列の最大行数
	 * @param string $postfix 文字列が最大長または最大行数を超えた場合に末尾に接続される文字列
	 * @return string
	 */
	public function text($value,$length=0,$lines=0,$postfix=null){
		return self::html(preg_replace("/<.+?>/","",$value),$length,$lines,$postfix);
	}
	/**
	 * Json文字列にして返す
	 * @param mixed $value
	 * @return string
	 */
	public function json($value){
		return json_encode($value);
	}
	/**
	 * クエリ文字列を生成する
	 * @return string
	 */
	public function build_url(){
		$args = func_get_args();
		$list = [];
		$url = array_shift($args);
		
		if(is_array($url)){
			$list = $url;
			$url = '';
		}
		foreach($args as $k => $v){
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
	 * !=
	 * @param mixed $a
	 * @return boolean 
	 */
	public function not($a){
		return !($a);
	}
	/**
	 * ==
	 * @param mixed $a
	 * @param mixed $b
	 * @return boolean
	 */
	public function eq($a,$b){
		return ($a == $b);
	}
	/**
	 * !=
	 * @param mixed $a
	 * @param mixed $b
	 * @return boolean
	 */
	public function neq($a,$b){
		return ($a != $b);
	}
	/**
	 * aがbより小さい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function lt($a,$b){
		return ($a < $b);
	}
	/**
	 * aがbより小さいか等しい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function lte($a,$b){
		return ($a <= $b);
	}
	/**
	 * aがbより大きい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function gt($a,$b){
		return ($a > $b);
	}
	/**
	 * aがbより大きいか等しい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function gte($a,$b){
		return ($a >= $b);
	}
	/**
	 * 剰余
	 * @param number $a
	 * @param number $b
	 * @return number
	 */
	public function remainder($a,$b){
		return ($a % $b);
	}
	/**
	 * ある範囲の整数を有する配列を作成します。
	 * @param mixed  $start
	 * @param mixed  $end
	 * @param number $step
	 * @return multitype:
	 */
	public function range($start,$end,$step=1){
		$array = range($start,$end,$step);
		return array_combine($array,$array);
	}
	/**
	 * 配列を逆順にして返す
	 * @param mixed $array
	 * @return array
	 */
	public function reverse($array){
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
	 * @param string $group
	 * @return boolean
	 */
	public function has_invalid($group=null){
		return \ebi\FlowInvalid::has($group);
	}
	/**
	 * 引数が空ではないか
	 * 一つまたは複数の値で一つでも空でなければtrue
	 * @param mixed $arg
	 * @return boolean
	 */
	public function has($arg){
		foreach(func_get_args() as $arg){
			if(!empty($arg)){
				return true;
			}
		}
		return false;
	}
	/**
	 * オブジェクトのクラス名を返す
	 * @param object $obj
	 * @return string
	 */
	public function get_class($obj){
		if(is_object($obj)){
			return get_class($obj);
		}
		return '';
	}
}
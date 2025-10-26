<?php
namespace ebi;

/**
 * ユーティリティ群
 */
class Util{
	/**
	 * ファイルから取得する
	 */
	public static function file_read(string $filename): string{
		if(!is_readable($filename) || !is_file($filename)){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
		return file_get_contents($filename);
	}
	/**
	 * CSVファイルから１行ずつ配列で取得する
	 */
	public static function file_read_csv(string $filename, string $separator=','): \Generator{
		try{
			$file = new \SplFileObject($filename);
			$file->setCsvControl($separator, '"', "\\");
			$file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
			
			foreach($file as $line){
				yield $line;
			}
		}catch(\RuntimeException $e){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
	}
	/**
	 * JSONファイルを読み込む
	 * @return mixed
	 */
	public static function file_read_json(string $filename){
		return \ebi\Json::decode(self::file_read($filename));
	}
	/**
	 * CSVファイルとして配列を書き出す
	 * @param mixed $file \SplFileObject|string
	 */
	public static function file_append_csv($file, array $arr=[]): \SplFileObject{
		if(is_string($file)){
			if(!is_file($file)){
				self::file_write($file);
			}
			$file = new \SplFileObject($file,'a');
		}
		if(!($file instanceof \SplFileObject)){
			throw new \ebi\exception\AccessDeniedException();
		}
		if(!empty($arr)){
			$file->fputcsv($arr, ',', '"', "\\");
		}
		return $file;
	}
	/**
	 * JSONを書き出す
	 */
	public static function file_write_json(string $filename, array $vars, bool $format=false): void{
		self::file_write($filename,\ebi\Json::encode($vars,$format));
	}
	/**
	 * ファイルに書き出す
	 */
	public static function file_write(string $filename, string $src='', bool $lock=true): void{
		if(empty($filename)){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
		$b = is_file($filename);
		self::mkdir(dirname($filename));
		
		if(false === file_put_contents($filename,(string)$src,($lock ? LOCK_EX : 0))){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
		if(!$b){
			chmod($filename,0666);
		}
	}	
	/**
	 * ファイルに追記する
	 */
	public static function file_append(string $filename, string $src='', bool $lock=true): void{
		self::mkdir(dirname($filename));
		
		if(false === file_put_contents($filename,(string)$src,FILE_APPEND|(($lock) ? LOCK_EX : 0))){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
	}
	/**
	 * フォルダを作成する
	 * @param mixed $permission oct
	 */
	public static function mkdir(string $dir_path, $permission=0775): bool{
		$bool = true;
		if(!is_dir($dir_path)){
			try{
				$list = explode('/',str_replace('\\','/',$dir_path));
				$dir = '';
				foreach($list as $d){
					$dir = $dir.$d.'/';
					if(!is_dir($dir)){
						$bool = mkdir($dir);
						if(!$bool) return $bool;
						chmod($dir,$permission);
					}
				}
			}catch(\ErrorException $e){
				throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$dir_path));
			}
		}
		return $bool;
	}
	/**
	 * 移動
	 */
	public static function mv(string $source, string $dest): bool{
		if(is_file($source) || is_dir($source)){
			self::mkdir(dirname($dest));
			return rename($source,$dest);
		}
		throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$source));
	}
	/**
	 * 削除
	 * $sourceがフォルダで$inc_selfがfalseの場合は$sourceフォルダ以下のみ削除
	 */
	public static function rm(string $source, bool $inc_self=true): void{
		if(is_dir($source)){
			$source = realpath($source);
			$dir = [];
			
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($source,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::UNIX_PATHS)
			);
			foreach($it as $f){
				if($f->getFilename() == '.'){
					if($inc_self || $source != $f->getPath()){
						$dir[$f->getPath()] = 1;
					}
				}else if($f->getFilename() != '..'){
					unlink($f->getPathname());
				}
			}
			krsort($dir);
			
			foreach(array_keys($dir) as $d){
				rmdir($d);
			}
		}else if(is_file($source)){
			unlink($source);
		}
	}
	/**
	 * コピー
	 * $sourceがフォルダの場合はそれ以下もコピーする
	 */
	public static function copy(string $source, string $dest): void{
		if(is_dir($source)){
			$source = realpath($source);
			$len = strlen($source);
			
			self::mkdir($dest);
			$dest = realpath($dest);
			
			foreach(self::ls($source,true) as $f){
				$destp = $dest.'/'.substr($f->getPathname(),$len);
				self::mkdir(dirname($destp));
				copy($f->getPathname(),$destp);
			}
			return;
		}else if(is_file($source)){
			self::mkdir(dirname($dest));
			copy($source,$dest);
			return;
		}
		throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$source));
	}
	/**
	 * ディレクトリ名の一覧
	 */
	public static function ls_directory(string $directory): \Generator{
		if(is_dir($directory)){
			foreach(scandir($directory) as $f){
				if(is_dir($directory.'/'.$f) && $f != '.' && $f != '..'){
					yield $f;
				}
			}
		}
	}
	/**
	 * ディレクトリ内のイテレータ
	 * @param $directory  検索対象のファイルパス
	 * @param $recursive 階層を潜って取得するか
	 * @param $pattern 検索するパターンを表す文字列
	 */
	public static function ls(string $directory, bool $recursive=false, ?string $pattern=null): \Iterator{
		$directory = self::parse_filename($directory);
		
		if(is_file($directory)){
			$directory = dirname($directory);
		}
		if(is_dir($directory)){
			$it = new \RecursiveDirectoryIterator($directory,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS);
			if($recursive){
				$it = new \RecursiveIteratorIterator($it);
			}
			if(!empty($pattern)){
				$it = new \RegexIterator($it,$pattern);
			}			
			return $it;
		}
		throw new \ebi\exception\InvalidArgumentException(sprintf('permission denied `%s`',$directory));
	}
	private static function parse_filename(string $filename){
		$filename = preg_replace("/[\/]+/",'/',str_replace("\\",'/',trim($filename)));
		return (substr($filename,-1) == '/') ? substr($filename,0,-1) : $filename;
	}
	
	/**
	 * 絶対パスを返す
	 */
	public static function path_absolute(?string $a, ?string $b): ?string{
		if($b === '' || $b === null){
			return $a;
		}
		if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)){
			return $b;
		}
		
		$h = [];
		if(preg_match("/^[\w]+\:\/\/[^\/]+/",$a,$h)){
			$a = preg_replace("/^(.+?)[".(($b[0] === '#') ? '#' : "#\?")."].*$/","\\1",$a);
			if($b[0] == '#' || $b[0] == '?') return $a.$b;
			if(substr($a,-1) != '/') $b = (substr($b,0,2) == './') ? '.'.$b : (($b[0] != '.' && $b[0] != '/') ? '../'.$b : $b);
			if($b[0] == '/' && isset($h[0])) return $h[0].$b;
		}else if($b[0] == '/'){
			return $b;
		}
		$p = [
			['://','/./','//'],
			['#R#','/','/'],
			["/^\/(.+)$/","/^(\w):\/(.+)$/"],
			["#T#\\1","\\1#W#\\2",''],
			['#R#','#W#','#T#'],
			['://',':/','/']
		];
		$a = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$a));
		$b = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$b));
		$d = $t = $r = '';
		if(strpos($a,'#R#')){
			[$r] = explode('/',$a,2);
			$a = substr($a,strlen($r));
			$b = str_replace('#T#','',$b);
		}
		$al = preg_split("/\//",$a,-1,PREG_SPLIT_NO_EMPTY);
		$bl = preg_split("/\//",$b,-1,PREG_SPLIT_NO_EMPTY);

		for($i=0;$i<sizeof($al)-substr_count($b,'../');$i++){
			if($al[$i] != '.' && $al[$i] != '..') $d .= $al[$i].'/';
		}
		for($i=0;$i<sizeof($bl);$i++){
			if($bl[$i] != '.' && $bl[$i] != '..') $t .= '/'.$bl[$i];
		}
		$t = (!empty($d)) ? substr($t,1) : $t;
		$d = (!empty($d) && $d[0] != '/' && substr($d,0,3) != '#T#' && !strpos($d,'#W#')) ? '/'.$d : $d;
		return str_replace($p[4],$p[5],$r.$d.$t);
	}
	/**
	 * パスの前後にスラッシュを追加／削除を行う
	 */
	public static function path_slash(?string $path, ?bool $prefix, ?bool $postfix=null): ?string{
		if($path == '/'){
			return ($postfix === true) ? '/' : '';
		}
		if(!empty($path)){
			if($prefix === true && $path[0] != '/'){
				$path = '/'.$path;
			}else if($prefix === false && $path[0] == '/'){
				$path = substr($path,1);
			}
			if($postfix === true && substr($path,-1) != '/'){
				$path = $path.'/';
			}else if($postfix === false && substr($path,-1) == '/'){
				$path = substr($path,0,-1);
			}
		}
		return $path;
	}
	/**
	 * ヒアドキュメントのようなテキストを生成する
	 * １行目のインデントに合わせてインデントが消去される
	 * @param string $text 対象の文字列
	 */
	public static function plain_text(string $text): string{
		if(!empty($text)){
			$text = str_replace(["\r\n","\r","\n"],"\n",$text);
			
			$lines = explode("\n",$text);
			if(sizeof($lines) > 2){
				if(trim($lines[0]) == ''){
					array_shift($lines);
				}
				if(trim($lines[sizeof($lines)-1]) == ''){
					array_pop($lines);
				}
				
				$match = [];
				return preg_match("/^([\040\t]+)/",$lines[0],$match) ? 
						preg_replace('/^'.$match[1].'/m','',implode("\n",$lines)) : 
						implode("\n",$lines);
			}
		}
		return $text;
	}
	/**
	 * フォーマット文字列 $str に基づき生成された文字列を返します。
	 * フォーマット中に現れた置換文字列{1},{2}...を置換する
	 */
	public static function fstring(string $str, ...$args): string{
		$match = [];
		
		if(preg_match_all("/\{([\d]+)\}/",$str,$match)){
			if(is_array($args[0])){
				$args = $args[0];
			}
			foreach($match[1] as $key => $value){
				$i = ((int)$value) - 1;
				$str = str_replace($match[0][$key], ($args[$i] ?? ''), $str);
			}
		}
		return $str;
	}
	/**
	 * 日付に加減する
	 * 
	 * @param $datetime +2 month, -7 day, yesterday, today, tomorrow, first, last
	 * @param mixed $date
	 * @see http://jp2.php.net/manual/ja/datetime.formats.relative.php
	 */
	public static function add_date(string $datetime, $date=null): int{
		if(!isset($date)){
			$t = time();
		}else if(ctype_digit((string)$date) || (substr($date,0,1) == '-' && ctype_digit(substr($date,1)))){
			$t = (int)$date;
		}else{
			$t = strtotime($date);
		}
		if($datetime == 'first'){
			$datetime = 'first day of 00:00:00';
		}else if($datetime == 'last'){
			$datetime = 'last day of 23:59:59';
		}
		
		$rtn = strtotime($datetime,$t);
		if($rtn === false){
			throw new \ebi\exception\InvalidArgumentException(sprintf('invalid date and time formats `%s`',$datetime));
		}
		return $rtn;
	}

	/**
	 * 次回の営業日のタイムスタンプ
	 * holidays: 休日の日付文字列(YYYYMMDD)の配列
	 * regular_holiday: 休日の曜日番号
	 */
	public static function next_business_day(int $base, int $days, array $holidays=[], $regular_holiday=[0,6]): int{
		$d = $base;

		for($i=0;$i<$days;){
			$d += 86400;

			if(!in_array(date('w', $d), $regular_holiday) && !in_array(date('Ymd', $d), $holidays)){
				$i++;
			}
		}
		return $d;
	}
	
	/**
	 * 文字列を丸める
	 * @deprecated
	 */
	public static function trim_width(string $str, int $width, string $postfix=''): string{
		$rtn = "";
		$cnt = 0;
		$len = mb_strlen($str);
		for($i=0;$i<$len;$i++){
			$c = mb_substr($str,$i,1);
			$cnt += (mb_strwidth($c) > 1) ? 2 : 1;
			if($width < $cnt) break;
			$rtn .= $c;
		}
		if($len > mb_strlen($rtn)) $rtn .= $postfix;
		return $rtn;
	}
	/**
	 * クラス名
	 */
	public static function get_class_name(string $class_name): string{
		if(class_exists($class_name)){
			$r = new \ReflectionClass($class_name);
			return $r->getName();
		}
		throw new \ebi\exception\ClassNotFoundException('Class `'.$class_name.'` not found');
	}
	
	/**
	 * 対象がtrue / 1 / 'true' ならtrue
	 * @param  mixed $bool
	 */
	public static function is_true($bool): bool{
		foreach(func_get_args() as $arg){
			if(!($arg === true || $arg === 1 || (is_string($arg) && strtolower($arg) === 'true'))){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 値をプリミティブ型で返す
	 * @param mixed $value
	 * @return mixed
	 */
	public static function to_primitive($value){
		switch(gettype($value)){
			case 'array':
				$list = [];
				foreach($value as $k => $v){
					$list[$k] = self::to_primitive($v);
				}
				return $list;
			case 'object':
				$list = [];
				foreach((($value instanceof \Traversable) ? $value : get_object_vars($value)) as $k => $v){
					$list[$k] = self::to_primitive($v);
				}
				return $list;
			default:
		}
		return $value;
	}
	/**
	 * 与えられたクラスのtraitを全て返します
	 */
	public static function get_class_traits(string $class): array{
		$ref = new \ReflectionClass($class);
		$traits = [];
		
		while(true){
			$traits = array_merge($traits,$ref->getTraitNames());
			
			if(($ref = $ref->getParentClass()) === false){
				break;
			}
		}
		return array_unique($traits);
	}
	
	/**
	 * 指定のクラスと同階層にあるクラスの一覧
	 */
	public static function ls_classes(string $base_class, ?string $parent_class_name=null, bool $recursive=false): array{
		$result = [];
		$ref = new \ReflectionClass($base_class);
		$dir = dirname($ref->getFileName());
		
		foreach(\ebi\Util::ls($dir,$recursive,'/\.php$/') as $f){
			$subns = '';
			
			if($recursive){
				$subns = str_replace([$dir,DIRECTORY_SEPARATOR],['','\\'],dirname($f->getPathname()));
			}
			$classname = $ref->getNamespaceName().$subns.'\\'.basename($f->getFilename(),'.php');
			
			if(class_exists($classname)){
				if(empty($parent_class_name) || is_subclass_of($classname, $parent_class_name)){
					$result[] = $classname;
				}
			}
		}
		sort($result);
		
		return $result;
	}
	
	/**
	 * クラスリソースのパス
	 */
	public static function get_class_resources(string $class, string $path=''): string{
		$ref = new \ReflectionClass($class);
		$dir = dirname($ref->getFileName()).'/'.$ref->getShortName();
		
		return $dir.'/resources'.self::path_slash($path,true);
	}
	
	/**
	 * camelcaseをsnakecaseへ変換する
	 */
	public static function camel2snake(string $str): string{
		if(empty($str)){
			return '';
		}
		$str = preg_replace("/^.*\\\\(.+)$/","\\1",$str);
		
		$name = strtolower($str[0]);
		
		for($i=1;$i<strlen($str);$i++){
			$name .= (ctype_lower($str[$i]) || ctype_digit($str[$i])) ? $str[$i] : '_'.strtolower($str[$i]);
		}
		return $name;
	}
	
	/**
	 * 配列のキー順でkeyが範囲内の値を返す
	 * 最小より小さければ最小を、最大より大きければ最大を返す
	 * @param mixed $key
	 * @return mixed
	 */
	public static function array_range_search($key, array $array){
		krsort($array);
		
		foreach($array as $k => $v){
			if($k <= $key){
				return $v;
			}
		}
		return empty($array) ? null : $v;
	}
	
	/**
	 * 文字列を処理し数値配列を返す
	 * @param mixed $str
	 * @deprecated
	 */
	public static function parse_numbers($str): array{
		$list = [];
		
		foreach((is_array($str) ? $str : explode(',',$str)) as $p){
			$p = trim($p);
			
			if(!empty($p)){
				if(is_numeric($p)){
					$list[$p] = $p;
				}else if(strpos($p,'..') !== false){
					[$start, $end] = explode('..',$p,2);
					
					if(!is_numeric($start) || !is_numeric($end)){
						throw new \ebi\exception\IllegalDataTypeException('value must be a number');
					}
					
					foreach(range($start,$end) as $n){
						$list[$n] = $n;
					}
				}else{
					throw new \ebi\exception\IllegalDataTypeException('value must be a number');
				}
			}
		}
		return array_keys($list);
	}
	/**
	 * 文字列を圧縮する
	 * @deprecated
	 */
	public static function compress(string $string, bool $base64=false): string{
		return ($base64) ? base64_encode(gzdeflate($string)) : gzdeflate($string);
	}
	
	/**
	 * 文字列を展開する
	 * @deprecated
	 */
	public static function uncompress(string $string, bool $base64=false): string{
		return ($base64) ? gzinflate(base64_decode($string)) : gzinflate($string);
	}
}

<?php
namespace ebi;
/**
 * ユーティリティ群
 */
class Util{
	/**
	 * ファイルから取得する
	 * @param string $filename ファイルパス
	 * @return string
	 */
	public static function file_read($filename){
		if(!is_readable($filename) || !is_file($filename)){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
		return file_get_contents($filename);
	}
	/**
	 * CSVファイルから１行ずつ配列で取得する
	 * @param string $filename
	 * @param string $delimiter 区切り文字
	 * @return array
	 */
	public static function file_read_csv($filename,$delimiter=','){
		try{
			$file = new \SplFileObject($filename);
			$file->setCsvControl($delimiter);
			$file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
			
			foreach($file as $line){
				yield $line;
			}
		}catch(\RuntimeException $e){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
	}
	/**
	 * CSVファイルとして配列を書き出す
	 * @param \SplFileObject $file
	 * @param array $arr
	 * @return \SplFileObject 
	 */
	public static function file_append_csv($file,array $arr=[]){
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
			$file->fputcsv($arr);
		}
		return $file;
	}
	/**
	 * ファイルに書き出す
	 * @param string $filename ファイルパス
	 * @param string $src 内容
	 */
	public static function file_write($filename,$src=null,$lock=true){
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
	 * @param string $filename ファイルパス
	 * @param string $src 追加する内容
	 * @param integer $dir_permission モード　8進数(0644)
	 */
	public static function file_append($filename,$src=null,$lock=true){
		self::mkdir(dirname($filename));
		
		if(false === file_put_contents($filename,(string)$src,FILE_APPEND|(($lock) ? LOCK_EX : 0))){
			throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$filename));
		}
	}
	/**
	 * フォルダを作成する
	 * @param string $source 作成するフォルダパス
	 * @param integer $permission
	 */
	public static function mkdir($source,$permission=0775){
		$bool = true;
		if(!is_dir($source)){
			try{
				$list = explode('/',str_replace('\\','/',$source));
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
				throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$source));
			}
		}
		return $bool;
	}
	/**
	 * 移動
	 * @param string $source 移動もとのファイルパス
	 * @param string $dest 移動後のファイルパス
	 */
	public static function mv($source,$dest){
		if(is_file($source) || is_dir($source)){
			self::mkdir(dirname($dest));
			return rename($source,$dest);
		}
		throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`',$source));
	}
	/**
	 * 削除
	 * $sourceがフォルダで$inc_selfがfalseの場合は$sourceフォルダ以下のみ削除
	 * @param string $source 削除するパス
	 * @param boolean $inc_self $sourceも削除するか
	 */
	public static function rm($source,$inc_self=true){
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
			return;
		}else if(is_file($source) && unlink($source)){
			return;
		}
	}
	/**
	 * コピー
	 * $sourceがフォルダの場合はそれ以下もコピーする
	 * @param string $source コピー元のファイルパス
	 * @param string $dest コピー先のファイルパス
	 */
	public static function copy($source,$dest){
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
	 * @param string $directory 検索対象のディレクトリパス
	 * @return string[]
	 */
	public static function lsd($directory){
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
	 * @param string $directory  検索対象のファイルパス
	 * @param boolean $recursive 階層を潜って取得するか
	 * @param string $pattern 検索するパターンを表す文字列
	 * @return \RecursiveDirectoryIterator
	 */
	public static function ls($directory,$recursive=false,$pattern=null){
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
	private static function parse_filename($filename){
		$filename = preg_replace("/[\/]+/",'/',str_replace("\\",'/',trim($filename)));
		return (substr($filename,-1) == '/') ? substr($filename,0,-1) : $filename;
	}
	
	/**
	 * 絶対パスを返す
	 * @param string $a
	 * @param string $b
	 * @return string
	 */
	public static function path_absolute($a,$b){
		if($b === '' || $b === null) return $a;
		if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)) return $b;
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
			list($r) = explode('/',$a,2);
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
	 * @param string $path ファイルパス
	 * @param boolean $prefix 先頭にスラッシュを存在させるか
	 * @param boolean $postfix 末尾にスラッシュを存在させるか
	 * @return string
	 */
	public static function path_slash($path,$prefix,$postfix=null){
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
	 * @return string
	 */
	public static function plain_text($text){
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
				return preg_match("/^([\040\t]+)/",$lines[0],$match) ? 
							preg_replace('/^'.$match[1].'/m','',implode("\n",$lines)) : 
							implode("\n",$lines);
			}
		}
		return $text;
	}
	/**
	 * フォーマット文字列 $str に基づき生成された文字列を返します。
	 *
	 * @param string $str 対象の文字列
	 * @param mixed[] $params フォーマット中に現れた置換文字列{1},{2}...を置換する値
	 * @return string
	 */
	public static function fstring($str,$params){
		if(preg_match_all("/\{([\d]+)\}/",$str,$match)){
			$params = func_get_args();
			array_shift($params);
			if(is_array($params[0])){
				$params = $params[0];
			}			
			foreach($match[1] as $key => $value){
				$i = ((int)$value) - 1;
				$str = str_replace($match[0][$key],isset($params[$i]) ? $params[$i] : '',$str);
			}
		}
		return $str;
	}
	/**
	 * 日付に加減する
	 * 
	 * @param string $time +2 month, -7 day, yesterday, today, tomorrow, first, last
	 * @param mixed $date
	 * @return number
	 * @see http://jp2.php.net/manual/ja/datetime.formats.relative.php
	 * @throws \ebi\exception\InvalidArgumentException 日付フォーマットが異常
	 */
	public static function add_date($time,$date=null){
		if(!isset($date)){
			$t = time();
		}else if(ctype_digit((string)$date) || (substr($date,0,1) == '-' && ctype_digit(substr($date,1)))){
			$t = (int)$date;
		}else{
			$t = strtotime($date);
		}
		if($time == 'first'){
			$time = 'first day of 00:00:00';
		}else if($time == 'last'){
			$time = 'last day of 23:59:59';
		}
		
		$rtn = strtotime($time,$t);
		if($rtn === false){
			throw new \ebi\exception\InvalidArgumentException(sprintf('invalid date and time formats `%s`',$time));
		}
		return $rtn;
	}
	
	/**
	 * 文字列を丸める
	 * @param string $str 対象の文字列
	 * @param integer $width 指定の幅
	 * @param string $postfix 文字列がまるめられた場合に末尾に接続される文字列
	 * @return string
	 */
	public static function trim_width($str,$width,$postfix=''){
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
	 * 文字列からインスタンスを返す
	 * @param string $class
	 * @return object
	 */
	public static function strtoinstance($class){
		if(is_object($class)){
			return $class;
		}
		$class = str_replace(['.','/'],'\\',$class);
		$r = new \ReflectionClass($class);
		return $r->newInstance();
	}
	/**
	 * クラス名
	 * @param string $class_name
	 * @return string
	 */
	public static function get_class_name($class_name){
		if(class_exists($class_name)){
			return $class_name;
		}
		$class_name = str_replace('.','\\',$class_name);
	
		if(!class_exists($class_name)){
			throw new \ebi\exception\ClassNotFoundException('Class `'.$class_name.'` not found');
		}
		$r = new \ReflectionClass($class_name);
		return $r->getName();
	}
			
	/**
	 * 対象がtrue / 1 / 'true' ならtrue
	 * @param  mixed $bool
	 * @return boolean
	 */
	public static function is_true($bool){
		return ($bool === true || $bool === 1 || strtolower($bool) === 'true') ? true : false;		
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
	 * @param string $class
	 * @return array
	 */
	public static function get_class_traits($class){
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
	 * @param string $base_class 基点となるクラス名
	 * @param string $parent_class_name 指定したサブクラスに属するクラスに絞り込む
	 * @param boolean $recursive 階層を潜って取得するか
	 * @return string[]
	 */
	public static function ls_classes($base_class,$parent_class_name=null,$recursive=false){
		$result = [];
		$ref = new \ReflectionClass($base_class);
		$namespace = $ref->getNamespaceName();
		$dir = dirname($ref->getFileName());
		
		foreach(\ebi\Util::ls($dir,$recursive,'/\.php$/') as $f){
			$subns = '';
			
			if($recursive){
				$subns = str_replace([$dir,DIRECTORY_SEPARATOR],['','\\'],dirname($f->getPathname()));
			}
			$classname = $namespace.$subns.'\\'.basename($f->getFilename(),'.php');
			
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
	 * 改行付きで出力
	 * @param string $val
	 */
	public static function println($val){
		print(((string)$val).PHP_EOL);
	}
	/**
	 * camelcaseをsnakecaseへ変換する
	 * namespace部は無視される
	 * @param string $str
	 * @return string
	 */
	public static function camel2snake($str){
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
	
}

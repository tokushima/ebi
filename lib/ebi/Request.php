<?php
namespace ebi;
/**
 * リクエストを処理する
 * @author tokushima
 */
class Request implements \IteratorAggregate{
	private $vars = [];
	private $files = [];
	private $args;
	private $_method;
	
	public function __construct(){
		if('' != ($pathinfo = array_key_exists('PATH_INFO',$_SERVER) ? $_SERVER['PATH_INFO'] : '')){
			if($pathinfo[0] != '/') $pathinfo = '/'.$pathinfo;
			$this->args = preg_replace("/(.*?)\?.*/","\\1",$pathinfo);
		}
		$this->_method = self::method();
		
		if(isset($this->_method)){
			if($this->_method == 'POST'){
				if(isset($_POST) && is_array($_POST)){
					$this->vars = array_merge($this->vars,$_POST);
				}
				if(isset($_FILES) && is_array($_FILES)){
					$marge_func = null;
					$marge_func = function($arr,$pk,$files,&$map) use(&$marge_func){
						if(is_array($arr)){
							foreach($arr as $k => $v){
								$marge_func($v,array_merge($pk,[$k]),$files,$map);
							}
						}else{
							$ks = implode('',array_map(function($v){ return '[\''.$v.'\']';},$pk));
							$eval = 'if(isset($files[\'name\']'.$ks.') && !empty($files[\'name\']'.$ks.')){ ';
								foreach(['name','tmp_name','size','error'] as $k){
									$eval .= '$map'.$ks.'[\''.$k.'\']=$files[\''.$k.'\']'.$ks.';';
								}
							eval($eval.'}');
						}
					};
					foreach($_FILES as $k => $v){
						if(is_array($v['name'])){
							$this->files[$k] = [];
							$marge_func($v['name'],[],$v,$this->files[$k]);
						}else if(array_key_exists('name',$v) && !empty($v['name'])){
							$this->files[$k] = $v;
						}
					}
				}
			}else if(isset($_GET) && is_array($_GET)){
				$this->vars = array_merge($this->vars,$_GET);
			}
			if(array_key_exists('_method',$this->vars)){
				if(empty($this->_method)){
					$this->_method = strtoupper($this->vars['_method']);
				}
				unset($this->vars['_method']);
			}
			if(
				($this->_method == 'POST' || $this->_method == 'PUT' || $this->_method == 'DELETE') &&
				(
					(array_key_exists('HTTP_CONTENT_TYPE',$_SERVER) && strpos($_SERVER['HTTP_CONTENT_TYPE'],'application/json') === 0) ||
					(array_key_exists('CONTENT_TYPE',$_SERVER) && strpos($_SERVER['CONTENT_TYPE'],'application/json') === 0)
				)
			){
				$json = json_decode(file_get_contents('php://input'),true);
				if(is_array($json)){
					$this->vars = array_merge($this->vars,$json);
				}
			}
		}else if(isset($_SERVER['argv'])){
			$argv = $_SERVER['argv'];
			array_shift($argv);
			if(isset($argv[0]) && $argv[0][0] != '-'){
				$this->args = implode(' ',$argv);
			}else{
				$size = sizeof($argv);
				for($i=0;$i<$size;$i++){
					if($argv[$i][0] == '-'){
						if(isset($argv[$i+1]) && $argv[$i+1][0] != '-'){
							$this->vars[substr($argv[$i],1)] = $argv[$i+1];
							$i++;
						}else{
							$this->vars[substr($argv[$i],1)] = '';
						}
					}
				}
			}
		}
	}
	/**
	 * REQUEST_METHOD
	 */
	public static function method(): ?string{
		if(array_key_exists('REQUEST_METHOD',$_SERVER)){
			$method = $_SERVER['REQUEST_METHOD'];
			
			if($_SERVER['REQUEST_METHOD'] == 'POST'){
				if(array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE',$_SERVER)){
					$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
				}
			}
			return $method;
		}
		return null;
	}

	public function getIterator(): \Traversable{
		return new \ArrayIterator($this->vars);
	}
	
	/**
	 * 現在のURLを返す
	 */
	public static function current_url(int $port_https=443, int $port_http=80): ?string{
		$server = self::host($port_https,$port_http);
		
		if(empty($server)){
			return null;
		}
		$path = isset($_SERVER['REQUEST_URI']) ?
			preg_replace("/^(.+)\?.*$/","\\1",$_SERVER['REQUEST_URI']) :
			(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '') : '');
		return $server.$path;
	}
	/**
	 * 現在のホスト
	 */
	public static function host(int $port_https=443, int $port_http=80): ?string{
		$port = $port_http;
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
			$port = $port_https;
		}else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'){
			$port = $port_https;
		}else if(isset($_SERVER['HTTP_X_FORWARDED_PORT'])){
			$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
		}else if(isset($_SERVER['SERVER_PORT']) && !isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
			$port = $_SERVER['SERVER_PORT'];
		}
		$server = preg_replace("/^(.+):\d+$/","\\1",isset($_SERVER['HTTP_X_FORWARDED_HOST']) ?
			$_SERVER['HTTP_X_FORWARDED_HOST'] :
			(
				isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
				(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '')
			));
		if($port != $port_http && $port != $port_https) $server = $server.':'.$port;
		if(empty($server)) return null;
		return (($port == $port_https) ? 'https' : 'http').'://'.preg_replace("/^(.+?)\?.*/","\\1",$server);
	}
	/**
	 * 現在のリクエストクエリを返す
	 * @param $sep 先頭に?をつけるか
	 */
	public static function request_string(bool $sep=false): string{
		$query = ((isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '');
		return (($sep && !empty($query)) ? '?' : '').$query;
	}

	public function is_get(): bool{
		return ($this->_method == 'GET');
	}
	public function is_post(): bool{
		return ($this->_method == 'POST');
	}
	public function is_put(): bool{
		return ($this->_method == 'PUT');
	}
	public function is_delete(): bool{
		return ($this->_method == 'DELETE');
	}
	public function is_cli(): bool{
		return (php_sapi_name() == 'cli' || !isset($_SERVER['REQUEST_METHOD']));
	}
	public static function user_agent(): ?string{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}
	/**
	 * pathinfo または argv
	 */
	public function args(): ?string{
		return $this->args;
	}
	/**
	 * 変数をセットする
	 * @param mixed $value
	 */
	public function vars(string $key, $value): void{
		$this->vars[$key] = $value;
	}
	/**
	 * ファイルをセットする
	 * @param mixed $file
	 */
	public function file_vars(string $key, $file): void{
		if(is_array($file)){
			$this->files[$key] = $file;
		}else if(is_file($file)){
			$this->files[$key] = [
				'name'=>basename($file),
				'tmp_name'=>$file,
				'size'=>filesize($file),
			];
		}
	}
	
	/**
	 * 変数の取得
	 * @return mixed
	 */
	public function in_vars(string $n, $d=null){
		if(array_key_exists($n,$this->vars)){
			return $this->vars[$n];
		}
		if($d !== null){
			$this->vars[$n] = $d;
			return $this->vars[$n];
		}
		return null;
	}
	/**
	 * キーが存在するか
	 */
	public function is_vars(string $n): bool{
		return array_key_exists($n,$this->vars);
	}
	/**
	 * 変数の削除
	 */
	public function rm_vars(...$args): void{
		if(empty($args) === 0){
			$this->vars = [];
		}else{
			foreach($args as $n){
				unset($this->vars[$n]);
			}
		}
	}
	/**
	 * 変数の一覧を返す
	 */
	public function ar_vars(...$args): array{
		if(!empty($args)){
			$result = $this->vars;
			
			foreach($args as $arg){
				if(!empty($arg) && is_array($arg)){
					$result = array_merge($result,$arg);
				}else if($arg instanceof \ebi\Paginator){
					$result['paginator'] = $arg;
				}
			}
			return $result;
		}
		return $this->vars;
	}
	/**
	 * 変数の一覧を返す
	 */
	public function ar_files(): array{
		return $this->files;
	}
	/**
	 * 添付ファイル情報の取得
	 */
	public function in_files(string $n): ?array{
		if(($err = error_get_last()) !== null &&
			$err['file'] == 'Unknown' &&
			$err['line'] == 0 &&
			strpos($err['message'],'POST Content-Length of') !== false
		){
			throw new \ebi\exception\ContentLengthException('Upload failed');
		}
		if(array_key_exists($n,$this->files)){
			if(array_key_exists('error',$this->files[$n])){
				// http://php.net/manual/ja/features.file-upload.errors.php
				switch($this->files[$n]['error']){
					case UPLOAD_ERR_OK:
						return $this->files[$n];
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
					case UPLOAD_ERR_PARTIAL:
					case UPLOAD_ERR_NO_FILE:
						throw new \ebi\exception\ContentLengthException('Upload failed ('.$this->files[$n]['error'].')');
					case UPLOAD_ERR_NO_TMP_DIR:
					case UPLOAD_ERR_CANT_WRITE:
					case UPLOAD_ERR_EXTENSION:
						throw new \ebi\exception\IOException('Upload failed ('.$this->files[$n]['error'].')');
					default:
				}
			}
			return $this->files[$n];
		}
		if(array_key_exists($n,$this->vars)){
			$value = base64_decode($this->vars[$n],true);
			
			if($value !== false){
				$path = \ebi\WorkingStorage::tmpfile($value);
				$this->file_vars($n,$path);
				unset($this->vars[$n]);
				unset($value);
				
				return $this->files[$n];
			}
		}
		return null;
	}
	/**
	 * 添付されたファイルがあるか
	 * @param mixed $file_info string|array
	 */
	public function has_file($file_info): bool{
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		return isset($file_info['tmp_name']) && is_file($file_info['tmp_name']);
	}
	/**
	 * 添付ファイルのオリジナルファイル名の取得
	 * @param mixed $file_info string|array
	 */
	public function file_original_name($file_info): ?string{
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		return isset($file_info['name']) ? $file_info['name'] : null;
	}
	/**
	 * 添付ファイルのファイルパスの取得
	 * @param mixed $file_info string|array
	 */
	public function file_path($file_info): ?string{
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		if(!isset($file_info['tmp_name']) || !is_file($file_info['tmp_name']) || filesize($file_info['tmp_name']) == 0){
			throw new \ebi\exception\UnknownFileException();
		}
		return isset($file_info['tmp_name']) ? $file_info['tmp_name'] : null;
	}
	/**
	 * 添付ファイルを移動します
	 * @param mixed $file_info string|array
	 * @param string $newname
	 */
	public function move_file($file_info, string $newname){
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		if(!$this->has_file($file_info)){
			throw new \ebi\exception\NotFoundException('file not found ');
		}
		if(!is_dir(dirname($newname))){
			\ebi\Util::mkdir(dirname($newname));
		}
		\ebi\Util::copy($file_info['tmp_name'],$newname);
		\ebi\Util::rm($file_info['tmp_name']);
	}
}
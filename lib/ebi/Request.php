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
					foreach($_POST as $k => $v){
						$this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
					}
				}
				if(isset($_FILES) && is_array($_FILES)){
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
				foreach($_GET as $k => $v){
					$this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
				}
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
					foreach($json as $k => $v){
						$this->vars[$k] = $v;
					}
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
	 * @return string
	 */
	public static function method(){
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
	/**
	 * varsを返す
	 * @see \IteratorAggregate::getIterator()
	 */
	public function getIterator(){
		return new \ArrayIterator($this->vars);
	}
	/**
	 * 現在のURLを返す
	 * @param integer $port_https
	 * @param integer $port_http
	 * @return string
	 */
	public static function current_url($port_https=443,$port_http=80){
		$server = self::host($port_https,$port_http);
		if(empty($server)) return null;
		$path = isset($_SERVER['REQUEST_URI']) ?
					preg_replace("/^(.+)\?.*$/","\\1",$_SERVER['REQUEST_URI']) :
					(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '') : '');
		return $server.$path;
	}
	/**
	 * 現在のホスト
	 * @param integer $port_https
	 * @param integer $port_http
	 * @return string
	 */
	public static function host($port_https=443,$port_http=80){
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
	 * @param boolean $sep 先頭に?をつけるか
	 * @return string
	 */
	public static function request_string($sep=false){
		$query = ((isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'].'&' : '').file_get_contents('php://input');
		return (($sep && !empty($query)) ? '?' : '').$query;
	}
	/**
	 * GET
	 * @return boolean
	 */
	public function is_get(){
		return ($this->_method == 'GET');
	}
	/**
	 * POST
	 * @return boolean
	 */
	public function is_post(){
		return ($this->_method == 'POST');
	}
	/**
	 * PUT
	 * @return boolean
	 */
	public function is_put(){
		return ($this->_method == 'PUT');
	}
	/**
	 * DLETE
	 * @return boolean
	 */
	public function is_delete(){
		return ($this->_method == 'DELETE');
	}
	/**
	 * CLIで実行されたか
	 * @return boolean
	 */
	public function is_cli(){
		return (php_sapi_name() == 'cli' || !isset($_SERVER['REQUEST_METHOD']));
	}
	/**
	 * ユーザエージェント
	 * @return string
	 */
	public static function user_agent(){
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}
	/**
	 * クッキーへの書き出し
	 * @param string $name 書き込む変数名
	 * @param mixed $value
	 * @param int $expire 有効期限(秒)
	 */
	public static function write_cookie($name,$value,$expire=null){
		$cookie_params = \ebi\Conf::cookie_params();
		
		if(empty($expire)){
			$expire = time() + $cookie_params['cookie_lifetime'];
		}
		setcookie(
			$name,
			$value,
			$expire,
			$cookie_params['cookie_path'],
			$cookie_params['cookie_domain'],
			$cookie_params['cookie_secure']
		);
	}
	/**
	 * クッキーから取得
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function read_cookie($name,$default=null){
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
	}
	/**
	 * クッキーから削除
	 * 登録時と同条件のものが削除される
	 * @param string $name クッキー名
	 */
	public static function delete_cookie($name){
		$cookie_params = \ebi\Conf::cookie_params();
		
		setcookie(
			$name,
			null,
			(time() - 1209600),
			$cookie_params['cookie_path'],
			$cookie_params['cookie_domain'],
			$cookie_params['cookie_secure']
		);
	}
	/**
	 * pathinfo または argv
	 * @return string
	 */
	public function args(){
		return $this->args;
	}
	/**
	 * 変数をセットする
	 * @param string $key
	 * @param mixed $value
	 */
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	/**
	 * ファイルをセットする
	 * @param string $key
	 * @param mixed $file
	 */
	public function file_vars($key,$file){
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
	 * @param string $n
	 * @param mixed $d 未定義の場合の値
	 * @return mixed
	 */
	public function in_vars($n,$d=null){
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
	 * @param string $n
	 * @return boolean
	 */
	public function is_vars($n){
		return array_key_exists($n,$this->vars);
	}
	/**
	 * 変数の削除
	 */
	public function rm_vars(){
		if(func_num_args() === 0){
			$this->vars = [];
		}else{
			foreach(func_get_args() as $n){
				unset($this->vars[$n]);
			}
		}
	}
	/**
	 * 変数の一覧を返す
	 * @param mixed{}
	 * @return array
	 */
	public function ar_vars(){
		if(func_num_args() > 0){
			$result = $this->vars;
			
			foreach(func_get_args() as $arg){
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
	 * @return array
	 */
	public function ar_files(){
		return $this->files;
	}
	/**
	 * 添付ファイル情報の取得
	 * @param string $n
	 * @return array
	 */
	public function in_files($n){
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
	 * @param array $file_info
	 * @return boolean
	 */
	public function has_file($file_info){
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		return isset($file_info['tmp_name']) && is_file($file_info['tmp_name']);
	}
	/**
	 * 添付ファイルのオリジナルファイル名の取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_original_name($file_info){
		if(is_string($file_info)){
			$file_info = $this->in_files($file_info);
		}
		return isset($file_info['name']) ? $file_info['name'] : null;
	}
	/**
	 * 添付ファイルのファイルパスの取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_path($file_info){
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
	 * @param array $file_info
	 * @param string $newname
	 */
	public function move_file($file_info,$newname){
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

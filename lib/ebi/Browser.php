<?php
namespace ebi;

class Browser{
	private $resource;
	private $agent;
	private $timeout = 30;
	private $redirect_max = 20;
	private $redirect_count = 1;

	private $request_header = [];
	private $request_vars = [];
	private $request_file_vars = [];
	private $head;
	private $body;
	private $cookie = [];
	private $url;
	private $status;
	
	private $user;
	private $password;
	private $bearer_token;
	private $proxy;
	private $ssl_verify = true;
	
	private $raw;
	
	private static $recording_request = false;
	private static $record_request = [];
	
	public function __construct(?string $agent=null, int $timeout=30, int $redirect_max=20){
		$this->agent = $agent;
		$this->timeout = (int)$timeout;
		$this->redirect_max = (int)$redirect_max;
	}

	/**
	 * 最大リダイレクト回数を設定
	 */
	public function redirect_max(int $redirect_max): self{
		$this->redirect_max = (int)$redirect_max;
		return $this;
	}

	/**
	 * タイムアウト時間を設定
	 */
	public function timeout(int $timeout_sec): self{
		$this->timeout = (int)$timeout_sec;
		return $this;
	}

	/**
	 * ユーザエージェントを設定
	 */
	public function agent(string $agent): self{
		$this->agent = $agent;
		return $this;
	}

	/**
	 * Basic認証
	 */
	public function basic(string $user, string $password): self{
		$this->user = $user;
		$this->password = $password;
		return $this;
	}

	/**
	 * Bearer token
	 */
	public function bearer_token(string $token): self{
		$this->bearer_token = $token;
		return $this;
	}
	/**
	 * Proxy
	 */
	public function proxy(string $url, ?int $port=null): self{
		$this->proxy = [$url, $port];
		return $this;
	}
	
	/**
	 * SSL証明書を確認する
	 */
	public function ssl_verify(bool $bool): self{
		$this->ssl_verify = $bool;
		return $this;
	}
	
	public function __toString(){
		return $this->body();
	}

	/**
	 * ヘッダを設定
	 */
	public function header(string $key, string $value): self{
		$this->request_header[$key] = $value;
		return $this;
	}

	/**
	 * クエリを設定
	 */
	public function vars(string $key, string|array $value): self{
		$this->request_vars[$key] = $value;
		
		if(isset($this->request_file_vars[$key])){
			unset($this->request_file_vars[$key]);
		}
		return $this;
	}

	/**
	 * クエリにファイルを設定
	 */
	public function file_vars(string $key, string $filename): self{
		$this->request_file_vars[$key] = $filename;
		
		if(isset($this->request_vars[$key])){
			unset($this->request_vars[$key]);
		}
		return $this;
	}

	/**
	 * クエリが設定されているか
	 */
	public function has_vars(string $key): bool{
		return (
			array_key_exists($key, $this->request_vars) || 
			array_key_exists($key, $this->request_file_vars)
		);
	}

	/**
	 * cURL 転送用オプションを設定する
	 * @param mixed $value
	 */
	public function setopt(string $key, $value): self{
		if(!isset($this->resource)){
			$this->resource = curl_init();
		}
		curl_setopt($this->resource,$key,$value);
		return $this;
	}

	/**
	 * 結果のヘッダを取得
	 */
	public function response_headers(): string{
		return $this->head;
	}

	/**
	 * クッキーを取得
	 */
	public function cookies(): array{
		return $this->cookie;
	}

	/**
	 * 結果の本文を取得
	 */
	public function body(): string{
		return ($this->body === null || is_bool($this->body)) ? '' : $this->body;
	}

	/**
	 * 結果のURLを取得
	 */
	public function url(): string{
		return $this->url;
	}

	/**
	 * 結果のステータスを取得
	 */
	public function status(): int{
		return empty($this->status) ? 0 : (int)$this->status;
	}

	/**
	 * HEADリクエスト
	 */
	public function do_head(string $url): self{
		return $this->request('HEAD', $url);
	}

	/**
	 * PUTリクエスト
	 */
	public function do_put(string $url): self{
		return $this->request('PUT', $url);
	}

	/**
	 * DELETEリクエスト
	 */
	public function do_delete(string $url): self{
		return $this->request('DELETE', $url);
	}

	/**
	 * GETリクエスト
	 */
	public function do_get(string $url): self{
		return $this->request('GET', $url);
	}

	/**
	 * POSTリクエスト
	 */
	public function do_post(string $url): self{
		return $this->request('POST', $url);
	}

	/**
	 * POSTリクエスト(RAW)
	 */
	public function do_raw(string $url, string $value): self{
		$this->raw = $value;
		return $this->request('RAW', $url);
	}

	/**
	 * POSTリクエスト(JSON)
	 */
	public function do_json(string $url): self{
		$this->header('Content-Type', 'application/json');
		return $this->do_raw($url, json_encode($this->request_vars));
	}

	/**
	 * GETリクエストでダウンロードする
	 */
	public function do_download(string $url, string $filename): self{
		return $this->request('GET', $url, $filename);
	}

	/**
	 * POSTリクエストでダウンロードする
	 */
	public function do_post_download(string $url, string $filename): self{
		return $this->request('POST', $url, $filename);
	}

	/**
	 * ヘッダ情報をハッシュで取得する
	 */
	public function explode_head(): array{
		$result = $m = [];

		foreach(explode("\n",$this->head) as $h){
			if(preg_match("/^(.+?):(.+)$/",$h,$m)){
				$result[trim($m[1])] = trim($m[2]);
			}
		}
		return $result;
	}

	/**
	 * 送信たリクエストの記録を開始する
	 */
	public static function start_record(): void{
		self::$recording_request = true;
		self::$record_request = [];
	}

	/**
	 * 送信したリクエストの記録を終了する
	 */
	public static function stop_record(): array{
		self::$recording_request = false;
		return self::$record_request;
	}

	private function request(string $method,string $url, ?string $download_path=null): self{
		if(!isset($this->resource)){
			$this->resource = curl_init();
		}
		$url_info = parse_url($url);
		$cookie_base_domain = ($url_info['host'] ?? '').($url_info['path'] ?? '');
		
		switch($method){
			case 'RAW':
			case 'POST': curl_setopt($this->resource, CURLOPT_POST,true); break;
			case 'GET':
				if(isset($url_info['query'])){
					$vars = [];
					parse_str($url_info['query'], $vars);
				
					foreach($vars as $k => $v){
						if(!isset($this->request_vars[$k])){
							$this->request_vars[$k] = $v;
						}
					}
					[$url] = explode('?', $url, 2);
				}
				curl_setopt($this->resource,CURLOPT_HTTPGET,true);
				break;
			case 'HEAD': curl_setopt($this->resource, CURLOPT_NOBODY, true); break;
			case 'PUT': curl_setopt($this->resource, CURLOPT_PUT, true); break;
			case 'DELETE': curl_setopt($this->resource, CURLOPT_CUSTOMREQUEST, 'DELETE'); break;
		}
		
		$http_build_query = function($vars){
			foreach($vars as $v){
				if(is_bool($v)){
					$v = ($v) ? 'true' : 'false';
				}
			}
			return preg_replace('/%5B%5D%5B[0-9]+%5D/ms','%5B%5D',http_build_query($vars));
		};
		
		switch($method){
			case 'POST':
				if(!empty($this->request_file_vars)){
					$vars = [];

					if(!empty($this->request_vars)){
						foreach(explode('&',$http_build_query($this->request_vars)) as $q){
							$s = explode('=',$q,2);
							$vars[urldecode($s[0])] = isset($s[1]) ? urldecode($s[1]) : null;
						}
					}
					foreach(explode('&',$http_build_query($this->request_file_vars)) as $q){
						$s = explode('=',$q,2);
						
						if(isset($s[1])){
							if(!is_file($f=urldecode($s[1]))){
								throw new \ebi\exception\InvalidArgumentException($f.' not found');
							}
							$vars[urldecode($s[0])] = (class_exists('\\CURLFile',false)) ? new \CURLFile($f,null,basename($f)) : '@'.$f;
						}
					}
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,$vars);
				}else{
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,$http_build_query($this->request_vars));
				}
				break;
			case 'RAW':
				if(!isset($this->request_header['Content-Type'])){
					$this->request_header['Content-Type'] = 'text/plain';
				}
				curl_setopt($this->resource,CURLOPT_POSTFIELDS,$this->raw);
				break;
			case 'GET':
			case 'HEAD':
			case 'PUT':
			case 'DELETE':
				$url = $url.(!empty($this->request_vars) ? '?'.$http_build_query($this->request_vars) : '');
		}
		curl_setopt($this->resource,CURLOPT_URL,$url);
		curl_setopt($this->resource,CURLOPT_FOLLOWLOCATION,false);
		curl_setopt($this->resource,CURLOPT_HEADER,false);
		curl_setopt($this->resource,CURLOPT_RETURNTRANSFER,false);
		curl_setopt($this->resource,CURLOPT_FORBID_REUSE,true);
		curl_setopt($this->resource,CURLOPT_FAILONERROR,false);
		curl_setopt($this->resource,CURLOPT_TIMEOUT,$this->timeout);
		
		if(self::$recording_request){
			curl_setopt($this->resource,CURLINFO_HEADER_OUT,true);
		}
		
		/**
		 * @param bool $ssl_verify SSL証明書を確認するかの真偽値
		 */
		if($this->ssl_verify === false || \ebi\Conf::get('ssl-verify',true) === false){
			curl_setopt($this->resource, CURLOPT_SSL_VERIFYHOST,false);
			curl_setopt($this->resource, CURLOPT_SSL_VERIFYPEER,false);
		}
		if(!empty($this->proxy)){
			curl_setopt($this->resource,CURLOPT_HTTPPROXYTUNNEL,true);
			curl_setopt($this->resource,CURLOPT_PROXY,$this->proxy[0]);
			
			if(!empty($this->proxy[1] ?? null)){
				curl_setopt($this->resource,CURLOPT_PROXYPORT,$this->proxy[1]);
			}
		}
		if(!empty($this->user)){
			curl_setopt($this->resource,CURLOPT_USERPWD,$this->user.':'.$this->password);
		}else if(!empty($this->bearer_token)){
			$this->request_header['Authorization'] = 'Bearer '.$this->bearer_token;
		}
		if(!isset($this->request_header['Expect'])){
			$this->request_header['Expect'] = null;
		}
		if(!empty($this->cookie)){
			$cookies = '';
			$now = time();
			
			foreach($this->cookie as $domain => $cookieval){
				if(strpos($cookie_base_domain,$domain) === 0 || strpos($cookie_base_domain,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
					foreach($cookieval as $k => $v){
						if(!empty($v['expires']) && $v['expires'] < $now){
							unset($this->cookie[$domain][$k]);
						}else if(!$v['secure'] || ($v['secure'] && substr($url,0,8) == 'https://')){
							$cookies .= sprintf('%s=%s; ',$k,$v['value']);
						}
					}
				}
			}
			curl_setopt($this->resource,CURLOPT_COOKIE,$cookies);
		}
		if(!isset($this->request_header['User-Agent'])){
			curl_setopt($this->resource,CURLOPT_USERAGENT,
				(empty($this->agent) ?
					(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null) :
					$this->agent
				)
			);
		}
		curl_setopt($this->resource,CURLOPT_HTTPHEADER,
			array_map(
				function($k,$v){
					return $k.': '.$v;
				},
				array_keys($this->request_header),
				$this->request_header
			)
		);

		curl_setopt($this->resource,CURLOPT_HEADERFUNCTION, function($curl, $data){
			$this->head .= $data;
			return strlen($data);
		});
		
		if(empty($download_path)){
			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION, function($curl, $data){
				$this->body .= $data;
				return strlen($data);		
			});
		}else{
			if(strpos($download_path,'://') === false && !is_dir(dirname($download_path))){
				mkdir(dirname($download_path),0777,true);
			}
			$fp = fopen($download_path,'wb');
			
			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION, function($curl,$data) use(&$fp){
				if($fp){
					fwrite($fp,$data);
				}
				return strlen($data);
			});
		}
		$this->request_header = $this->request_vars = $this->request_file_vars = [];
		$this->head = $this->body = $this->raw = '';
		$this->bearer_token = $this->user = $this->password = null;
		
		curl_exec($this->resource);
		
		if(!empty($download_path) && $fp){
			fclose($fp);
		}
		if(($err_code = curl_errno($this->resource)) > 0){
			if($err_code == 47 || $err_code == 52){
				return $this;
			}
			throw new \ebi\exception\ConnectionException($err_code.': '.curl_error($this->resource));
		}
		$this->url = curl_getinfo($this->resource,CURLINFO_EFFECTIVE_URL);
		$this->status = curl_getinfo($this->resource,CURLINFO_HTTP_CODE);

		if(self::$recording_request){
			self::$record_request[] = curl_getinfo($this->resource,CURLINFO_HEADER_OUT);
		}
		
		$match = [];
		if(preg_match_all('/Set-Cookie:[\s]*(.+)/i',$this->head,$match)){
			foreach($match[1] as $cookies){
				$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
				$cookie_domain = $cookie_base_domain;
				$cookie_path = '/';
				$secure = false;
				
				foreach(explode(';',$cookies) as $cookie){
					$cookie = trim($cookie);
					if(strpos($cookie,'=') !== false){
						[$k, $v] = explode('=',$cookie,2);
						$k = trim($k);
						$v = trim($v);
						switch(strtolower($k)){
							case 'expires': $cookie_expires = ctype_digit($v) ? (int)$v : strtotime($v); break;
							case 'domain': $cookie_domain = preg_replace('/^[\w]+:\/\/(.+)$/','\\1',$v); break;
							case 'path': $cookie_path = $v; break;
							default:
								if(!isset($cookie_name)){
									$cookie_name = $k;
									$cookie_value = $v;
								}
						}
					}else if(strtolower($cookie) == 'secure'){
						$secure = true;
					}
				}
				$cookie_domain = substr(\ebi\Util::path_absolute('http://'.$cookie_domain,$cookie_path),7);
				
				if($cookie_expires !== null && $cookie_expires < time()){
					if(isset($this->cookie[$cookie_domain][$cookie_name])){
						unset($this->cookie[$cookie_domain][$cookie_name]);
					}
				}else{
					$this->cookie[$cookie_domain][$cookie_name] = ['value'=>$cookie_value,'expires'=>$cookie_expires,'secure'=>$secure];
				}
			}
		}
		curl_close($this->resource);
		unset($this->resource);
		
		if($this->redirect_count++ < $this->redirect_max){
			switch($this->status){
				case 300:
				case 301:
				case 302:
				case 303:
				case 307:
					$redirect_url = [];
					if(preg_match('/Location:[\040](.*)/i',$this->head,$redirect_url)){
						return $this->request('GET',trim(\ebi\Util::path_absolute($url,$redirect_url[1])),$download_path);
					}
			}
		}
		$this->redirect_count = 1;
		return $this;
	}

	public function __destruct(){
		if(isset($this->resource)){
			curl_close($this->resource);
		}
	}

	/**
	 * bodyをXMLとして解析しXMLオブジェクトとして返す
	 */
	public function xml(string $name=''): \ebi\Xml{
		return \ebi\Xml::extract($this->body(),$name);
	}

	/**
	 * bodyをJsonとして解析し配列として返す
	 * @return mixed
	 */
	public function json(string $name=''){
		$json = new \ebi\Json($this->body());
		return $json->find($name);
	}
	
	/**
	 * FORMタグからform.action, form.method, varsを取得する
	 */
	public function form(string|int $form_name_or_index=1): array{
		$cnt = 0;
		$vars = [];
		
		foreach(\ebi\Xml::extract($this->body(),'body')->find('form') as $form){
			$cnt++;
			
			if(
				(is_int($form_name_or_index) && $cnt == $form_name_or_index) || 
				$form->in_attr('name') == $form_name_or_index || 
				$form->in_attr('id') == $form_name_or_index
			){
				$chkbx_vars = [];
				foreach($form->find(['input','textarea','select']) as $input){
					if($input->is_attr('name')){
						$tag = strtolower($input->name());
						$nm = str_replace('[]','',$input->in_attr('name'));
						
						if($tag == 'input'){
							$type = strtolower($input->in_attr('type'));
							
							if($type == 'hidden' || $type == 'text'){
								$vars[$nm] = $input->in_attr('value');
							}else if($input->is_attr('checked')){
								if($type == 'radio'){
									$vars[$nm] = $input->in_attr('value');
								}else if($type == 'checkbox'){
									$chkbx_vars[$nm] ?? [];
									$chkbx_vars[$nm][] = $input->in_attr('value');
								}
							}
						}else if($tag == 'textarea'){
							$vars[$nm] = $input->value();
						}else if($tag == 'select'){
							$select = [];
							$val = null;
							
							foreach($input->find('option') as $op){
								if(!isset($val)){
									$val = [$op->in_attr('value')];
								}
								if($op->is_attr('selected')){
									$select[] = $op->in_attr('value');
								}
							}
							if(empty($select)){
								$select = $val;
							}
							$vars[$nm] = $input->is_attr('multiple') ? $select : array_shift($select);
						}
					}
					if(!empty($chkbx_vars)){
						$vars = array_merge($vars,$chkbx_vars);
					}
				}
				foreach($vars as $k => $v){
					$this->vars($k,$v);
				}
				return [
					'action'=>\ebi\Util::path_absolute($this->url(),$form->in_attr('action')),
					'method'=>strtolower($form->in_attr('method','get')),
					'vars'=>$vars,
				];
			}
		}
		throw new \ebi\exception\NotFoundException('not found');
	}
	
	/**
	 * bodyをクエリ文字列として解析し配列として返す
	 */
	public function query_string(?string $name=null): array{
		$array = [];
		parse_str($this->body(),$array);
		
		if(empty($name)){
			return $array;
		}
		$names = explode('/',$name);
		foreach($names as $key){
			if(array_key_exists($key,$array)){
				$array = $array[$key];
			}else{
				throw new \ebi\exception\NotFoundException($name.' not found');
			}
		}
		return $array;
	}
}
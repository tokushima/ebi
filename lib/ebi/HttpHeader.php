<?php
namespace ebi;
/**
 * HTTPヘッダを制御する
 * @author tokushima
 */
class HttpHeader{
	private static $header = [];
	private static $send_status;

	/**
	 * statusを出力する
	 */
	public static function send_status(int $code): void{
		if(!isset(self::$send_status)){
			self::$send_status = $code;
			header('HTTP/1.1 '.self::status_string($code));
		}
	}
	
	/**
	 * キャッシュを指示する
	 * @param $expires キャッシュさせる秒数
	 */
	public static function send_cache(int $expires): void{
		self::send('Last-Modified',gmdate('D, d M Y H:i:s T',time() - $expires));
		self::send('Expires',gmdate('D, d M Y H:i:s T',time() + $expires));
		self::send('Cache-Control','private, max-age='.$expires);
		self::send('Pragma','');
	}
	
	/**
	 * headerを送信する
	 */
	public static function send(string $key, string $value): void{
		if(!isset(self::$header[$key])){
			header($key.': '.$value);
			self::$header[$key] = $value;
		}
	}

	/**
	 * 送信済みヘッダ
	 */
	public static function sended(): array{
		return self::$header;
	}

	/**
	 * HTTPステータスを返す
	 */
	public static function status_string(int $status_code): string{
		switch($status_code){
			case 100: return '100 Continue';
			case 101: return '101 Switching Protocols';
			case 200: return '200 OK';
			case 201: return '201 Created';
			case 202: return '202 Accepted';
			case 203: return '203 Non-Authoritative Information';
			case 204: return '204 No Content';
			case 205: return '205 Reset Content';
			case 206: return '206 Partial Content';
			case 300: return '300 Multiple Choices';
			case 301: return '301 MovedPermanently';
			case 302: return '302 Found';
			case 303: return '303 See Other';
			case 304: return '304 Not Modified';
			case 305: return '305 Use Proxy';
			case 307: return '307 Temporary Redirect';
			case 400: return '400 Bad Request';
			case 401: return '401 Unauthorized';
			case 403: return '403 Forbidden';
			case 404: return '404 Not Found';
			case 405: return '405 Method Not Allowed';
			case 406: return '406 Not Acceptable';
			case 407: return '407 Proxy Authentication Required';
			case 408: return '408 Request Timeout';
			case 409: return '409 Conflict';
			case 410: return '410 Gone';
			case 411: return '411 Length Required';
			case 412: return '412 Precondition Failed';
			case 413: return '413 Request Entity Too Large';
			case 414: return '414 Request-Uri Too Long';
			case 415: return '415 Unsupported Media Type';
			case 416: return '416 Requested Range Not Satisfiable';
			case 417: return '417 Expectation Failed';
			case 500: return '500 Internal Server Error';
			case 501: return '501 Not Implemented';
			case 502: return '502 Bad Gateway';
			case 503: return '503 Service Unavailable';
			case 504: return '504 Gateway Timeout';
			case 505: return '505 Http Version Not Supported';
			default: return '403 Forbidden ('.$status_code.')';
		}
		return '';
	}

	/**
	 * リダイレクトする
	 */
	public static function redirect(string $url, array $vars=[]): void{
		if(!empty($vars)){
			$requestString = http_build_query($vars);
			if(substr($requestString,0,1) == '?') $requestString = substr($requestString,1);
			$url = sprintf('%s?%s',$url,$requestString);
		}
		self::send_status(302);
		self::send('Location',$url);
		exit;
	}

	/**
	 * リファラを取得する
	 */
	public static function referer(): string{
		return (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'://') !== false) ? 
			$_SERVER['HTTP_REFERER'] : 
			$_SERVER['HTTP_HOST'] ?? '';
	}
	
	/**
	 * リファラにリダイレクトする
	 */
	public static function redirect_referer(): void{
		self::redirect(self::referer());
	}

	/**
	 * raw dataを取得する
	 */
	public static function rawdata(): ?string{
		return file_get_contents('php://input');
	}
}
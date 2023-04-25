<?php
namespace testman;

class Conf{
    public static function set(array $vars): void{
    }
}

class Runner{
    public static function current(): string{
        return '';
    }
}

class Util{
    public static function url($entry_key): string{
        return '';
    }
}

class Resource{
    /**
     * @param $path test/testman.resources/以下のパス
     */
    public static function path(string $path): string{
        return '';
    }
}

class Browser{
	/**
	 * ユーザエージェントを設定
	 */
	public function agent(string $agent){
	}
	/**
	 * Basic認証
	 */
	public function basic(string $user, string $password){
	}
	/**
	 * Bearer token
	 */
	public function bearer_token(string $token){
	}	
	/**
	 * ヘッダを設定
	 */
	public function header(string $key, ?string $value=null){
	}
	/**
	 * ACCEPT=application/debugを設定する
	 */
	public function set_header_accept_debug(){
	}
	/**
	 * ACCEPT=application/jsonを設定する
	 */
	public function set_header_accept_json(){
	}
	
	/**
	 * クエリを設定
	 */
	public function vars(string $key, $value=null){
	}
	/**
	 * クエリにファイルを設定
	 */
	public function file_vars(string $key, string $filename){
	}
	/**
	 * 結果の本文を取得
	 */
	public function body(): string{
        return '';
	}
	public function cookies(): array{
		return [];
	}
	/**
	 * 結果のURLを取得
	 */
	public function url(): string{
		return '';
	}
	/**
	 * 結果のステータスを取得
	 */
	public function status(): int{
		return 200;
	}
	/**
	 * GETリクエスト
	 */
	public function do_get($url){
	}
	/**
	 * POSTリクエスト
	 */
	public function do_post($url){
	}
	/**
	 * POSTリクエスト(JSON)
	 */
	public function do_json($url){
	}
	/**
	 * GETリクエストでダウンロードする
	 */
	public function do_download($url, string $filename){
	}
	/**
	 * POSTリクエストでダウンロードする
	 */
	public function do_post_download($url, string $filename){
	}
	/**
	 * bodyを解析し配列として返す
	 * @return mixed
	 */
	public function json(?string $name=null){
	}
	/**
	 * bodyを解析し配列として返す
	 */
	public function xml(?string $name=null): \testman\Xml{
		return new \testman\Xml();
	}
	/**
	 * エラーがあるか
	 */
	public function has_error(string $type): bool{
        return false;
	}
	public function find_get(string $name){
	}
}

class Xml{
	/**
	 * @return array<self>
	 */
	public function find($path=null, int $offset=0, int $length=0): array{
		return [];
	}
	public function find_get(string $path, int $offset=0): self{
		return new self();
	}
	public function in_attr(string $name, ?string $default=null): ?string{
		return '';
	}
	public function name(): string{
		return '';
	}
	public function value(): string{
		return '';
	}
}

<?php
namespace testman;

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
	 */
	public function json(?string $name=null): array{
        return [];
	}
	/**
	 * エラーがあるか
	 */
	public function has_error(string $type): bool{
        return false;
	}
}
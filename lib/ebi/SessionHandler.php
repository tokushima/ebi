<?php
namespace ebi;
/**
 * @see http://jp2.php.net/manual/ja/function.session-set-save-handler.php
 */
interface SessionHandler{
	/**
	 * 初期処理
	 * @param $path セッションを格納/取得するパス。
	 * @param $name セッション名
	 */
	public function session_open(string $path, string $name): bool;

	/**
	 * writeが実行された後で実行される
	 */
	public function session_close(): bool;

	/**
	 * データを読み込む
	 */
	public function session_read(string $id);

	/**
	 * データを書き込む
	 */
	public function session_write(string $id, $data): bool;

	/**
	 * 破棄
	 */
	public function session_destroy(string $id): bool;

	/**
	 * 古いセッションを削除する
	 */
	public function session_gc(int $maxlifetime): bool;
}
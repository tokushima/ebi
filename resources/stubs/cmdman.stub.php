<?php
/**
 * cmdman stubs - IDE サポート用の型定義ファイル
 *
 * このファイルは実行されません（phar のオートローダが読むのは src/cmdman/ 以下だけで、
 * ここは読み込まれません）。PhpStorm や VS Code などの IDE での
 * コード補完・型チェックのために使用します。
 *
 * 取り出し方: cmdman --stub > cmdman.stubs.php
 *
 * cmdman のコマンドは素の PHP スクリプトとして書かれ、そこから下記の API を呼び出せます。
 */
namespace cmdman;

/**
 * 標準入出力
 */
class Std{
	/**
	 * 標準入力からの入力を取得する
	 *
	 * @param string      $msg       プロンプトに出すメッセージ
	 * @param string|null $default   未入力時の既定値
	 * @param array       $choice    選択肢（指定するとその中からのみ受け付ける）
	 * @param bool        $multiline 複数行入力にするか
	 * @param bool        $silently  入力を非表示にするか
	 */
	public static function read(string $msg, ?string $default=null, array $choice=[], bool $multiline=false, bool $silently=false): ?string{}

	/**
	 * read のエイリアス。入力を非表示にする（Windows では非表示になりません）
	 */
	public static function silently(string $msg, ?string $default=null, array $choice=[], bool $multiline=false): ?string{}

	/**
	 * 文字列を色装飾して返す（出力はしない）
	 */
	public static function color(string $value, string|bool|null $fmt=null): string{}

	/**
	 * 直前の出力を $len 文字ぶん消す
	 */
	public static function backspace(int $len): void{}

	/**
	 * 改行せずにプリントする
	 */
	public static function print_inline(string $msg, string|int $color=0): void{}

	/**
	 * 色を指定してプリントする
	 */
	public static function println(string $msg='', string $color='0'): void{}

	public static function println_default(string $msg): void{}
	public static function println_white(string $msg): void{}
	public static function println_primary(string $msg): void{}
	public static function println_success(string $msg): void{}
	public static function println_info(string $msg): void{}
	public static function println_warning(string $msg): void{}
	public static function println_danger(string $msg): void{}
}

/**
 * コマンドライン引数
 *
 * docblock の @param で宣言したオプションはローカル変数へ注入されるため、
 * 通常はこのクラスを直接使うのは位置引数（value/values）を取るときです。
 */
class Args{
	/**
	 * 引数の解析を行う（通常は cmdman 本体が呼ぶ）
	 */
	public static function init(int $offset=1): void{}

	/**
	 * オプションの値を返す。未指定なら $default（既定 false）
	 */
	public static function opt(string $name, mixed $default=false): mixed{}

	/**
	 * 同名オプションが複数指定された場合の値をすべて返す
	 */
	public static function opts(string $name): array{}

	/**
	 * 先頭の位置引数を返す
	 */
	public static function value(mixed $default=null): mixed{}

	/**
	 * 位置引数をすべて返す
	 */
	public static function values(): array{}

	/**
	 * 実行中のサブコマンド名を返す
	 */
	public static function cmd(): string{}

	/**
	 * 実行中のスクリプトのパスを返す
	 */
	public static function script(): string{}
}

/**
 * ユーティリティ
 */
class Util{
	/**
	 * exit_wait() の終了コード。repeat コマンドはこれを見て待機後に再実行する
	 */
	public const EXIT_WAIT = 19;

	/**
	 * ファイルから取得する
	 */
	public static function file_read(string $filename): string{}

	/**
	 * ファイルに書き出す
	 */
	public static function file_write(string $filename, ?string $src=null, bool $lock=true): void{}

	/**
	 * ファイルに追記する
	 */
	public static function file_append(string $filename, ?string $src=null, bool $lock=true): void{}

	/**
	 * フォルダを作成する
	 */
	public static function mkdir(string $source, int $permission=0755): bool{}

	/**
	 * 移動する
	 */
	public static function mv(string $source, string $dest): bool{}

	/**
	 * 削除する
	 *
	 * $source がフォルダで $inc_self が false の場合は $source 以下のみ削除する
	 */
	public static function rm(string $source, bool $inc_self=true): void{}

	/**
	 * コピーする（$source がフォルダの場合はそれ以下もコピーする）
	 */
	public static function copy(string $source, string $dest): void{}

	/**
	 * ディレクトリ内のファイル・ディレクトリを走査する
	 */
	public static function ls(string $directory, bool $recursive=false, ?string $pattern=null): \Iterator{}

	/**
	 * 絶対パスを返す
	 */
	public static function path_absolute(?string $a, ?string $b): string{}

	/**
	 * パスの前後にスラッシュの追加／削除を行う
	 */
	public static function path_slash(string $path, ?bool $prefix, ?bool $postfix=null): string{}

	/**
	 * 複数プロセスで処理する（$data の数だけプロセスをフォークする）
	 */
	public static function parallel(callable $callback, array $data): void{}

	/**
	 * エラーとして終了する（終了コード 1）
	 */
	public static function exit_error(): never{}

	/**
	 * 待機を要求して終了する（終了コード EXIT_WAIT。repeat コマンドが待機後に再実行する）
	 */
	public static function exit_wait(): never{}
}

class NotFound extends \Exception{}
class CommandNotFound extends \Exception{}
class AccessDeniedException extends \Exception{}
class InvalidJsonException extends \Exception{}

<?php
/**
 * testman stubs - IDE サポート用の型定義ファイル
 *
 * このファイルは実行されません。PhpStorm や VS Code などの IDE での
 * コード補完・型チェックのために使用します。
 *
 * 生成方法: php testman.phar --stub > testman.stubs.php
 */

// =============================================================================
// グローバル関数
// =============================================================================

namespace {

/**
 * 値が等しいことを検証する
 *
 * 引数が1つの場合は true との比較として扱われる。
 *
 * @param mixed $expectation 期待値（引数1つの場合は真偽値として検証）
 * @param mixed $result      実行結果
 * @param string $msg        失敗時のメッセージ
 * @throws \testman\AssertFailure
 */
function eq($expectation, $result = null, string $msg = 'failure equals'): void {}

/**
 * 値が等しくないことを検証する
 *
 * @param mixed $expectation 期待値
 * @param mixed $result      実行結果
 * @param string $msg        失敗時のメッセージ
 * @throws \testman\AssertFailure
 */
function neq($expectation, $result, string $msg = 'failure not equals'): void {}

/**
 * 文字列中に指定の文字列が含まれることを検証する
 *
 * @param string $keyword           検索文字列
 * @param string|\testman\Xml $result 対象文字列
 * @param string $msg               失敗時のメッセージ
 * @throws \testman\AssertFailure
 */
function meq(string $keyword, $result, string $msg = 'failure match'): void {}

/**
 * 文字列中に指定の文字列が含まれないことを検証する
 *
 * @param string $keyword           検索文字列
 * @param string|\testman\Xml $result 対象文字列
 * @param string $msg               失敗時のメッセージ
 * @throws \testman\AssertFailure
 */
function mneq(string $keyword, $result, string $msg = 'failure not match'): void {}

/**
 * テストを明示的に失敗させる
 *
 * @param string $msg 失敗メッセージ
 * @throws \testman\AssertFailure
 */
function fail(string $msg = 'failure'): void {}

/**
 * Browser インスタンスを生成する
 *
 * @return \testman\Browser
 */
function b(): \testman\Browser {}

} // namespace

// =============================================================================
// testman 名前空間
// =============================================================================

namespace testman {

/**
 * HTTP リクエストを行うブラウザクライアント
 *
 * cURL を使った HTTP テスト用クライアント。
 * Cookie の自動管理、リダイレクト追従、認証などをサポートする。
 *
 * @example
 *   $b = b();
 *   $b->do_get('http://localhost:8000/api/users');
 *   eq(200, $b->status());
 */
class Browser
{
    /**
     * @param string|null $agent       User-Agent 文字列
     * @param int         $timeout     タイムアウト秒数（デフォルト: 30）
     * @param int         $redirect_max 最大リダイレクト回数（デフォルト: 20）
     */
    public function __construct(?string $agent = null, int $timeout = 30, int $redirect_max = 20) {}

    /**
     * タイムアウト秒数を設定する
     *
     * @param int $timeout 秒数
     * @return static
     */
    public function timeout(int $timeout): self {}

    /**
     * 最大リダイレクト回数を設定する
     *
     * @param int $redirect_max 最大回数
     * @return static
     */
    public function redirect_max(int $redirect_max): self {}

    /**
     * User-Agent を設定する
     *
     * @param string $agent User-Agent 文字列
     * @return static
     */
    public function agent(string $agent): self {}

    /**
     * Basic 認証を設定する
     *
     * @param string $user     ユーザ名
     * @param string $password パスワード
     * @return static
     */
    public function basic(string $user, string $password): self {}

    /**
     * Bearer トークンを設定する
     *
     * @param string $token トークン文字列
     * @return static
     */
    public function bearer_token(string $token): self {}

    /**
     * リクエストヘッダを設定する
     *
     * @param string      $key   ヘッダ名
     * @param string|null $value ヘッダ値（null で削除）
     * @return static
     */
    public function header(string $key, ?string $value = null): self {}

    /**
     * Accept: application/debug ヘッダを設定する
     *
     * @return static
     */
    public function set_header_accept_debug(): self {}

    /**
     * Accept: application/json ヘッダを設定する
     *
     * @return static
     */
    public function set_header_accept_json(): self {}

    /**
     * Accept を指定しない（Accept: star/star）
     *
     * @return static
     */
    public function set_header_accept_none(): self {}

    /**
     * リクエストパラメータを設定する
     *
     * @param string $key   パラメータ名
     * @param mixed  $value 値（bool は 'true'/'false' に変換される）
     * @return static
     */
    public function vars(string $key, $value = null): self {}

    /**
     * ファイルアップロード用パラメータを設定する
     *
     * @param string $key      パラメータ名
     * @param string $filename ファイルパス
     * @return static
     */
    public function file_vars(string $key, string $filename): self {}

    /**
     * パラメータが設定されているか確認する
     *
     * @param string $key パラメータ名
     * @return bool
     */
    public function has_vars(string $key): bool {}

    /**
     * cURL オプションを直接設定する
     *
     * @param string $key   CURLOPT_* 定数
     * @param mixed  $value 値
     * @return static
     */
    public function setopt(string $key, $value): self {}

    /**
     * HEAD リクエストを送信する
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_head($url): self {}

    /**
     * GET リクエストを送信する
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_get($url): self {}

    /**
     * POST リクエストを送信する
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_post($url): self {}

    /**
     * PUT リクエストを送信する
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_put($url): self {}

    /**
     * DELETE リクエストを送信する
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_delete($url): self {}

    /**
     * RAW ボディで POST リクエストを送信する
     *
     * @param string|array $url   URL または ['短縮名', ...パラメータ]
     * @param string       $value リクエストボディ
     * @return static
     */
    public function do_raw($url, string $value): self {}

    /**
     * vars() で設定した値を JSON エンコードして POST リクエストを送信する
     *
     * Content-Type: application/json が自動設定される。
     *
     * @param string|array $url URL または ['短縮名', ...パラメータ]
     * @return static
     */
    public function do_json($url): self {}

    /**
     * GET リクエストでファイルをダウンロードする
     *
     * @param string|array $url      URL または ['短縮名', ...パラメータ]
     * @param string       $filename 保存先ファイルパス
     * @return static
     */
    public function do_download($url, string $filename): self {}

    /**
     * POST リクエストでファイルをダウンロードする
     *
     * @param string|array $url      URL または ['短縮名', ...パラメータ]
     * @param string       $filename 保存先ファイルパス
     * @return static
     */
    public function do_post_download($url, string $filename): self {}

    /**
     * レスポンスボディを返す
     *
     * @return string
     */
    public function body(): string {}

    /**
     * HTTP ステータスコードを返す
     *
     * @return int
     */
    public function status(): int {}

    /**
     * リダイレクト後の最終 URL を返す
     *
     * @return string
     */
    public function url(): string {}

    /**
     * レスポンスヘッダを文字列で返す
     *
     * @return string
     */
    public function response_headers(): string {}

    /**
     * レスポンスヘッダを連想配列で返す
     *
     * @return array<string, string>
     */
    public function explode_head(): array {}

    /**
     * Cookie を返す
     *
     * @return array
     */
    public function cookies(): array {}

    /**
     * レスポンスボディを XML として解析する
     *
     * @param string|null $name タグ名（省略時は最初のタグ）
     * @return \testman\Xml
     * @throws \testman\NotFoundException
     */
    public function xml(?string $name = null): \testman\Xml {}

    /**
     * レスポンスボディを JSON として解析してパスで値を返す
     *
     * @param string|null $name パス（'key/nested/value' 形式、省略時は全体）
     * @return mixed
     */
    public function json(?string $name = null) {}

    /**
     * レスポンスにエラーが含まれることを検証する
     *
     * Conf の browser_has_error_func が設定されていればそれを使用。
     * 未設定の場合は JSON/XML の error 要素を検索する。
     *
     * @param string $type エラー種別
     * @throws \testman\NotFoundException エラーが見つからない場合
     */
    public function has_error(string $type): void {}

    /**
     * レスポンスボディから要素を検索して返す
     *
     * Conf の browser_find_func が設定されていればそれを使用。
     * 未設定の場合は JSON/XML から自動判別して検索する。
     *
     * @param string $name パスまたは要素名
     * @return mixed
     */
    public function find_get(string $name) {}

    /**
     * リクエストの記録を開始し、以前の記録を返す
     *
     * @return array 以前の記録済みリクエスト
     */
    public static function start_record(): array {}

    /**
     * リクエストの記録を終了し、記録されたリクエストを返す
     *
     * @return array 記録済みリクエスト
     */
    public static function stop_record(): array {}

    /**
     * デバッグモードを有効化する（Accept: application/debug を自動設定）
     *
     * @param bool $debug_mode デバッグモード
     */
    public static function debug(bool $debug_mode = true): void {}
}

/**
 * XML 要素を表すクラス
 *
 * XML の生成・解析・操作を行う。
 *
 * @example
 *   // 生成
 *   $xml = new \testman\Xml('user', 'John');
 *   echo $xml->get(); // <user>John</user>
 *
 *   // 解析
 *   $xml = \testman\Xml::extract($html, 'div');
 *   foreach ($xml->find('item') as $item) {
 *       echo $item->value();
 *   }
 */
class Xml implements \IteratorAggregate
{
    /**
     * @param string|object|null $name  タグ名またはオブジェクト（オブジェクト時はクラス名がタグ名）
     * @param mixed              $value 値（文字列、配列、Xml インスタンス）
     */
    public function __construct($name = null, $value = null) {}

    /**
     * 値が空の場合に自己終了タグ（`<tag />`）にするか設定する
     *
     * @param bool $bool false にすると `<tag></tag>` 形式
     * @return static
     */
    public function close_empty(bool $bool): self {}

    /**
     * 特殊文字をエスケープ（CDATA）するか設定する
     *
     * @param bool $bool false にするとエスケープしない
     * @return static
     */
    public function escape(bool $bool): self {}

    /**
     * 解析元の生文字列を返す
     *
     * @return string
     */
    public function plain(): string {}

    /**
     * 子要素検索時の現在位置を返す
     *
     * @return int
     */
    public function cur(): int {}

    /**
     * タグ名を取得・設定する
     *
     * @param string|null $name 設定する場合はタグ名、取得する場合は省略
     * @return string|null
     */
    public function name(?string $name = null): ?string {}

    /**
     * 値を取得・設定する
     *
     * @param mixed ...$args 設定する場合は値を渡す、取得する場合は省略
     * @return string|null
     */
    public function value(...$args): ?string {}

    /**
     * 値または属性を追加する
     *
     * 引数が2つの場合は属性の追加（`attr()` と同等）。
     * 引数が1つの場合は値に追記。
     *
     * @param mixed ...$args
     * @return static
     */
    public function add(...$args): self {}

    /**
     * 属性値を取得する
     *
     * @param string      $name    属性名（大文字小文字を区別しない）
     * @param string|null $default 属性が存在しない場合のデフォルト値
     * @return string|null
     */
    public function in_attr(string $name, ?string $default = null): ?string {}

    /**
     * 属性を削除する
     *
     * 引数なしで全属性を削除。
     *
     * @param string ...$args 削除する属性名（複数指定可）
     */
    public function rm_attr(...$args): void {}

    /**
     * 属性が存在するか確認する
     *
     * @param string $name 属性名
     * @return bool
     */
    public function is_attr(string $name): bool {}

    /**
     * 属性を設定する
     *
     * @param string $name  属性名
     * @param mixed  $value 属性値（bool は 'true'/'false' に変換）
     * @return static
     */
    public function attr(string $name, $value): self {}

    /**
     * XML 文字列を返す
     *
     * @param string|null $encoding エンコーディング（指定時は XML 宣言を付加）
     * @param bool        $format   整形出力するか
     * @param string      $indent_str インデント文字（デフォルト: タブ）
     * @return string
     */
    public function get(?string $encoding = null, bool $format = false, string $indent_str = "\t"): string {}

    /**
     * 子要素を検索するイテレータを返す
     *
     * パスは `/` 区切りで指定。`|` で複数タグを同時検索可能。
     *
     * @param string|array|null $path   タグ名、パス（`parent/child`）、または複数タグ（`tagA|tagB`）
     * @param int               $offset 開始位置
     * @param int               $length 取得件数（0 で無制限）
     * @return \testman\XmlIterator
     */
    public function find($path = null, int $offset = 0, int $length = 0): \testman\XmlIterator {}

    /**
     * 子要素の件数を返す
     *
     * @param string $name   タグ名
     * @param int    $offset 開始位置
     * @param int    $length 取得件数（0 で無制限）
     * @return int
     */
    public function find_count(string $name, int $offset = 0, int $length = 0): int {}

    /**
     * 子要素を1件取得する
     *
     * @param string $path   タグ名またはパス
     * @param int    $offset 開始位置
     * @return static
     * @throws \testman\NotFoundException 要素が見つからない場合
     */
    public function find_get(string $path, int $offset = 0): self {}

    /**
     * パスで指定した要素の値を置換した新しいインスタンスを返す
     *
     * @param string $path  置換対象のパス
     * @param string $value 置換後の値
     * @return static
     */
    public function replace(string $path, string $value): self {}

    /**
     * 子要素を配列または値として展開する
     *
     * @return mixed 子要素がある場合は配列、ない場合は string|null
     */
    public function children() {}

    /**
     * 匿名タグとしてインスタンスを生成する
     *
     * @param string $value XML 文字列
     * @return static
     */
    public static function anonymous(string $value): self {}

    /**
     * 文字列からタグを検出してインスタンスを返す
     *
     * @param string|null $plain  対象文字列
     * @param string|null $name   タグ名（省略時は最初のタグ）。パス指定も可能（`parent/child`）
     * @param int         $offset 検索開始位置
     * @return static
     * @throws \testman\NotFoundException タグが見つからない場合
     */
    public static function extract(?string $plain = null, ?string $name = null, int $offset = 0): self {}

    /**
     * XML 文字列を整形する
     *
     * @param string $src        整形対象の XML 文字列
     * @param string $indent_str インデント文字（デフォルト: タブ）
     * @param int    $depth      初期インデント深さ
     * @return string
     */
    public static function format(string $src, string $indent_str = "\t", int $depth = 0): string {}

    /**
     * 対象タグを検索してコールバックの戻り値で置換した文字列を返す
     *
     * 最初にマッチした要素のみ置換する。
     *
     * @param string   $src  対象文字列
     * @param string   $name タグ名
     * @param callable $func コールバック（Xml を受け取り置換値を返す）
     * @return string
     */
    public static function find_replace(string $src, string $name, callable $func): string {}

    /**
     * 対象タグをすべて検索してコールバックの戻り値で置換した文字列を返す
     *
     * @param string   $src  対象文字列
     * @param string   $name タグ名
     * @param callable $func コールバック（Xml を受け取り置換値を返す）
     * @return string
     */
    public static function find_replace_all(string $src, string $name, callable $func): string {}
}

/**
 * XML 要素のイテレータ
 *
 * `Xml::find()` の戻り値として使用される。
 * foreach で各 Xml インスタンスを順に取得できる。
 */
class XmlIterator implements \Iterator
{
    public function __construct($tag_name, $value, $offset, $length) {}

    /** @return string タグ名 */
    public function key(): string {}

    /** @return \testman\Xml 現在の XML 要素 */
    public function current(): \testman\Xml {}

    public function valid(): bool {}
    public function next(): void {}
    public function rewind(): void {}
}

/**
 * JSON 文字列を操作するクラス
 *
 * @example
 *   $json = new \testman\Json('{"user":{"name":"John"}}');
 *   eq('John', $json->find('user/name'));
 */
class Json
{
    /**
     * JSON 文字列からインスタンスを生成する
     *
     * @param string $json JSON 文字列
     */
    public function __construct(string $json) {}

    /**
     * パスで値を取得する
     *
     * @param string|null $name パス（`key/nested` 形式、省略時は全体）
     * @return mixed
     * @throws \testman\NotFoundException パスが見つからない場合
     */
    public function find(?string $name = null) {}

    /**
     * 値を JSON 文字列にエンコードする
     *
     * @param mixed $val    エンコードする値
     * @param bool  $format 整形出力するか
     * @return string
     */
    public static function encode($val, bool $format = false): string {}

    /**
     * JSON 文字列をデコードする
     *
     * @param string $json JSON 文字列
     * @return mixed
     */
    public static function decode(string $json) {}
}

/**
 * テスト設定を管理するクラス
 *
 * @example
 *   // testman.settings.php
 *   \testman\Conf::set([
 *       'urls' => ['api' => 'http://localhost:8000/%s'],
 *       'ssl-verify' => false,
 *   ]);
 */
class Conf
{
    /**
     * 設定を一括登録する
     *
     * @param array $conf 設定の連想配列
     */
    public static function set(array $conf): void {}

    /**
     * 設定値を取得する
     *
     * @param string $name    設定キー
     * @param mixed  $default デフォルト値
     * @return mixed
     */
    public static function get(string $name, $default = null) {}

    /**
     * 設定ファイル（testman.{name}）が存在するか確認する
     *
     * @param string $name ファイル名（拡張子含む）
     * @return string|null パス（存在しない場合は null）
     */
    public static function has_settings(string $name): ?string {}

    /**
     * 設定ファイルのパスを返す
     *
     * @param string $name ファイル名
     * @return string
     */
    public static function settings_path(string $name): string {}
}

/**
 * テスト用リソースファイルへのアクセスを提供するクラス
 *
 * `testman.resources/` ディレクトリ配下のファイルを参照する。
 */
class Resource
{
    /**
     * リソースファイルの絶対パスを返す
     *
     * @param string $file ファイル名（testman.resources/ からの相対パス）
     * @return string 絶対パス
     * @throws \testman\NotFoundException ファイルが見つからない場合
     */
    public static function path(string $file): string {}
}

/**
 * アサーション失敗例外
 */
class AssertFailure extends \Exception
{
    /**
     * 期待値と実際の値を設定する
     *
     * @param mixed $expectation 期待値
     * @param mixed $result      実際の値
     * @return static
     */
    public function ab($expectation, $result): self {}

    /** @return bool 期待値/実際の値が設定されているか */
    public function has(): bool {}

    /** @return mixed 期待値 */
    public function expectation() {}

    /** @return mixed 実際の値 */
    public function result() {}
}

/** XML/JSON 要素が見つからない場合の例外 */
class NotFoundException extends \Exception {}

/** setup ファイルで宣言された変数が定義されていない場合の例外 */
class DefinedVarsRequireException extends \Exception {}

/** setup ファイルで宣言された変数の型が一致しない場合の例外 */
class DefinedVarsInvalidTypeException extends \Exception {}

/** 引数が不正な場合の例外 */
class InvalidArgumentException extends \Exception {}

/** 再帰上限を超えた場合の例外 */
class RetryLimitOverException extends \Exception {}

} // namespace testman

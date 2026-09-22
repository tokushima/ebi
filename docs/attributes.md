ebi Attribute リファレンス
==========================

ebi ではルーティング・入力検証・API ドキュメント（OpenAPI）・モデル定義・フロー（MCP）を、
すべて PHP 8 の Attribute で宣言する。Attribute はすべて `\ebi\Attribute` 名前空間にあり、
読み取りは `\ebi\AttributeReader` に集約されている。

```php
use ebi\Attribute\Parameter;
use ebi\Attribute\Response;

class OrderApi extends \ebi\app\Request{
	#[Parameter(name:'code', type:'string', require:true)]
	#[Response(name:'order', type: \App\Model\Order::class)]
	public function detail(){
		// ...
	}
}
```


早見表
------

| Attribute | 付ける対象 | 複数可 | 主な解釈者 | 用途 |
|---|---|---|---|---|
| [`Route`](#route) | メソッド | – | `\ebi\App` | URL・遷移先 |
| [`HttpMethod`](#httpmethod) | メソッド | – | `\ebi\App` | HTTP メソッド制限 |
| [`Login`](#login) | クラス | – | `\ebi\App` | 認証要件 |
| [`S2s`](#s2s) | クラス | – | `\ebi\Dt\SourceAnalyzer` | サーバ間通信の印 |
| [`Parameter`](#parameter) | メソッド | ○ | `\ebi\app\Request` / `\ebi\Dt\OpenApi` | リクエストパラメータ |
| [`OneOf`](#oneof) | メソッド | ○ | `\ebi\app\Request` / `\ebi\Dt\OpenApi` | 排他必須（ちょうど1つ） |
| [`Response`](#response) | メソッド | ○ | `\ebi\Dt\OpenApi` | result 内のフィールド |
| [`ResponseBody`](#responsebody) | メソッド | – | `\ebi\Dt\OpenApi` | 200 ボディ全体 |
| [`ErrorResponse`](#errorresponse) | メソッド | ○ | `\ebi\Dt\OpenApi` | エラー応答 |
| [`Prop`](#prop) | プロパティ / クラス | ○ | `\ebi\Obj` / `\ebi\Dao` / `\ebi\Validator` | プロパティの型と制約 |
| [`Table`](#table) | クラス | – | `\ebi\Dao` | テーブル設定 |
| [`ReadonlyModel`](#readonlymodel) | クラス | – | `\ebi\Dao` | 読み取り専用モデル |
| [`FlowToken`](#flowtoken) | クラス | ○ | `\ebi\Dt\OpenApi` / `\ebi\Dt\Mcp` | 生産者なしトークンの定義 |
| [`FlowProduces`](#flowproduces) | メソッド | ○ | 同上 | 成立させる状態 |
| [`FlowRequires`](#flowrequires) | メソッド | ○ | 同上 | 前提となる状態 |
| [`FlowFollows`](#flowfollows) | メソッド | ○ | 同上 | 順序ヒント |
| [`FlowGate`](#flowgate) | メソッド | ○ | 同上 | 属性条件の前提 |
| [`Batch`](#batch) | メソッド | – | 同上 | バッチ handler |


共通ルール
----------

### 型の書き方（`\ebi\T`）

`type:` には `\ebi\T` の enum か、その値と同じ文字列を書く。クラス型は `Foo::class` を渡す。

| `\ebi\T` | 文字列 | PHP 型 | 備考 |
|---|---|---|---|
| `T::String` | `'string'` | `string` | 既定。CRLF は除去される |
| `T::Text` | `'text'` | `string` | 改行を保つ本文 |
| `T::Int` | `'int'` | `int` | |
| `T::Float` | `'float'` | `float` | `decimal_places` で桁指定 |
| `T::Bool` | `'bool'` | `bool` | |
| `T::Datetime` | `'datetime'` | `int` | UNIX 時刻 |
| `T::Date` | `'date'` | `int` | |
| `T::Time` | `'time'` | `int` | |
| `T::Intdate` | `'intdate'` | `int` | `YYYYMMDD` |
| `T::Serial` | `'serial'` | `int` | 連番（Dao） |
| `T::Email` | `'email'` | `string` | |
| `T::Alnum` | `'alnum'` | `string` | `additional_chars` で許可文字を追加 |
| `T::File` | `'file'` | `mixed` | アップロードファイル |
| `T::Mixed` | `'mixed'` | `mixed` | |
| `T::Arr` | `'array'` | `array` | 連番配列。`items` と併用 |
| `T::Map` | `'map'` | `array` | `map<string, T>`。OpenAPI では `additionalProperties` |

PHP 型との対応は `\ebi\T::phpType()` が単一の真実の源になっている。

### `items` — 要素型とコンテナ表記

`type:'array'` / `type:'map'` の要素型は `items:` で指定する。`items` は
**単一要素**（プレーンな型文字列 `'string'` / クラス `X::class` / `\ebi\T`）か、
**1段深くする配列**（`[X]`）を受ける。どれも内部では同じ正準形（[後述](#内部表現type--attr)）へ畳まれる。

```php
#[Parameter(name:'tags',     type:'array', items:'string')]          // string[]
#[Parameter(name:'sections', type:'map',   items: Section::class)]   // map<string, Section>
#[Parameter(name:'pages',    type:'map',   items:[Block::class])]    // map<string, Block[]>
#[Parameter(name:'grid',     type:'array', items:['int'])]           // int[][]
```

**多段は `items` を配列で包んで表す。** `[X::class]` = `X[]`、`[[X::class]]` = `X[][]`。
クラス型を `::class` のまま多次元宣言できる。コンテナは `type:'array'` / `type:'map'` と `items` でのみ表し、
`'X[]'` / `'X{}'` のような**サフィックス文字列は使わない**（内部では互換のため受理するが、記述はしない）。

要素がクラス型の場合、`\ebi\app\Request` はリクエスト入力を
インスタンス化せず**連想配列の構造として**検証する（入力は常に連想配列で `instanceof` を満たせないため）。

### 内部表現（`type` + `attr`）

`\ebi\AttributeReader` が返すメタでは、`items` は残らず **`type`（基底型）+ `attr`（コンテナ種別列）**
に畳まれる。`attr` は外側から内側へ `a`（配列）/ `h`（連想）を並べた文字列で、**長さがそのまま段数**になる。

| 宣言 | メタ |
|---|---|
| `type:'string'` | `type:'string'` |
| `type:'array', items:'string'` | `type:'string'`, `attr:'a'` |
| `type:'map', items:'int'` | `type:'int'`, `attr:'h'` |
| `type:'array', items:['int']` | `type:'int'`, `attr:'aa'` |
| `type:'array', items:'int[]'` | `type:'int'`, `attr:'aa'` |
| `type:'map', items:['int']` | `type:'int'`, `attr:'ha'` |
| `type:'array'`（items なし） | `type:'array'` |

`Prop` / `Parameter` / `Response` / `ResponseBody` で規則は共通。この表現のおかげで
`map<string, int[]>` のような配列と連想の混在も、3次元以上も1つの形で表せる。

`\ebi\Validator::type()` は正準形に加えて旧形式（`type:'array'` + `items`、`'X[]'` サフィックス）も
受理するため、手書きのメタ配列を渡している既存コードはそのまま動く。

### 未指定（null）の扱い

多くのオプションは `null` を「未指定」として扱い、メタ情報に出力しない。
`Prop` では trait / 親クラスの値を打ち消すために `false` を明示することがある
（例: 親の `require:true` を `require:false` で解除）。


エンドポイント定義
------------------

### Route

URL と遷移先を定義する。対象: **メソッド**、複数不可。

| 引数 | 型 | 説明 |
|---|---|---|
| `suffix` | `?string` | 生成 URL の末尾に付ける文字列（`.json` 等） |
| `name` | `?string` | URL とマップ名のベース（既定はメソッド名）。マップ名は `<親マップ名>/<name>` になる |
| `secure` | `?bool` | `true` で https を必須にする |
| `after` | `?string` | アクション実行後のリダイレクト先（マップ名） |
| `post_after` | `?string` | POST のときだけ `after` より先に評価される遷移先 |
| `query` | `?array` | `after` / `post_after` へ引き継ぐクエリ文字列 `[名前 => 値]` |
| `redirect` | `?string` | アクションを実行せずリダイレクトする |

`query` の値は `'@xxx'` 記法で result 変数 `xxx` を参照できる。

```php
#[Route(suffix:'.json', name:'user_list')]
public function index(){}

#[Route(after:'item_info', query:['client_id' => '@client_id'])]
public function create(){}
```

### HttpMethod

HTTP メソッドを制限する。対象: **メソッド**、複数不可。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `method` | `string` | `'GET'` | 許可する HTTP メソッド |

```php
#[HttpMethod('POST')]
public function create(){}
```

### Login

認証要件を宣言する。対象: **クラス**、複数不可。

| 引数 | 型 | 説明 |
|---|---|---|
| `type` | `?string` | ユーザモデルのクラス名 |
| `user_role` | `string\|array\|null` | 要求するロール |

```php
#[Login(type: 'ebi\User')]
class UserApi extends \ebi\app\Request{}
```

このクラスのエンドポイントには、フロー上 `session.user` の前提が自動で付く
（[FlowRequires](#flowrequires) を明示的に書く必要はない）。

### S2s

サーバ間通信（S2S）のエンドポイントであることを示す印。対象: **クラス**、複数不可。引数なし。
`\ebi\Dt\SourceAnalyzer` がドキュメント上の区別に使う。

```php
#[S2s]
class PaymentWebhook extends \ebi\app\Request{}
```


入力
----

### Parameter

リクエストパラメータを定義する（OpenAPI の `parameters` 相当）。対象: **メソッド**、複数可。
`\ebi\app\Request` の入力検証と `\ebi\Dt\OpenApi` のスキーマ生成の両方が読む。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `name` | `string` | 必須 | パラメータ名 |
| `type` | `\ebi\T\|string` | `T::String` | 型。クラス型は `Foo::class` |
| `items` | `\ebi\T\|string\|array\|null` | `null` | 要素型（[items の記法](#items--要素型とコンテナ表記)） |
| `summary` | `?string` | `null` | 説明 |
| `require` | `bool` | `false` | 必須か |
| `min` | `int\|float\|null` | `null` | 数値は値、文字列は文字数の下限 |
| `max` | `int\|float\|null` | `null` | 同上の上限 |
| `format` | `?string` | `null` | OpenAPI の format。`'binary'` でファイルアップロード |
| `deprecated` | `bool` | `false` | 非推奨 |
| `enum` | `array\|string\|null` | `null` | backed enum の FQCN（推奨）または `[値 => ラベル]` |
| `enum_subset` | `?string` | `null` | `enum` が enum クラスのとき、部分集合を返す static メソッド名 |

```php
#[Parameter(name:'email', type:'string', require:true)]
#[Parameter(name:'age',   type:'int', min:0, max:150)]
#[Parameter(name:'file',  type:'string', format:'binary', require:true)] // multipart/form-data
public function create(){}
```

`enum` は backed enum の FQCN を渡すのが推奨。値とラベルの単一ソースになる。
リクエストでは一部の値だけ許したい場合に `enum_subset` で絞り込む。

### OneOf

列挙したパラメータのうち**ちょうど1つ**が必須（排他必須）。対象: **メソッド**、複数可。

| 引数 | 型 | 説明 |
|---|---|---|
| `props` | `string[]` | 対象パラメータ名 |

```php
#[Parameter(name:'id',    type:'int')]
#[Parameter(name:'code',  type:'string')]
#[Parameter(name:'email', type:'string')]
#[OneOf(['id', 'code', 'email'])]
public function search(){}
```

実行時、0 個なら `RequiredException`、2 個以上なら `InvalidArgumentException`（いずれも入力エラー＝422）。
OpenAPI には `x-required-one` として出力し、body がある場合は JSON Schema の `oneOf(required)` でも表現する。

> これは 1 リクエスト内のパラメータに対する制約であり、
> 別エンドポイントが生産するトークンを消費する [`FlowRequires`](#flowrequires) とは全く別物。


出力
----

### Response

`result` オブジェクト内の**名前付きフィールド**を定義する。対象: **メソッド**、複数可。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `name` | `string` | 必須 | フィールド名 |
| `type` | `\ebi\T\|string` | `T::Mixed` | 型 |
| `items` | `\ebi\T\|string\|array\|null` | `null` | 要素型 |
| `summary` | `?string` | `null` | 説明 |
| `deprecated` | `bool` | `false` | 非推奨 |
| `required` | `bool` | `true` | `result` 内にキーが必ず存在するか |
| `nullable` | `?bool` | `null` | 未指定は nullable ON 扱い。非 null 確定なら `false` |

`required` と `nullable` は独立した2軸で、モデル層スキーマと同じ既定を持つ。
条件付きで省略されるキーは `required:false`、値が必ず入るなら `nullable:false` を明示する。

```php
#[Response(name:'user', type: \App\Model\User::class)]
public function show(){}
```

### ResponseBody

200 レスポンスの**ボディ全体**のスキーマを定義する。対象: **メソッド**、複数不可。

`result{}` ラップに収まらない応答に使う。

- bare 配列（配列がそのままボディ）
- 単一オブジェクト
- バイナリ（画像 / PDF 等）

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `type` | `\ebi\T\|string` | `T::Mixed` | 型 |
| `items` | `\ebi\T\|string\|array\|null` | `null` | 要素型 |
| `summary` | `?string` | `null` | 説明 |
| `deprecated` | `bool` | `false` | 非推奨 |
| `nullable` | `?bool` | `null` | 未指定は nullable ON 扱い |
| `format` | `?string` | `null` | `'binary'` でバイナリ応答 |
| `mediaType` | `?string` | `null` | `format:'binary'` のときの MIME（既定 `application/octet-stream`） |

ボディ全体には「キーが存在するか」の概念が無いため `required` は持たない。

```php
// bare 配列ボディ
#[ResponseBody(type:'array', items:'\App\Model\Kit', nullable:false, summary:'キットの配列')]

// 単一オブジェクトボディ
#[ResponseBody(type: \App\Model\Foo::class, nullable:false)]

// バイナリ応答
#[ResponseBody(format:'binary', mediaType:'image/jpeg', summary:'プレビュー画像')]
```

> **`Response` との併記は不可。** 「ボディ全体か、result 内の名前付きフィールドか」は排他。
> 併記した場合は実行時例外ではなく、Dt 画面のスペック生成時に `x-skipped` として理由付きで警告表示される。

### ErrorResponse

エラー応答を宣言する。対象: **メソッド**、複数可。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `status` | `int` | 必須 | HTTP ステータス |
| `description` | `string` | `''` | 説明 |

```php
#[ErrorResponse(status:404, description:'紹介コードが不正な場合')]
#[ErrorResponse(status:409, description:'既に紹介コードが登録されている場合')]
public function set_referral_code(): void{}
```


モデル層
--------

### Prop

プロパティの型と制約を定義する（`\ebi\Obj` / `\ebi\Dao` 共通）。
対象: **プロパティ / クラス**、複数可。

**付けるのは `type:`（セマンティック型）か option が要るときだけ。**
型と nullable は PHP の型宣言、説明は PHPDoc に書く。空の `#[Prop]` や `summary` だけの `#[Prop]` は付けない。

```php
protected ?int $member_id = null;                        // プレーン型は #[Prop] 不要
#[Prop(expose:false)] protected ?int $inner_id = null;   // option が要るときだけ
#[Prop(type:'text')] protected ?string $note = null;     // 改行を保つ本文
#[Prop(type:'datetime', auto_now_add:true)] protected ?int $create_date = null;
```

#### 型と nullable の解決

| 書き方 | 結果 |
|---|---|
| `type` 未指定 | PHP の型宣言に委譲。Dao の列型は命名規約（`id`→serial、`create_date`→datetime+auto_now_add 等）が補完 |
| `type` 明示 | PHP で表せないセマンティック型だけ書く。PHP 宣言型は `\ebi\T::phpType()` に一致させる |
| `?T` | nullable（既定。メタに出さない） |
| `T` | `nullable:false` |

#### クラスレベルで付ける場合

`name:` に対象プロパティ名を書くと、**書いたオプションのキーだけが** trait / 親のメタへ重なる
（キー単位のマージ。プロパティの再宣言は不要）。プロパティに直接付ける場合 `name` は `null`。

#### 引数 — Obj / Dao 共通

| 引数 | 型 | 説明 |
|---|---|---|
| `name` | `?string` | クラスレベルで付けるときの対象プロパティ名 |
| `type` | `string` | セマンティック型（`\ebi\T` の値） |
| `items` | `\ebi\T\|string\|array\|null` | `type:'array'/'map'` の要素型 |
| `nullable` | `?bool` | 未指定は PHP 型宣言の `?` から推論 |
| `enum` | `array\|string\|null` | backed enum の FQCN（推奨）または `[値 => ラベル]` |
| `require` | `?bool` | `null`=未指定 / `true`=必須 / `false`=trait・親の `require:true` を打ち消す |
| `min` / `max` | `int\|float\|null` | 数値は値、文字列は文字数 |
| `additional_chars` | `?string` | `type:'alnum'` で英数字に加えて許可する文字 |
| `decimal_places` | `?int` | `float` / `number` の小数桁数（丸め / DDL の NUMERIC 桁） |
| `summary` | `?string` | 説明 |
| `deprecated` | `?bool` | OpenAPI schema に `deprecated:true` を出す |
| `expose` | `?bool` | `false` でハッシュ化・ドキュメント出力から除外 |
| `get` / `set` | `?bool` | アクセサの生成可否 |

#### 引数 — Dao 専用（DB 列としての定義）

| 引数 | 型 | 説明 |
|---|---|---|
| `primary` | `?bool` | 主キー |
| `unique` | `?bool` | 一意制約 |
| `unique_together` | `string\|array\|null` | 複合一意制約 |
| `column` | `?string` | プロパティ名と異なる列名 |
| `via` | `?string` | 別プロパティの結合を流用し、その結合先テーブルの別列を読む |
| `from` | `?array` | 結合の道筋（下記） |
| `extra` | `?bool` | 列にしない（保存対象外の作業用プロパティ） |
| `auto_now` | `?bool` | 更新の度に現在時刻 |
| `auto_now_add` | `?bool` | 新規作成時に現在時刻 |
| `auto_code_add` | `?bool` | 新規作成時にユニークコード |
| `base` | `?string` | コードに使う文字を直接指定 |
| `ctype` | `?string` | `base` 未指定時の文字種 `0`:数字 / `a`:小文字 / `A`:大文字 / `t`:token68 |
| `length` | `?int` | コードの桁数（未指定は `max`→32） |

#### `from` の書き方

ホップの配列で、先頭のローカル列から順に結合を辿る。

| ホップの形 | 意味 |
|---|---|
| `[local, Model::class\|'table', target]` | `local`（現テーブルの列）= `table.target` で結合。次ホップの `local` は `table` 上 |
| `[local, 'table.target']` | テーブルを文字列で指定（モデル無しのフォールバック） |
| `[local, Prop::SELF, selfCol]` | 最終ホップを自テーブル列で閉じる（`local` = 自テーブルの `selfCol`） |

先頭ホップの `local` に `otherprop.col` を置くと、既存プロパティ `otherprop` の結合を再利用し、
その結合先テーブルの `col` から続けて結合する（`via` の多段版）。

```php
// 3ホップ
from: [['client_order_id', PrintTicket::class, 'code'],
       ['delivery_package_id', DeliveryPackage::class, 'id'],
       ['destination_id', Destination::class, 'id']]

// 既存の結合を再利用
from: [['code.book_id', Book::class, 'id']]
```

### Table

Dao クラスのテーブル設定。対象: **クラス**、複数不可。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `name` | `?string` | `null` | テーブル名。未指定はクラス名のスネークケース（継承時は基底クラス名を使い親とテーブルを共有する） |
| `create` | `bool` | `true` | マイグレーションで作成するか |

```php
#[Table(name:'users', create:true)]
class User extends \ebi\Dao{}
```

### ReadonlyModel

Dao クラスを読み取り専用にする。対象: **クラス**、複数不可。引数なし。

```php
#[ReadonlyModel]
class ReadOnlyModel extends \ebi\Dao{}
```


フロー（flow token / MCP）
--------------------------

API を「状態トークンの生産と消費」として機械可読に記述するための Attribute 群。
`\ebi\Dt\OpenApi` が `x-flow-registry`（トークン辞書）と `x-flow-issues`（違反一覧）を生成し、
`\ebi\Dt\Mcp` がそれを plan（実行手順）として提供する。

### 考え方

- **トークン**は `domain.entity[.qualifier]` 形式の名前（`order.code` / `session.user` 等）
- トークンの語彙は、**生産箇所**（[`FlowProduces`](#flowproduces)）か、生産者を持たない場合は
  [`FlowToken`](#flowtoken) が定義する。生産箇所が単一の真実の源
- 消費側は [`FlowRequires`](#flowrequires)（存在）と [`FlowGate`](#flowgate)（属性条件）で前提を書く
- データ依存で表せない順序だけは [`FlowFollows`](#flowfollows) で補う

| Attribute | 役割 |
|---|---|
| `FlowToken` | 生産者を持たないトークンの語彙定義（ambient） |
| `FlowProduces` | このエンドポイントが**成立させる**状態 |
| `FlowRequires` | このエンドポイントの**前提**（トークンが在ること） |
| `FlowGate` | 前提のうち**属性が条件を満たす**こと |
| `FlowFollows` | データ依存では表せない**順序**のヒント |
| `Batch` | HTTP でもユーザ呼び出しでもない**別アクター**の handler |

### FlowToken

生産者を持たないトークンの語彙を宣言する。対象: **クラス**、複数可。
ユーザ入力 / QR / 共有リンク / メール等、API の外で成立する ambient トークンに使う。
所有ドメインのクラスに 1 回ずつ宣言する。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `token` | `string` | 必須 | トークン名 `domain.entity[.qualifier]` |
| `kind` | `string` | `'ambient'` | `'ambient'`（生産者 op 不要） / `'value'` / `'state'` |
| `summary` | `?string` | `null` | 説明 |
| `ambient` | `bool` | `true` | ambient 扱いにするか |
| `reason` | `?string` | `null` | 成立元（下表） |

| `reason` | 意味 |
|---|---|
| `'external'` | 系外 / out-of-band（メール・PIN・QR・共有リンク等）。establisher が無いのが正常 |
| `'session'` | アプリ内 op で張れる。`#[FlowProduces(..., ambient:true)]` の集合が `establishedBy` になる |
| `null` | Dt が establisher の有無から導出（1件以上→session / 0件→external。ただし Lint 警告対象） |

```php
#[FlowToken('product.serial', kind:'ambient', summary:'製造番号（ユーザ入力/QR、API外で成立）')]
#[FlowToken('session.user', kind:'ambient', reason:'session', summary:'ログインセッション')]
class ProductCatalog{}
```

### FlowProduces

このエンドポイントが成立させる状態トークンを宣言する。対象: **メソッド**、複数可。
**この宣言自体がトークンの定義**になる。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `token` | `string` | 必須 | トークン名 |
| `via` | `?string` | `null` | 値の出所。`'response:<name>'`（`#[Response]` 名）/ `'effect'`（値なし副作用） |
| `when` | `string` | `'success'` | 成立条件。`'success'` / `'always'` / 例外クラス名 |
| `summary` | `?string` | `null` | 説明 |
| `kind` | `?string` | `null` | `'value'` / `'state'`。`null` なら `via` から推論（`response:*`→value / その他→state） |
| `ambient` | `bool` | `false` | `true` で plan に段として出さず `establishedBy` にのみ現れる |

```php
#[FlowProduces('order.code', via:'response:code', summary:'大口注文コードを発番')]  // 値トークン
#[FlowProduces('order.canceled', via:'effect', when:'success')]                   // 状態トークン
#[FlowProduces('session.user', via:'effect', kind:'state', ambient:true)]         // ログイン確立
public function create(){}
```

`ambient:true` は login / auth 系のセッション確立に使う。
対象トークンは `#[FlowToken(kind:'ambient', reason:'session')]` で定義済みであること。

### FlowRequires

呼ぶ前に成立していなければならないトークンを宣言する。対象: **メソッド**、複数可。
`token` は `#[FlowProduces]` か `#[FlowToken]` で定義済みである必要がある。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `token` | `string` | 必須 | 前提トークン |
| `bind` | `?string` | `null` | この前提が値を供給する `#[Parameter]` 名 |
| `optional` | `bool` | `false` | `false`=hard（必須） / `true`=soft（順序ヒントのみ） |
| `summary` | `?string` | `null` | 説明 |

`bind` には location プレフィックスも書ける:
`header:Authorization` / `cookie:X` / `query:X` / `path:X` / `body:X`。
`header` / `cookie` は Bearer 等の security scheme 由来を許容する。

```php
#[FlowRequires('order.code', bind:'code')]              // 値トークン
#[FlowRequires('product.serial', bind:'serial_no')]     // ambient（API外で成立）
#[FlowRequires('payment.authorized', optional:true)]    // soft
public function detail(){}
```

### FlowFollows

データ依存では表せない UX 上の順序だけを補助的に宣言する。対象: **メソッド**、複数可。
自分視点の命名で「このメソッドは `endpoint` に**続く**（`endpoint` が先）」。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `endpoint` | `string` | 必須 | 先行して呼ばれる想定の operationId |
| `soft` | `bool` | `true` | `true`=推奨 / `false`=強い順序 |
| `summary` | `?string` | `null` | 説明 |

```php
#[FlowFollows('bulkorder_estimate', soft:true)]
public function create(){}
```

### FlowGate

トークンが「在る」こと（`FlowRequires`）とは別に、トークン**属性が条件を満たす**ことを宣言する。
対象: **メソッド**、複数可。存在と属性条件で役割を分ける。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `token` | `string` | 必須 | 評価対象の属性トークン |
| `in` | `?array` | `null` | 満たすべき値集合（いずれかに一致で通過） |
| `equals` | `mixed` | `null` | 満たすべき単一値（`false` / `0` / `''` も有効値） |
| `bind` | `?string` | `null` | どの `#[Parameter]` が指す対象を評価するか |
| `onFail` | `?string` | `null` | 違反時に発火する例外クラス（`x-throws` と突合） |
| `reason` | `?string` | `null` | 違反理由の説明 |
| `when` | `string` | `'success'` | 評価が有効な条件 |
| `summary` | `?string` | `null` | 説明 |

```php
#[FlowGate(token:'product.category', in:['photobook'], bind:'product_code',
           onFail: InvalidProductException::class, reason:'フォトブック専用')]
#[FlowGate(token:'kit.orderable', equals:true, bind:'kit_id',
           onFail: InvalidProductException::class)]
public function create(){}
```

plan の各段に `gate` として併記される。宣言の不整合は Lint として `x-flow-issues` に出る:
`token` が `#[FlowProduces]` / `#[FlowToken]` で未定義なら **G1**、`onFail` が `x-throws`
（`@throws` または検出された `throw new`）に短縮名で見つからなければ **G8**。

### Batch

cron / daemon / キューワーカー / CLI 等、HTTP でもユーザ呼び出しでもない handler を示す。
対象: **メソッド**、複数不可。

| 引数 | 型 | 既定 | 説明 |
|---|---|---|---|
| `name` | `?string` | `null` | 表示名（既定はメソッド名） |

`FlowRequires` / `FlowProduces` と併用することで、フローに「別アクター（batch）による状態遷移」として参加する。
**呼び出し可能ではない**（`callable:false`）。起動機構や時刻は環境依存なのでソースには持たない。

```php
#[Batch]
#[FlowRequires('payment.authorized')]
#[FlowProduces('payment.settled', via:'effect')]
public static function order_payment(): bool{}
```

Dt は Conf `ebi\Dt@flow_batch_classes` に登録されたクラスの静的メソッドを走査して収集する。

### Lint コード（`x-flow-issues`）

`\ebi\Dt\OpenApi` がフロー宣言を検証し、違反を `x-flow-issues` として出力する。

| コード | 内容 |
|---|---|
| `G1` | `requires` / `gate` の token が未定義（`#[FlowProduces]` も `#[FlowToken]` も無い。typo の可能性） |
| `G2` | hard な `requires` の生産者（`#[FlowProduces]`）が存在しない |
| `G3` | `via:'response:<name>'` に対応する `#[Response]` が無い |
| `G4` | `bind` に対応する `#[Parameter]` が無い |
| `G5` | `follows` の endpoint が operationId として解決できない |
| `G6` | 同一 op で同じ token を require かつ produce（`when` ガード無し） |
| `G7` | `reason:'session'` のトークンに establisher が無い |
| `G8` | `gate` の `onFail` が `x-throws` に宣言されていない |

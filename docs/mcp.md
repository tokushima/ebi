MCP エンドポイントフレームワーク
================================

ebi は `\ebi\App`（HTTP ルーティング）と対になる MCP（Model Context Protocol）エンドポイントを
標準で備える。クラスに `#[McpEndpoint]` を付け、メソッドに `#[McpTool]` を付けるだけで、そのクラスが
1つの MCP エンドポイントになる（`#[Route]` を持つ1クラスが1つのHTTPマウントになるのと同じ関係）。
そのエンドポイントは自クラスの `#[McpTool]` だけを公開する。第三者ブリッジ（npx 等）を介さず
ebi 自身が MCP（JSON-RPC 2.0 / Streamable HTTP）を喋る。

```
\ebi\App : #[Route] を持つ1クラス      → 1つのHTTPマウント     → OpenAPI 自動生成
\ebi\App : #[McpEndpoint] を持つ1クラス → 1つのMCPエンドポイント → inputSchema 自動生成
```

エンドポイントを定義する
------------------------

クラスに `#[McpEndpoint]` を付ける。入力は `#[Parameter]` で宣言し、メソッド本体は通常のアクションと
同じく `in_vars()` で読む（tools/call の `arguments` が in_vars に注入される）。

```php
namespace app;
use ebi\Attribute\{McpEndpoint, McpTool, Parameter};

#[McpEndpoint]
class MaterialMcp extends \ebi\app\Request{
    /**
     * アイテムを検索して一覧取得する
     */
    #[McpTool]  // 説明は docコメント先頭行。#[McpTool('明示的な説明')] でも可
    #[Parameter(name:'query', type:'string', summary:'検索キーワード')]
    #[Parameter(name:'page',  type:'int',    summary:'ページ番号')]
    public function items(): array{
        $query = $this->in_vars('query', '');
        $page  = (int)$this->in_vars('page', 1);
        return ['items' => $list];
    }
}
```

### 既存クラスを継承してツールを再利用する

既に `#[McpTool]` を持つクラス（例: HTTP API のアクションクラス）を継承すれば、そのツールを
移動せずそのまま公開できる。`#[McpEndpoint]` を付けるだけ（本体は空でよい）。

```php
namespace app;
#[McpEndpoint]
class MaterialMcp extends \app\Api{}   // Api の #[McpTool] を継承して公開
```

継承した HTTP ルート（`#[Route]`）は MCP マウント配下へ**再公開されない**（App の automap が
`#[McpEndpoint]` を検出し、`#[Route]` を張らず MCP トランスポートへ振り向けるため）。
公開されるのは `#[McpTool]` を持つメソッドだけ。ツールを別クラスに委譲したい場合は
`#[McpEndpoint(tools: \app\Api::class)]` のように対象を指定する（既定は付けたクラス自身）。

### ルートマップに割り当てる

素のクラス指定でマウントする。複数エンドポイントはクラスを増やす。

```php
\ebi\App::run([
    'api'       => ['action' => 'app\\Api'],
    'mcp'       => ['action' => 'app\\MaterialMcp'],  // → POST /mcp
    'mcp/admin' => ['action' => 'app\\AdminMcp'],     // 別エンドポイント = 別クラス
]);
```

### 公開

ルートマップに置いた時点で公開される（有効/無効フラグや認証は持たない）。dev 限定にしたい等は、
マップに置くか否か（例: `mode` や環境別のエントリ）で制御する。認証が要る場合はアクセス制御を
持つ既存クラス（`\ebi\app\Request` 派生で認証を実装したもの）を継承するなど、アプリ側で行う。

（開発用ドキュメントMCP `/dt/mcp` は別サブシステムで、`Conf(\ebi\Dt) の mcp_enabled` で有効化する。）

補足
----

- ツール名は `#[McpTool(name:'...')]`、無指定ならメソッド名。
- `inputSchema` は `#[Parameter]`（と後方互換の `@request` DocBlock）から生成する。モデル型は
  MCP に `$ref` を持てないためインライン展開される。
- 公開されるのは `#[McpTool]` を持つメソッドだけ。継承した `#[Route]` は出ない。
- ツール本体は `in_vars()` を使うため、対象クラスは `\ebi\app\Request`（または派生。既存の API アクション等）であること。
- 戻り値（配列）は MCP の tool result（`content[].text` の JSON）として返る。
- トランスポートは Streamable HTTP（POST の JSON-RPC 2.0）のみ対応。server→client の SSE は非対応
  （GET 等には 405）。バッチ（JSON 配列）と単一リクエストの両方に対応する。
- プロトコルは `initialize` / `tools/list` / `tools/call` / `ping` と通知に対応。プロトコル版は
  `2025-06-18` / `2025-03-26` / `2024-11-05` をネゴシエートする。
- JSON-RPC の純ハンドラ `(new \ebi\McpServer($class))->handle(array $req): ?array` を直接呼べる（テスト用途）。

動作確認
--------

```sh
curl -s -X POST http://localhost:PORT/mcp \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

構成
----

| 部品 | 役割 |
|---|---|
| `\ebi\Attribute\McpEndpoint` | クラスを MCP エンドポイントにする属性。App の automap がこれを検出する |
| `\ebi\Attribute\McpTool` | メソッドを MCP ツールとして公開する属性 |
| `\ebi\McpServer` | MCP ランタイム。対象クラスの #[McpTool] 反映・inputSchema生成・JSON-RPC・ディスパッチ・アクセス制御を内包 |

開発支援ツールの API ドキュメント MCP（`\ebi\Dt\Mcp`、`/dt/mcp`）は別サブシステム（用途が異なり
エントリ全体のAPI表面を対象にする）。データを公開する本フレームワークとは独立している。

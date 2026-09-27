<?php
// \ebi\McpServer: #[McpTool] の探索・inputSchema自動生成・arguments注入ディスパッチ。
// フィクスチャ: test\mcp\Sample（#[McpEndpoint]。list_items / get_item に #[McpTool]、secret は非公開）

$server = new \ebi\McpServer(\test\mcp\Sample::class);
$rpc = function(string $method, array $params) use ($server){
	return $server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
};

// --- initialize: serverInfo が返る ---
$init = $rpc('initialize', ['protocolVersion' => '2025-06-18']);
eq('2025-06-18', $init['result']['protocolVersion']);
eq(true, isset($init['result']['serverInfo']['name']));

// --- tools/list ---
$list = $rpc('tools/list', []);
$tools = [];
foreach($list['result']['tools'] as $t){
	$tools[$t['name']] = $t;
}

// #[McpTool] のあるメソッドだけが公開される（secret は出ない）
eq(true, isset($tools['list_items']));
eq(true, isset($tools['get_item']));
eq(false, isset($tools['secret']));

// description: #[McpTool] 引数が優先 / 無指定は docコメント summary
eq('アイテムを検索して一覧取得する', $tools['list_items']['description']);
eq('アイテム詳細を取得する', $tools['get_item']['description']);

// inputSchema: #[Parameter] から生成
eq('object', $tools['list_items']['inputSchema']['type']);
eq('string', $tools['list_items']['inputSchema']['properties']['query']['type']);
eq('integer', $tools['list_items']['inputSchema']['properties']['page']['type']);
eq('検索キーワード', $tools['list_items']['inputSchema']['properties']['query']['description']);

// require:true は required[] に出る
eq(['id'], $tools['get_item']['inputSchema']['required']);
eq('integer', $tools['get_item']['inputSchema']['properties']['id']['type']);

// --- tools/call: arguments が in_vars に注入され既存アクションがそのまま動く ---
$call = $rpc('tools/call', ['name' => 'list_items', 'arguments' => ['query' => 'cat', 'page' => 3]]);
eq(false, $call['result']['isError']);
$body = json_decode($call['result']['content'][0]['text'], true);
eq('cat', $body['query']);
eq(3, $body['page']);

// --- tools/call: required 欠落は実行前にエラー ---
$miss = $rpc('tools/call', ['name' => 'get_item', 'arguments' => []]);
eq(true, $miss['result']['isError']);

// --- tools/call: required 充足 ---
$ok = $rpc('tools/call', ['name' => 'get_item', 'arguments' => ['id' => 42]]);
eq(false, $ok['result']['isError']);
eq(42, json_decode($ok['result']['content'][0]['text'], true)['id']);

// --- tools/call: 未知ツールはエラー ---
$unknown = $rpc('tools/call', ['name' => 'nope', 'arguments' => []]);
eq(true, $unknown['result']['isError']);

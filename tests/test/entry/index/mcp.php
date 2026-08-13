<?php
// dt の MCP エンドポイント（JSON-RPC 2.0）: API ドキュメントの検索・参照

$url = \testman\Util::url('index::dt/mcp');

$rpc = function(\testman\Browser $b, array $req) use ($url){
	$b->header('Content-Type', 'application/json');
	$b->do_raw($url, json_encode($req));
	return json_decode($b->body(), true);
};
$call = function(\testman\Browser $b, string $name, array $args = []) use ($rpc){
	$res = $rpc($b, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => $args]]);
	return $res['result'];
};

// initialize: バージョンネゴシエーション（対応版はエコー）
$b = b();
$res = $rpc($b, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-03-26']]);
eq(200, $b->status());
eq('2.0', $res['jsonrpc']);
eq('2025-03-26', $res['result']['protocolVersion']);
eq(true, !empty($res['result']['serverInfo']['name']));

// 非対応版の要求は既定（最新の対応版）へフォールバック
$b = b();
$res = $rpc($b, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '1999-01-01']]);
eq('2025-06-18', $res['result']['protocolVersion']);

// tools/list はドキュメント検索・参照ツール（実行系は無い）
$b = b();
$res = $rpc($b, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => []]);
$names = array_map(fn($t) => $t['name'], $res['result']['tools']);
sort($names);
eq(['get_endpoint', 'get_schema', 'list_tags', 'search_endpoints'], $names);

// search_endpoints（空クエリ=全件）→ endpoints 配列が返る
$b = b();
$r = $call($b, 'search_endpoints', []);
eq(false, $r['isError']);
$search = json_decode($r['content'][0]['text'], true);
eq(true, is_array($search['endpoints']));
eq(true, $search['total'] > 0);

// 検索結果の先頭 operationId で get_endpoint
$op_id = $search['endpoints'][0]['operationId'];
$b = b();
$r = $call($b, 'get_endpoint', ['operationId' => $op_id]);
eq(false, $r['isError']);
$detail = json_decode($r['content'][0]['text'], true);
eq($op_id, $detail['operationId']);
eq(true, isset($detail['operation']['responses']));

// method で絞り込み（GET）
$b = b();
$r = $call($b, 'search_endpoints', ['method' => 'get']);
$getonly = json_decode($r['content'][0]['text'], true);
foreach($getonly['endpoints'] as $e){
	eq('GET', $e['method']);
}

// list_tags
$b = b();
$r = $call($b, 'list_tags');
$tags = json_decode($r['content'][0]['text'], true);
eq(true, is_array($tags['tags']));

// get_endpoint 存在しないID → isError
$b = b();
$r = $call($b, 'get_endpoint', ['operationId' => '__no_such_op__']);
eq(true, $r['isError']);

// 不明ツール → isError
$b = b();
$r = $call($b, '__no_such_tool__');
eq(true, $r['isError']);

// notifications/initialized は応答なし(202)
$b = b();
$b->header('Content-Type', 'application/json');
$b->do_raw($url, json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']));
eq(202, $b->status());

// 不明メソッド → JSON-RPC error -32601
$b = b();
$res = $rpc($b, ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'no/such/method', 'params' => []]);
eq(-32601, $res['error']['code']);

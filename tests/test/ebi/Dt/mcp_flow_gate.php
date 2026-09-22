<?php
// Mcp get_flow: #[FlowGate] 付きの op が plan 段に gate を併記して出ること（gate の Mcp 出力側）。
// フィクスチャ: test\dt\GateFlow（make=gate 付きで book.made を produce）

$entry = sys_get_temp_dir() . '/ebi_mcp_gate_entry_' . getmypid() . '.php';
file_put_contents($entry, "<?php\n\\ebi\\Flow::app([\n"
	. "'gateflow/make' => ['name'=>'gateflow_make','action'=>'test\\\\dt\\\\GateFlow::make'],\n"
	. "]);\n");

$mcp = new \ebi\Dt\Mcp($entry);
$rpc = function(string $name, array $args) use ($mcp){
	$res = $mcp->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => $args]]);
	return $res['result'];
};

$r = $rpc('get_flow', ['goal' => 'book.made']);
@unlink($entry);

eq(false, $r['isError']);
$f = json_decode($r['content'][0]['text'], true);

// plan のどこかの段に gate が併記されている（operationId 命名規則に依存しない）
$step = null;
foreach(($f['plan'] ?? []) as $s){
	if(!empty($s['gate'])){
		$step = $s;
	}
}
neq(null, $step);

// gate の内容が属性どおり伝播している
eq('product.category', $step['gate'][0]['token']);
eq(['photobook'], $step['gate'][0]['in']);
eq('product_code', $step['gate'][0]['bind']);

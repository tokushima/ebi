<?php
// #[McpEndpoint] を付けたマウントは automap で #[Route] を張らず、マウント直下が MCP トランスポート(callable)になる。
// 継承した #[Route]（LeakApi::writer/reader）が /mcp 配下へ再公開されない（漏れない）ことを検証する。
// あわせて、継承した #[McpTool] が Server で公開されることも確認する。

$entry = sys_get_temp_dir() . '/ebi_mcp_endpoint_entry_' . getmypid() . '.php';
file_put_contents($entry, "<?php\n\\ebi\\Flow::app([\n"
	. "'api' => ['action' => 'test\\\\mcp\\\\LeakApi'],\n"
	. "'mcp' => ['action' => 'test\\\\mcp\\\\LeakEndpoint'],\n"
	. "]);\n");

$map = \ebi\App::get_map($entry);
$patterns = $map['patterns'] ?? [];
@unlink($entry);

// --- Endpoint マウント: 'mcp' 直下が callable(MCPトランスポート) に振り向く ---
eq(true, isset($patterns['mcp']));
eq(true, is_callable($patterns['mcp']['action']));

// --- 継承した #[Route] が /mcp 配下へ automap されていない（漏れない） ---
eq(false, isset($patterns['mcp/writer']));
eq(false, isset($patterns['mcp/reader']));

// --- 通常の HTTP マウント(api)は従来どおり automap される（対照） ---
eq(true, isset($patterns['api/writer']));
eq(true, isset($patterns['api/reader']));

// --- 継承した #[McpTool] は Server で公開される（extends で再利用できる） ---
$list = (new \ebi\McpServer(\test\mcp\LeakEndpoint::class))->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
$tools = [];
foreach($list['result']['tools'] as $t){
	$tools[$t['name']] = $t;
}
eq(true, isset($tools['reader']));   // #[McpTool] を持つ継承メソッド
eq(false, isset($tools['writer']));  // #[Route] のみ＝ツールではない

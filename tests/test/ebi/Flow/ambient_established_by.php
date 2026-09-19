<?php
// MCP get_flow: ambient トークン(session.user)の establishedBy / reason:session を検証する。
// フィクスチャ: test\flow\FlowAmbientAction（grant=ambient確立 / consume=要求＋flowamb.done生産）

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

// 改修2: get_flow('session.user') は ambient-token として解決し、establishedBy(張り方候補)を返す
$b = b();
$r = $call($b, 'get_flow', ['goal' => 'session.user']);
eq(false, $r['isError']);
$f = json_decode($r['content'][0]['text'], true);
eq('ambient-token', $f['resolvedAs']);
eq('session', $f['reason']);
eq(true, !empty($f['establishedBy']));
// grant(ambient確立者) が establishedBy に含まれる（path で判定）
$paths = array_map(fn($e) => $e['path'] ?? '', $f['establishedBy']);
$hit = false;
foreach($paths as $p){ if(strpos((string)$p, 'flowamb/grant') !== false){ $hit = true; } }
eq(true, $hit);

// 改修1: get_flow('flowamb.done') の inputs に session.user が reason:session + establishedBy 付きで出る
$b = b();
$r = $call($b, 'get_flow', ['goal' => 'flowamb.done']);
eq(false, $r['isError']);
$f2 = json_decode($r['content'][0]['text'], true);
$su = null;
foreach(($f2['inputs'] ?? []) as $in){
	if($in['token'] === 'session.user'){ $su = $in; }
}
neq(null, $su);
eq('session', $su['reason']);
eq(true, !empty($su['establishedBy']));

// ambient 確立者(grant)は plan の段には出ない（establishedBy にのみ現れる）
$plan_paths = array_map(fn($s) => $s['path'] ?? '', $f2['plan'] ?? []);
$grant_in_plan = false;
foreach($plan_paths as $p){ if(strpos((string)$p, 'flowamb/grant') !== false){ $grant_in_plan = true; } }
eq(false, $grant_in_plan);

// list_flows のゴール一覧に ambient の session.user は出ない（producer 扱いしない）／flowamb.done は出る
$b = b();
$r = $call($b, 'list_flows');
$lf = json_decode($r['content'][0]['text'], true);
$goals = array_map(fn($x) => $x['goal'], $lf['flows']);
eq(false, in_array('session.user', $goals, true));
eq(true, in_array('flowamb.done', $goals, true));

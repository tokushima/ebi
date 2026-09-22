<?php
// OpenApi flow gate: #[FlowGate]（述語前提）が x-flow.gate へ載り、G1(token未定義)/G8(onFail未宣言) を検知すること。
// フィクスチャ: test\dt\Gate（ok=宣言済み / missing_throws=onFail未宣言 / unknown_token=未定義トークン）

$entry = sys_get_temp_dir() . '/ebi_openapi_gate_entry_' . getmypid() . '.php';
file_put_contents($entry, "<?php\n\\ebi\\Flow::app([\n"
	. "'gate/ok' => ['name'=>'gate_ok','action'=>'test\\\\dt\\\\Gate::ok'],\n"
	. "'gate/missing' => ['name'=>'gate_missing','action'=>'test\\\\dt\\\\Gate::missing_throws'],\n"
	. "'gate/unknown' => ['name'=>'gate_unknown','action'=>'test\\\\dt\\\\Gate::unknown_token'],\n"
	. "]);\n");

$spec = (new \ebi\Dt\OpenApi($entry))->generate_spec(false, false);
@unlink($entry);

// operationId => x-flow.gate（属性が x-flow へ載ることの確認も兼ねる）
$gate_by_oid = [];
foreach($spec['paths'] as $methods){
	foreach($methods as $op){
		if(isset($op['x-flow']['gate'])){
			$gate_by_oid[$op['operationId']] = $op['x-flow']['gate'];
		}
	}
}

// gate token から operationId を引く（テストは operationId の命名規則に依存しない）
$oid_of_token = function(string $token) use ($gate_by_oid){
	foreach($gate_by_oid as $oid => $gates){
		foreach($gates as $g){
			if(($g['token'] ?? null) === $token){
				return $oid;
			}
		}
	}
	return null;
};

$oid_ok      = $oid_of_token('product.category');
$oid_missing = $oid_of_token('kit.orderable');
$oid_unknown = $oid_of_token('unknown.token');

// 3メソッドとも #[FlowGate] が x-flow へ載っている
eq(true, $oid_ok !== null);
eq(true, $oid_missing !== null);
eq(true, $oid_unknown !== null);

// 属性の各フィールドが欠落なく載る（in/bind/onFail/reason）
$g_ok = $gate_by_oid[$oid_ok][0];
eq(['photobook'], $g_ok['in']);
eq('product_code', $g_ok['bind']);
eq('フォトブック専用', $g_ok['reason']);
eq(true, isset($g_ok['onFail']));

// issues 突合ヘルパ
$issues = $spec['x-flow-issues'] ?? [];
$has_issue = function(string $code, ?string $oid) use ($issues){
	foreach($issues as $i){
		if($i['gate'] === $code && $i['operationId'] === $oid){
			return true;
		}
	}
	return false;
};

// G8: onFail(GateException) を @throws 宣言していない missing_throws で発火する
eq(true, $has_issue('G8', $oid_missing));
// ok は @throws 宣言済み → G8 は出ない
eq(false, $has_issue('G8', $oid_ok));

// G1: 未定義トークンを gate に指定した unknown_token で発火する
eq(true, $has_issue('G1', $oid_unknown));
// #[FlowToken] で定義済みのトークンは G1 を出さない
eq(false, $has_issue('G1', $oid_ok));
eq(false, $has_issue('G1', $oid_missing));

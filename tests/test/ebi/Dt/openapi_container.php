<?php
// OpenApi: コンテナ型（配列 / 連想 / 多段 / 混在）が段数ぶん正しく入れ子になること。
// メタは type=基底型 + attr=コンテナ種別列（外側→内側の 'a'/'h'）で表現される。

$entry = sys_get_temp_dir() . '/ebi_openapi_container_entry_' . getmypid() . '.php';
file_put_contents($entry, "<?php\n\\ebi\\Flow::app(['shape' => ['name' => 'shape', 'action' => 'test\\dt\\Container::shape']]);\n");

$spec = (new \ebi\Dt\OpenApi($entry))->generate_spec(false, false);
@unlink($entry);

$params = [];
foreach($spec['paths'] as $methods){
	foreach($methods as $op){
		foreach(($op['parameters'] ?? []) as $p){
			$params[$p['name']] = $p['schema'] ?? [];
		}
	}
}

// string[]
eq('array', $params['tags']['type']);
eq('string', $params['tags']['items']['type']);

// int[][] … 配列で包んだぶん1段深い
eq('array', $params['grid']['type']);
eq('array', $params['grid']['items']['type']);
eq('integer', $params['grid']['items']['items']['type']);

// map<string, int>
eq('object', $params['dict']['type']);
eq('integer', $params['dict']['additionalProperties']['type']);

// map<string, int[]> … 配列と連想の混在
eq('object', $params['pages']['type']);
eq('array', $params['pages']['additionalProperties']['type']);
eq('integer', $params['pages']['additionalProperties']['items']['type']);

// レスポンス側も同じ規則（string[][]）
$matrix = null;
foreach($spec['paths'] as $methods){
	foreach($methods as $op){
		foreach(($op['responses'] ?? []) as $res){
			foreach(($res['content'] ?? []) as $c){
				$props = $c['schema']['properties']['result']['properties'] ?? $c['schema']['properties'] ?? [];
				if(isset($props['matrix'])){
					$matrix = $props['matrix'];
				}
			}
		}
	}
}
eq(true, $matrix !== null);
eq('array', $matrix['items']['type']);
eq('string', $matrix['items']['items']['type']);

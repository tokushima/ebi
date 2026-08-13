<?php
// OpenApi: エラーレスポンス(4xx)に共通 Error スキーマの content が付くこと

$entry = realpath(__DIR__ . '/../../../index.php');
$spec = (new \ebi\Dt\OpenApi($entry))->generate_spec(false, true);

// components.schemas.Error が定義されている
eq(true, isset($spec['components']['schemas']['Error']));

$err = $spec['components']['schemas']['Error'];
eq('object', $err['type']);
eq('array', $err['properties']['error']['type']);
$item = $err['properties']['error']['items'];
eq('object', $item['type']);
eq(true, isset($item['properties']['message'], $item['properties']['type'], $item['properties']['group']));
eq(['message', 'type'], $item['required']);

// 4xx/5xx レスポンスに content が付く場合は Error を $ref する。2xx は Error を指さない。
$found_4xx_content = false;
foreach($spec['paths'] as $methods){
	foreach($methods as $op){
		foreach(($op['responses'] ?? []) as $status => $resp){
			$ref = $resp['content']['application/json']['schema']['$ref'] ?? null;
			if((int)$status >= 400){
				if(isset($resp['content'])){
					eq('#/components/schemas/Error', $ref);
					$found_4xx_content = true;
				}
			}else{
				// 成功系は Error を指さない
				eq(true, $ref !== '#/components/schemas/Error');
			}
		}
	}
}

// テストアプリには @throws を持つエンドポイントがあるので、少なくとも1件は content 付き4xxが存在する
eq(true, $found_4xx_content);


// openapi_auto_throws: throw new からの自動検出を4xxに載せるか（既定 true）
// ※ \ebi\Conf::set は set-once（既存キーは上書き不可）なので、先に「未設定=既定true」を検証してから false を設定する
// エントリはtestmanにテストとして拾われないよう一時ファイルに生成する（アクションクラスは tests/lib/test/dt/ThrowAuto）
$throw_entry = sys_get_temp_dir() . '/ebi_openapi_throw_entry_' . getmypid() . '.php';
file_put_contents($throw_entry, "<?php\n\\ebi\\Flow::app(['pick' => ['name' => 'pick', 'action' => 'test\\dt\\ThrowAuto::pick']]);\n");

// 既定(未設定=true): @throwsを書いていなくても throw new NotFoundException が 404 として載る
$spec_on = (new \ebi\Dt\OpenApi($throw_entry))->generate_spec(false, false);
$res404 = $spec_on['paths']['/pick']['get']['responses']['404'] ?? null;
eq(true, isset($res404));
eq('#/components/schemas/Error', $res404['content']['application/json']['schema']['$ref'] ?? null);

// false: 明示宣言のみ → 自動検出の404は載らない（ここで初めてConfを設定）
\ebi\Conf::set(['ebi\Dt\OpenApi' => ['openapi_auto_throws' => false]]);
$spec_off = (new \ebi\Dt\OpenApi($throw_entry))->generate_spec(false, false);
eq(false, isset($spec_off['paths']['/pick']['get']['responses']['404']));


// servers: Conf未設定時は、dtを実行中のサーバー（現在のリクエスト）を1件自動補完する
$_SERVER['HTTP_HOST'] = 'openapi.example';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;
$spec_srv = (new \ebi\Dt\OpenApi($throw_entry))->generate_spec(false, false);
eq(true, isset($spec_srv['servers']));
// ベースパスは環境（app_url）依存なので、現在のスキーム+ホストで始まることを検証する
eq(true, strpos($spec_srv['servers'][0]['url'], 'https://openapi.example') === 0);

@unlink($throw_entry);

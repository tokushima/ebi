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


// openapi_catch_aware: 呼び出しグラフ横断でcatchされる例外を4xxから除外する（opt-in）
// ※ openapi_auto_throws=true(既定) のうちに検証する（自動検出throwが対象のため）
$catch_entry = sys_get_temp_dir() . '/ebi_openapi_catch_entry_' . getmypid() . '.php';
file_put_contents($catch_entry, "<?php\n\\ebi\\Flow::app([\n"
	. "'guarded' => ['name' => 'guarded', 'action' => 'test\\dt\\CatchEndpoint::guarded'],\n"
	. "'unguarded' => ['name' => 'unguarded', 'action' => 'test\\dt\\CatchEndpoint::unguarded'],\n"
	. "]);\n");

\ebi\Conf::set(['ebi\Dt\OpenApi' => ['openapi_catch_aware' => true]]);
$spec_catch = (new \ebi\Dt\OpenApi($catch_entry))->generate_spec(false, false);
// guarded: 呼び先のthrowをtry/catchで握りつぶしているので404は載らない
eq(false, isset($spec_catch['paths']['/guarded']['get']['responses']['404']));
// unguarded: 握りつぶしていないので404が載る
eq(true, isset($spec_catch['paths']['/unguarded']['get']['responses']['404']));

@unlink($catch_entry);


// HttpHeader::send_status(NNN) 直接呼び出し → ステータスのみ文書化（bodyは主張しない=content無し）
$status_entry = sys_get_temp_dir() . '/ebi_openapi_status_entry_' . getmypid() . '.php';
file_put_contents($status_entry, "<?php\n\\ebi\\Flow::app(['mc' => ['name' => 'mc', 'action' => 'test\\dt\\StatusEndpoint::methodcheck']]);\n");

$spec_status = (new \ebi\Dt\OpenApi($status_entry))->generate_spec(false, false);
$res405 = $spec_status['paths']['/mc']['get']['responses']['405'] ?? null;
eq(true, isset($res405));                    // 405が載る
eq(false, isset($res405['content']));        // bodyは主張しない（Errorスキーマを付けない）

@unlink($status_entry);


// envelope=true: 例外由来の4xxは HTTP200 に畳まれ、200スキーマが oneOf[成功, Error] になる
$env_entry = sys_get_temp_dir() . '/ebi_openapi_env_entry_' . getmypid() . '.php';
file_put_contents($env_entry, "<?php\n\\ebi\\Flow::app(['pick2' => ['name' => 'pick2', 'action' => 'test\\dt\\ThrowAuto::pick']]);\n");

$spec_env = (new \ebi\Dt\OpenApi($env_entry))->generate_spec(true, false); // envelope=true
$env_res = $spec_env['paths']['/pick2']['get']['responses'];
eq(false, isset($env_res['404']));                    // 例外由来4xxは200に畳まれ消える
$env200 = $env_res['200']['content']['application/json']['schema'] ?? null;
eq(true, isset($env200['oneOf']));                     // 200はoneOf[成功, Error]
$env_has_error = false;
foreach($env200['oneOf'] as $b){
	if(($b['$ref'] ?? '') === '#/components/schemas/Error'){ $env_has_error = true; }
}
eq(true, $env_has_error);

@unlink($env_entry);


// exception_to_status: 例外の実 http_status に基づく（旧: 名前ヒューリスティックで一律422）
// ConnectionException の http_status=503 が正しくマップされる
$conn_entry = sys_get_temp_dir() . '/ebi_openapi_conn_entry_' . getmypid() . '.php';
file_put_contents($conn_entry, "<?php\n\\ebi\\Flow::app(['conn' => ['name' => 'conn', 'action' => 'test\\dt\\ThrowAuto::conn']]);\n");

$spec_conn = (new \ebi\Dt\OpenApi($conn_entry))->generate_spec(false, false);
$conn_res = $spec_conn['paths']['/conn']['get']['responses'];
eq(true, isset($conn_res['503']));   // 503にマップ（旧実装なら422になっていた）
eq(false, isset($conn_res['422']));

@unlink($conn_entry);


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


// ログイン必須(@login_required)エンドポイントの401にも Error の content が付く
// （未認証時は他エラー同様 {"error":[...]} を返すため）
$login_entry = sys_get_temp_dir() . '/ebi_openapi_login_entry_' . getmypid() . '.php';
file_put_contents($login_entry, "<?php\n\\ebi\\Flow::app(['need' => ['name' => 'need', 'action' => 'test\\dt\\NeedLogin::get']]);\n");

$spec_login = (new \ebi\Dt\OpenApi($login_entry))->generate_spec(false, false);
$res401 = $spec_login['paths']['/need']['get']['responses']['401'] ?? null;
eq(true, isset($res401));
eq('#/components/schemas/Error', $res401['content']['application/json']['schema']['$ref'] ?? null);

@unlink($login_entry);

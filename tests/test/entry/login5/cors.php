<?php
// CORSヘッダは認証・認可・バリデーションより前に送出される（before()冒頭でcors()を実行）。
// cors_origin は commons/local.php で http://localhost:8000 を許可している。

// ヘッダ名は大文字小文字を区別せずに取得する
$header = function(\testman\Browser $b, string $name){
	foreach($b->explode_head() as $k => $v){
		if(strcasecmp($k, $name) === 0){
			return $v;
		}
	}
	return null;
};


// 未ログインの保護エンドポイント(401)でもCORSヘッダが付与される
// （cors()が認証チェックより前に走るため。SPAが401本文を読めるようにする）
$b = b();
$b->header('Origin', 'http://localhost:8000');
$b->do_post('login5::aaa');
eq(401, $b->status());
eq('http://localhost:8000', $header($b, 'Access-Control-Allow-Origin'));
eq('true', $header($b, 'Access-Control-Allow-Credentials'));


// プリフライト(OPTIONS)は認証を要求されず200で応答し、CORSヘッダを返す
$b = b();
$b->header('Origin', 'http://localhost:8000');
$b->header('Access-Control-Request-Method', 'POST');
$b->do_options('login5::aaa');
eq(200, $b->status());
eq('http://localhost:8000', $header($b, 'Access-Control-Allow-Origin'));
eq('POST', $header($b, 'Access-Control-Allow-Methods'));


// 許可されていないOriginにはCORSヘッダを付けない
$b = b();
$b->header('Origin', 'http://evil.example.com');
$b->do_post('login5::aaa');
eq(401, $b->status());
eq(null, $header($b, 'Access-Control-Allow-Origin'));


// Originヘッダ無し（同一オリジン等）ではCORSヘッダは付かない
$b = b();
$b->do_post('login5::aaa');
eq(401, $b->status());
eq(null, $header($b, 'Access-Control-Allow-Origin'));

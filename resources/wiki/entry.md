#エントリポイント

```
アプリケーションの入り口がエントリポイントです。
エントリポイントにはどのURLからどのアクションがよばれ、どのテンプレートで出力するかを定義します。
```


##基本的な例
```php
include_once('bootstrap.php');

(new \ebi\Flow())->execute([
	'patterns'=>[
		''=>['name'=>'index','template'=>'index.html'],
		'abc'=>['name'=>'a','template'=>'abc.html'],
		'def'=>['name'=>'b','template'=>'def.html','action'=>'rhaco.flow.Request::noop'],
		'ghi'=>['name'=>'c','template'=>'def.html','action'=>'rhaco.flow.Request::noop','after'=>'b'],
		'jkl'=>['name'=>'d','template'=>'def.html','action'=>'rhaco.flow.Request::noop','post_after'=>'b'],

		'dev'=>['action'=>'rhaco.Dt','mode'=>'local'],
	],
]);
```

定義名				| 					| 説明
---					| ---				| ---
nomatch_redirect	| 					| URLがpatternsにマッチしなかった場合にリダイレクトするURLを指定します
error_redirect		|					| キャッチされない例外が発生した場合にリダイレクトするURLを指定します
error_template		|					| キャッチされない例外が発生した場合に出力するテンプレートを指定します
error_status		|					| キャッチされない例外が発生した場合のHTTPステータスを指定します
media_path			|					| 画像ファイルやCSSファイル等のパスを指定します Confのmedia_urlからの相対パスとなります
template_path		|					| テンプレートのパスを指定します Confのtemplate_pathからの相対パスとなります
plugins				|					| actionに差し込むプラグインを配列形式で指定します
secure				| 					| URLをhttpsにする場合は(boolean)trueを指定します
patterns			| 					| URLマッピング、array(URLパターン(正規表現)=>テンプレート等の指定) の配列形式で定義します
					| name				| マッピングに名前をつけます URLの生成やテスト等で利用します
					| template			| 出力するテンプレートを指定します テンプレートが指定されず、かつモジュールメソッドflow_outputがない場合はJSONが出力されます<
					| action			| 実行するアクションを指定します クラス名::メソッド名の形で定義します
					| vars				| actionの実行結果に追加する値をarray(key=>value)の配列形式で定義します
					| args				| actionに渡す値をarray(key=>value)の配列形式で定義します
					| error_template	| キャッチされない例外が発生した場合に出力するテンプレートを指定します
					| media_path		| 画像ファイルやCSSファイル等のパスを指定します Confのmedia_urlからの相対パスとなります
					| template_path		| テンプレートのパスを指定します Confのtemplate_pathからの相対パスとなります
					| plugins			| actionに差し込むプラグインを配列形式で指定します
					| secure			| URLをhttpsにする場合は(boolean)trueを指定します
					| template_super	| 上書きする親テンプレートのパスを指定します　Confのtemplate_pathからの相対パスとなります
					| suffix			| actionの指定がクラス名のみの場合にメソッド展開されるURLへの接尾語
					| after				| 正常実行完了後のリダイレクト先のマップ名
					| post_after		| HTTP methodがPOSTで正常実行完了後のリダイレクト先のマップ名





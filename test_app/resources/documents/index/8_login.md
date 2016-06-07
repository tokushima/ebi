#ログイン処理

ログインが必要なアクションを定義する


###アクションにユーザモデルを定義する

```php
/**
 * @login ユーザモデルの定義 @['require'=>'test.model.Member']
 */
class LoginRequestAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}
```

###エントリにログイン、ログアウトの挙動を定義する
```php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>'test.flow.plugin.Login', // ログイン処理のプラグインをセット
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',  // nameをloginにすると自動でログイン画面にリダイレクトします
			'action'=>'ebi.flow.Request::do_login', // ログイン処理のアクション
			'args'=>['login_redirect'=>'aaa'], // ログイン成功時のリダイレクト先
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'login_redirect::do_logout', // ログイン処理のアクション
		]
		,'aaa'=>[
			'name'=>'aaa'
			,'action'=>'test.flow.LoginRequestAction::aaa'
		]
	]
]);
```

#メール送信のテスト(SmtpBlackhole)

メール送信処理でsmtpサーバには送らずDBに保存する

###\ebi\Confで設定する

```php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[ // DBの接続設定
			'ebi.SmtpBlackholeDao'=>['type'=>'ebi.DbConnector','dbname'=>'local.db','host'=>dirname(__DIR__)],
		]
	]
]);

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>['ebi.SmtpBlackholeDao'] // プラグインを設定する
]);
```

###テーブルを作成する

```
php cmdman.php ebi.Dt::create_table --model ebi.SmtpBlackholeDao
```

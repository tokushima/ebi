#アクションで発生したエラーを確認する

Flowで実行されたアクションで発生したExceptionをログに出力する

###\ebi\Conf::setで設定する

```php
\ebi\Conf::set([
	'ebi.Log'=>[
		'level'=>'warn', // ログレベル
		'file'=>dirname(__DIR__).'/work/debug.log',  // ログファイルの出力先
	],
	'ebi.Flow'=>[
		'exception_log_level'=>'warn', // Exception発生時に出力するエラーレベル
		'exception_log_ignore'=>[ // 出力しない正規表現パターン
			'Unauthorized',
			'.+ require',
		]
	],
]);
```

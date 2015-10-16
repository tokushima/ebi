ebi
====
__2012-12-25__

PHP framework (PHP 5 >= 5.5.0)





## Quick Start 


### cmdmanのダウンロード

```
$ curl -LO http://git.io/cmdman.phar
```


### cmdmanを実行すると利用可能なコマンドが一覧されます

```
$ php cmdman.phar 

cmdman 0.3.8 (PHP 5.6.12)
Type 'php cmdman.php subcommand --help' for usage.

Subcommands:
  ebi.phar      : Download ebi.phar
  composer.phar : Download composer.phar
```


### ebiをダウンロードします

```
$ php cmdman.phar ebi.phar 

ebi successfully installed to: /Users/tokushima/Documents/workspace/work/ebi.phar
```


cmdmanにpharファイルを指定するとphar内で利用できるコマンドが一覧されます

```
$ php cmdman.phar ebi.phar 

cmdman 0.3.8 (PHP 5.6.12)
Type 'php cmdman.php subcommand --help' for usage.

Subcommands:
  ebi.phar#ebi.Dt::dao_create_table : モデルからtableを作成する
  ebi.phar#ebi.Dt::dao_export       : dao data export
  ebi.phar#ebi.Dt::dao_import       : dao data import
  ebi.phar#ebi.Dt::format           : PHPファイルの改行コードをCRに統一する
  ebi.phar#ebi.Dt::phar             : ライブラリをpharにする
  ebi.phar#ebi.Dt::setup            : Application setup
  ebi.phar#ebi.Dt::start            : 簡易ランチャー作成
  ebi.phar#ebi.Dt::unphar           : pharを展開する
```


### ebiの単純な構成をインストールします

```
$ php cmdman.phar ebi.phar#ebi.Dt::start 

Application mode (local) [local]: local
Application mode is `local`
getting testman? (y / n) [n]: n
Done.


port? [8000]: 8000
entry? [index.php]: index.php
Written: /Users/tokushima/Documents/workspace/work/start.sh
Written: /Users/tokushima/Documents/workspace/work/start.html
```

testmanはtestを実行するシンプルなコマンドラインツールです。 ( https://github.com/tokushima/testman )



### サーバを起動します

```
$ ./start.sh 


PHP 5.6.12 Development Server started at Fri Oct 16 12:11:13 2015
Listening on http://0.0.0.0:8000
Document root is /Users/tokushima/Documents/workspace/work
Press Ctrl-C to quit.
[Fri Oct 16 12:11:15 2015] 127.0.0.1:50711 [200]: /index.php

```


自動的にブラウザが開き開発者ツールが表示されます

実際の運用ではstart.shやstart.htmlは当然必要ありません

nginx等で適切に処理してください



## Document

https://github.com/tokushima/ebi/wiki


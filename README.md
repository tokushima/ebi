ebi
====
__2012-12-25__

PHP framework (PHP 5 >= 5.5.0)





## Quick Start 


### ebicoのダウンロード

```
$ curl -LO http://git.io/ebico.phar
```


### ebico を実行すると利用可能なコマンドが一覧されます

```
$ php ebico.phar

ebico 20151202.213659 (PHP 7.0.0RC4)
Type 'php ebico.phar subcommand --help' for usage.

Subcommands:
  ebi.phar      : Download ebi.phar
  composer.phar : Download composer.phar
  archive       : Creating Phar Archives
  extract       : Extract the contents of a phar archive to a directory
```


### ebiをダウンロードします

```
$ php ebico.phar ebi.phar 

ebi successfully installed to: /Users/tokushima/Documents/workspace/work/ebi.phar
```


ebicoにpharファイルを指定するとphar内で利用できるコマンドが一覧されます

```
$ php ebico.phar ebi.phar 

ebico 20151202.213833 (PHP 7.0.0RC4)
Type 'php ebico.phar subcommand --help' for usage.

Subcommands:
  ebi.phar#ebi.Dt::dao_create_table : モデルからtableを作成する
  ebi.phar#ebi.Dt::dao_export       : dao data export
  ebi.phar#ebi.Dt::dao_import       : dao data import
  ebi.phar#ebi.Dt::setup            : Application setup
  ebi.phar#ebi.Dt::start            : 簡易ランチャー作成
```


### ebiの単純な構成をインストールします

```
$ php ebico.phar ebi.phar#ebi.Dt::start 

Application mode (local) [local]: local
Application mode is `local`
getting testman? (y / n) [n]: n
Done.


port? [8000]: 8000
entry? [index.php]: index.php
Written: /Users/tokushima/Documents/workspace/work/start.sh
Written: /Users/tokushima/Documents/workspace/work/start.html
```

ebitenはtestを実行するシンプルなコマンドラインツールです。 ( https://github.com/tokushima/ebiten )



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


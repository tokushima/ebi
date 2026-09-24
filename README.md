ebi
====

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)


__2016-09-20__ (since: 2012-12-25)

(PHP 8 >= 8.2)


## Composer 

```
composer.phar require tokushima/ebi
```


## ドキュメント

- [Attribute リファレンス](docs/attributes.md) … ルーティング・入力検証・OpenAPI・モデル定義・フロー(MCP) の Attribute 一覧


## エディタ支援

コードからは読み込まれない、IDE の補完・検証用のファイル。

- [resources/dtd/mail.dtd](resources/dtd/mail.dtd) … メールテンプレート（`\ebi\Mail::set_template`）の語彙
- [resources/dtd/template.dtd](resources/dtd/template.dtd) … テンプレート（`\ebi\Template`）の `rt:` 語彙
- [resources/stubs/cmdman.stub.php](resources/stubs/cmdman.stub.php) … cmdman コマンドから呼べる API
- [resources/stubs/testman.stub.php](resources/stubs/testman.stub.php) … testman のテストから呼べる API

stub は各ツールの `--stub` が出力する生成物。手で編集せず再生成する。

```
cmdman  --stub > resources/stubs/cmdman.stub.php
testman --stub > resources/stubs/testman.stub.php
```

ツール本体とずれると `tests/test/ebi/stubs.php` が失敗する（ツール未導入の環境ではスキップ）。

VS Code では [XML 拡張](https://marketplace.visualstudio.com/items?itemName=redhat.vscode-xml)（`redhat.vscode-xml`）が DTD を読む。
XML 側に `DOCTYPE` を書くか、`xml.fileAssociations` でパターンと結び付ける。

```json
{
  "xml.fileAssociations": [
    {
      "pattern": "**/resources/mail/**/*.xml",
      "systemId": "vendor/tokushima/ebi/resources/dtd/mail.dtd"
    }
  ]
}
```



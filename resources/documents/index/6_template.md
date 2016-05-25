#テンプレートエンジン

テンプレートエンジンで出来る事は、テンプレートのXML拡張による操作と変数の展開です。

###変数の書式
```
{$abc}
{$t.html($abc)}
```


###作成例

```
<?php
include_once('bootstrap.php');

$template = new \ebi\Template();
$template->vars('name','Sample');
$template->vars('data_array',array('ABC','DEF','GHI'));
$template->output(__FILE__);
?>
<rt:template>
<html>
<body>
 <h1>{$name}</h1>
 <rt:loop param="data_array" var="data">
 	<p>{$data}</p>
 <rt:loop>
</body>
</html>
</rt:template>
```



###実行結果
```php
<html>
<body>
 <h1>Sample</h1>
  	<p>ABC</p>
  	<p>DEF</p>
  	<p>GHI</p>
 </body>
</html>
```



###デフォルトで埋め込まれている変数

変数名		| 内容
---			| ---
$t			| ebi.FlowHelper (ebi.Flow を利用した場合)


###拡張されたXML

####<rt:template>

<rt:template>で囲まれた部分がテンプレートとして使用されます。

ひとつも<rt:template>がない場合は全体をテンプレートとして使用します。


属性			| 説明
---			| ---
name		| テンプレート名


####<rt:extends>

継承するテンプレートを指定する

属性			| 説明
---			| ---
href		| 継承するテンプレートの相対パス


####<rt:block>

上書き可能ブロックの定義、上書きを行う。

同名のブロックが<rt:block>で囲まれた部分に上書きされる


属性			| 説明
---			| ---
name		| ブロック名

```php
<rt:extends href="index.html" />
<rt:block name="abc">
	ABC
</rt:block>
<rt:block name="def">
	DEF
</rt:block>
```

####<rt:blockvar>

ブロックファイルで変数を定義する

属性			| 説明
---			| ---
name		| 変数名

```php
<rt:extends href="index.html" />
<rt:blockvar name="abc">ABC</rt:blockvar>
```
	

####<rt:comment>

<rt:comment>で囲まれた部分が実行時に削除されるコメントブロックとなる

```php
abc
<rt:comment>
	この部分は表示されない
</rt:comment>
def
```

####<rt:loop>

<rt:loop>で囲まれた部分を繰り返し処理する。

属性			| 説明
---			| ---
param		| 対象の変数名
var			| paramからとりだされた現在の値を格納する変数名
key			| paramからとりだされた現在のキーを格納する変数名
counter		| offsetからの現在のカウント数を格納する変数名
evenodd		| 偶数か奇数かを表す文字列を格納する変数名
even_value	| 偶数を表す文字列
odd_value	| 奇数を表す文字列


```php
<rt:loop param="object_list" var="obj">
	{$obj.fm_value()}
</rt:loop>			
```


####<rt:if>

条件が真の場合に<rt:if>で囲まれたブロックを処理する。

valueが指定されてない場合はparam値で判断する。

属性			| 説明
---			| ---
param		| 対象の変数名
value		| paramと比較する値


要素				| 説明
---				| ---
\<rt:else />	| 条件が偽の場合に以下のブロックを処理する


```php
<rt:if param="abc" value="1">
	One
<rt:else />
	Other
</rt:if>
```
	

####<form>

formの拡張


属性			| 説明
---			| ---
rt:ref		| formの要素にrt:refを適用する
rt:aref		| 結果（変数,タグの展開済）を処理する (true, false)



要素				| 説明
---				| ---
\<input>		|
\<textarea>		| 
\<select>		|

####\<input>, \<textarea>

Templateにセットされた値とname属性(またはid属性)を元にformを処理する。			

属性			| 説明
---			| ---
rt:ref		| 変数を参照し処理する (true, false)
rt:multiple	| type=checkbox もしくは multiple=multiple の 要素のname属性を配列形式にするか name=hoge[] (true, false)


####\<select>

Templateにセットされた値とname属性(またはid属性)を元にformを処理する。

rt:paramから<option>を生成される。 rt:paramが未指定の場合はselect内要素が利用される。

属性				| 説明
---				| ---
rt:ref			| 変数を参照し処理する (true, false)
rt:param		| 対象の変数名
rt:var			| paramからとりだされた現在の値を格納する変数名
rt:key			| paramからとりだされた現在のキーを格納する変数名
rt:counter		| offsetからの現在のカウント数を格納する変数名
rt:evenodd		| 偶数か奇数かを表す文字列を格納する変数名
rt:even_value	| 偶数を表す文字列(未指定の場合は"even")
rt:odd_value	| 奇数を表す文字列(未指定の場合は"odd")
rt:null			| 空の値をもつ<option>を作成する、指定した文字列がラベルとなる


####\<table>,\<ul>,\<ol>

繰り返し処理の拡張を処理する

属性				| 説明
---				| ---
rt:param		| 対象の変数名
rt:var			| paramからとりだされた現在の値を格納する変数名
rt:key			| paramからとりだされた現在のキーを格納する変数名
rt:counter		| offsetからの現在のカウント数を格納する変数名
rt:evenodd		| 偶数か奇数かを表す文字列を格納する変数名
rt:even_value	| 偶数を表す文字列(未指定の場合は"even")
rt:odd_value	| 奇数を表す文字列(未指定の場合は"odd")

```php
<table rt:param="object_list" rt:var="obj" rt:evenodd="eo">
<thead>
	<th>name</th>
	<th>value</th>
</thead>
<tbody>
<tr>
	<td>{$obj.name()}</td>
	<td>{$obj.fm_value()}</td>
</tr>
</tbody>
</table>
```


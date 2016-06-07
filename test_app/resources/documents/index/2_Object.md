#ebi.Object

ebi.Objectを基底クラスにすることでプロパティやアクセサの挙動を容易に変更できます。



##特殊メソッド ( protected )

特殊な名前をもったメソッドを定義することで独自の振る舞いをすることができます。

メソッド名	 		| 説明
---				| ---
\__init\__		| コンストラクタで初期処理を行う
\__del\__		| デストラクタを拡張する
\__str\__		| オブジェクトの文字列表現を拡張する


###作成例

```php
namespace abc;
/**
 * @var integer $abc 数値
 */
class Hoge extends \ebi\Object{
	protected $abc;
	
	protected function __init__(){
		$this->abc = $this->abc + 10;
	}
	protected function __str__(){
		return '['.$this->abc.']';
	}
}
```

###使用例

```php
include('bootstrap.php');

$obj = new \ebi\Hoge(10);
var_dump($obj->abc());
var_dump((string)$obj);
```

結果
```
int(20)
string(4) "[20]"
```


##プロパティのアクセサをカスタマイズする

protectedまたはpublicで定義されたプロパティは自動的に同名のメソッドをGetter,Setterとして扱う事ができます。

###作成例

```php
namespace abc;
/**
 * @var integer $abc 数値
 * @var string $def 文字列
 */
class Hoge extends \ebi\Object{
	protected $abc;
	protected $def;
	
	protected function __get_abc__(){
		return $this->abc + 10;
	}
}
```


###使用例

```php
$obj = new \abc\Hoge();
$obj->abc('warn');
var_dump($obj->abc());
```

結果

```
int(123)
```

##クラスコメントに@varで宣言することでプロパティの型を定義する事ができます。

型			| 説明
---			| ---
number		| アノテーションdecimal_placesでサイズを指定します
serial		| シリアル型 数値
boolean		| 真偽値型 true/false/null
timestamp	| タイムスタンプ型 YYYY/MM/DD HH:ii:ss
date		| 日付型 YYYY/MM/DD
time		| 時間型 HH:ii:ss
intdate		| 数値形式の日付型 YYYYMMDD
email		| email型
alnum		| 英数値型 0-9,A-Z,a-z,_

	
特殊なprefixをもったprotectedメソッドを定義することでアクセサを拡張します。


##プロパティ名abcの場合

メソッド名			| 説明
---				| ---
\__get_abc\__		| Getter を拡張します
\__set_abc\__		| Setter を拡張します
\__in_abc\__		| inを拡張します
\__is_abc\__		| isを拡張します
\__ar_abc\__		| arを拡張します
\__fm_abc\__		| fmを拡張します
\__rm_abc\__		| rmを拡張します



* aaaa
* bbbb
  * cccc
   * dddd
  *ccc
  *cc
*qq 
  vccc
aaaa
bbb

	
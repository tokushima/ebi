#O/Rマッパー

##クラスのアノテーション

@table        | name:(string) リンクするテーブル名		
@readonly		| save,delete禁止のモデルとする			


##プロパティのアノテーション(@var)

extra				| テーブルのカラムとリンクしないプロパティとする					| boolean	
cond				| テーブルのカラムとリンクする際の追加の条件						| string	
primary			| 主キーとする												| boolean	
require			| 必須とする												| boolean	
unique			| ユニークとする											| boolean	
unique_together	| 指定のプロパティとあわせてユニークとする						| string[]	
master			| 指定のDaoの主キーにプロパティの値が存在することを確認する			| string	
min				| 最低値													| integer	
max				| 最大値													| integer	
auto_now_add		| 新規登録時のみ現在日時をセットする							| boolean	
auto_now			| 更新時に現在日時をセットする								| boolean	
auto_future_add	| 新規登録時のみ未来日時をセットする							| boolean	
auto_code_add		| 新規登録時のみユニークなコードをセットする、桁数はmaxで指定する	| boolean	

####auto_code_add
コードの種類は base で指定する 'base'=>'0123456789ABCDEF'
生成したくないパターンがある場合は verify_****ではじく



##拡張メソッド

メソッド名				| 説明								
--			| ---		
\__find_conds\__		| select時の追加条件					
\__before_save\__		| 追加/更新の前に行う処理				
\__after_save\__		| 追加/更新の後に行う処理				
\__before_create\__	| 追加の前に行う処理					
\__after_create\__	| 追加の後に行う処理					
\__before_update\__	| 更新の前に行う処理					
\__after_update\__	| 更新の後に行う処理					
\__before_delete\__	| 削除の前に行う処理					
\__after_delete\__	| 削除の後に行う処理					


##拡張アクセサ

アクセサ名			| 説明									
----------		| ----------							
verify			| 追加の検証処理、失敗時にfalseを返すようにする	


##作成例

###テーブルの作成
```
$ sqlite3 sample.db
SQLite version 3.7.7 2011-06-25 16:35:41
Enter ".help" for instructions
Enter SQL statements terminated with a ";"
sqlite> create table `entry`(
   		`id` INTEGER PRIMARY KEY AUTOINCREMENT null ,
   		`name` TEXT null ,
   		`title` TEXT null ,
   		`description` TEXT null ,
   		`create_date` INTEGER null ,
   		`update_date` INTEGER null 
   		);
sqlite> .exit
```

###sample.php
```php
require('bootstrap.php');
use \ebi\Q;

// 接続情報の記述
\ebi\Conf::set('ebi.Dao','connection',array(
'Entry'=>array(
'type'=>'ebi.DbConnector','host'=>__DIR__,'dbname'=>'sample.db','user'=>'','password'=>'','port'=>'','encode'=>'utf8'
)));

/**
 * @var serial $id
 * @var alnum $name @['max'=>50,'unique'=>true]
 * @var string $title @['max'=>100,'require'=>true]
 * @var text $description @['require'=>true]
 * @var timestamp $create_date @['auto_now_add'=>true]
 * @var timestamp $update_date @['auto_now'=>true]
 * @class @['create'=>true,'update'=>true,'delete'=>true]
 */
class Entry extends \ebi\Dao{
	protected $id;
	protected $name;
	protected $title;
	protected $description;
	protected $create_date;
	protected $update_date;
}

$println = function($s){
	print($s.PHP_EOL);
};

$obj = new \Entry();
$props = array_keys($obj->props());

$str = 'ABCDEFGH';
for($i=0;$i<strlen($str);$i++){
	$v = substr($str,$i,1);

	$entry = new \Entry();
	$entry->name($i+1);
	$entry->title(strtolower($v));
	$entry->description($v);
	$entry->save();
}

$println('# 一覧');
$println(implode(',',$props));
foreach(\Entry::find() as $obj){
  $println(implode(',',$obj->hash()));
}

$entry = \Entry::find_get(Q::eq('title','a'));
$entry->title('x');
$entry->save();

$println('# aがxに更新されている');
$println(implode(',',$props));
foreach(\Entry::find(Q::eq('title','X')) as $obj){
  $println(implode(',',$obj->hash()));
}

$entry = \Entry::find_get(Q::eq('title','x'));
$entry->delete();

$println('# xが削除されている');
$println(implode(',',$props));
foreach(\Entry::find() as $obj){
  $println(implode(',',$obj->hash()));
}

$println('# 1ページ');
$println(implode(',',$props));
foreach(\Entry::find(new \ebi\Paginator(3,1),Q::order('id')) as $obj){
  $println(implode(',',$obj->hash()));
}

$println('# 2ページ');
$println(implode(',',$props));
foreach(\Entry::find(new \ebi\Paginator(3,2),Q::order('id')) as $obj){
  $println(implode(',',$obj->hash()));
}

$println('# 3ページ');
$println(implode(',',$props));
foreach(\Entry::find(new \ebi\Paginator(3,3),Q::order('id')) as $obj){
  $println(implode(',',$obj->hash()));
}
```

###結果
```
# 一覧
id,name,title,description,create_date,update_date
1,1,a,A,2011/07/03 14:04:05,2011/07/03 14:04:05
2,2,b,B,2011/07/03 14:04:05,2011/07/03 14:04:05
3,3,c,C,2011/07/03 14:04:05,2011/07/03 14:04:05
4,4,d,D,2011/07/03 14:04:05,2011/07/03 14:04:05
5,5,e,E,2011/07/03 14:04:05,2011/07/03 14:04:05
6,6,f,F,2011/07/03 14:04:05,2011/07/03 14:04:05
7,7,g,G,2011/07/03 14:04:05,2011/07/03 14:04:05
8,8,h,H,2011/07/03 14:04:05,2011/07/03 14:04:05
# aがxに更新されている
id,name,title,description,create_date,update_date
1,1,x,A,2011/07/03 14:04:05,2011/07/03 14:04:05
# xが削除されている
id,name,title,description,create_date,update_date
2,2,b,B,2011/07/03 14:04:05,2011/07/03 14:04:05
3,3,c,C,2011/07/03 14:04:05,2011/07/03 14:04:05
4,4,d,D,2011/07/03 14:04:05,2011/07/03 14:04:05
5,5,e,E,2011/07/03 14:04:05,2011/07/03 14:04:05
6,6,f,F,2011/07/03 14:04:05,2011/07/03 14:04:05
7,7,g,G,2011/07/03 14:04:05,2011/07/03 14:04:05
8,8,h,H,2011/07/03 14:04:05,2011/07/03 14:04:05
# 1ページ
id,name,title,description,create_date,update_date
2,2,b,B,2011/07/03 14:04:05,2011/07/03 14:04:05
3,3,c,C,2011/07/03 14:04:05,2011/07/03 14:04:05
4,4,d,D,2011/07/03 14:04:05,2011/07/03 14:04:05
# 2ページ
id,name,title,description,create_date,update_date
5,5,e,E,2011/07/03 14:04:05,2011/07/03 14:04:05
6,6,f,F,2011/07/03 14:04:05,2011/07/03 14:04:05
7,7,g,G,2011/07/03 14:04:05,2011/07/03 14:04:05
# 3ページ
id,name,title,description,create_date,update_date
8,8,h,H,2011/07/03 14:04:05,2011/07/03 14:04:05
```

<?php
use \ebi\Q;

// ドット有り self_var の未解決参照は、自テーブル列に化けさせず InvalidAnnotationException で落とす。
// fixture の reset_tables に拾われないよう、モデルはこのテスト内でインライン定義する。

class BadRefInlineDao extends \ebi\Dao{
	#[\ebi\Attribute\Prop(type:'serial')]
	protected ?int $id = null;

	#[\ebi\Attribute\Prop]
	protected ?int $b_ref = null;

	// codex は存在しない結合プロパティ。ドット有り＝参照意図なので例外になるべき。
	#[\ebi\Attribute\Prop(cond:'codex.c_ref(chain_c.id)', column:'cval')]
	protected ?string $bad = null;
}

try{
	// メタ構築（cond 解決）を発火させる。DB へ到達する前に例外で落ちる。
	BadRefInlineDao::find_all(Q::eq('id',1));
	failureuer(); // 到達したら失敗
}catch(\ebi\exception\InvalidAnnotationException $e){
	// メッセージに未解決の参照先トークンが含まれること
	eq(true,strpos($e->getMessage(),'codex') !== false);
}

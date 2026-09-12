<?php
use \ebi\Q;

// 相互参照（a→b, b→a）は requeue が収束しないため、循環ガードで InvalidAnnotationException を投げる。
// fixture の reset_tables に拾われないよう、モデルはこのテスト内でインライン定義する。

class CircularInlineDao extends \ebi\Dao{
	#[\ebi\Attribute\Prop(type:'serial')]
	protected ?int $id = null;

	#[\ebi\Attribute\Prop(from: [['b.x', 'ztbl', 'id']], column:'v')]
	protected ?string $a = null;

	#[\ebi\Attribute\Prop(from: [['a.y', 'ztbl', 'id']], column:'v')]
	protected ?string $b = null;
}

try{
	// メタ構築（cond 解決）を発火。a→b→a が解決せず循環ガードで落ちる。
	CircularInlineDao::find_all(Q::eq('id',1));
	failureuer(); // 到達したら失敗
}catch(\ebi\exception\InvalidAnnotationException $e){
	eq(true,strpos($e->getMessage(),'circular') !== false);
}

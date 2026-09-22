<?php
// Obj::___set___ 多段コンテナ: 外1段はここで反復し、残りの段は \ebi\Validator が剥がす。葉はキャストされる。
$obj = new \test\object\NestedTypes();

// メタが正準形（基底型 + 種別列）へ畳まれている
eq('int', $obj->prop_anon('grid', 'type'));
eq('aa', $obj->prop_anon('grid', 'attr'));
eq('int', $obj->prop_anon('pages', 'type'));
eq('ha', $obj->prop_anon('pages', 'attr'));

// int[][]: 外1段を反復し、各要素を int[] として検証（葉 '1'/'2' は int へキャスト）
$obj->grid([['1', '2'], ['3']]);
eq([[1, 2], [3]], $obj->grid());

// map<string, int[]>: 連想はキー保持、値は int[] として検証
$obj->pages(['a' => ['1', '2'], 'b' => ['3']]);
eq(['a' => [1, 2], 'b' => [3]], $obj->pages());

// 葉の型不一致は例外（メッセージに位置と型）
$obj2 = new \test\object\NestedTypes();
try{
	$obj2->grid([['1', 'x']]);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('grid', $e->getMessage());
	meq('int', $e->getMessage());
}

<?php

// array 型: 配列ならそのまま返す
eq([1,2,3], \ebi\Validator::type('tags', [1,2,3], ['type'=>'array']));

// array 型: 空配列もOK
eq([], \ebi\Validator::type('tags', [], ['type'=>'array']));

// array 型: 配列でない場合は例外
try{
	\ebi\Validator::type('tags', 'foo', ['type'=>'array']);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('tags', $e->getMessage());
	meq('array', $e->getMessage());
}

try{
	\ebi\Validator::type('tags', 123, ['type'=>'array']);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
}

// items が指定されていれば各要素を再帰的に検証 (string)
eq(['a','b','c'], \ebi\Validator::type('tags', ['a','b','c'], ['type'=>'array','items'=>'string']));

// items が指定されていれば各要素を再帰的に検証 (int) - キャストされる
eq([1,2,3], \ebi\Validator::type('ids', ['1','2','3'], ['type'=>'array','items'=>'int']));

// items の型に合わない要素があれば、要素位置を含むメッセージで例外
try{
	\ebi\Validator::type('ids', [1,'abc',3], ['type'=>'array','items'=>'int']);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('ids[1]', $e->getMessage());
	meq('int', $e->getMessage());
}

// items: 'bool' でも動作する
eq([true,false], \ebi\Validator::type('flags', ['true','false'], ['type'=>'array','items'=>'bool']));

// null の場合は何も検証せず null を返す
eq(null, \ebi\Validator::type('tags', null, ['type'=>'array']));
eq(null, \ebi\Validator::type('tags', null, ['type'=>'array','items'=>'string']));

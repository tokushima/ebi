<?php
// Validator: コンテナ正準形（type=基底型 + attr=コンテナ種別列）のランタイム検証。
// 方針: コンテナは type:'array'/'map' + items で表す（サフィックス文字列の入力解釈は撤去済み）。
// attr_suffix は attr→'X[]'/'X{}' の内部復元（OpenApi/SourceAnalyzer の型文字列化用）。

// --- attr_suffix（attr の段数ぶん 'X[]'/'X{}' へ復元。多段も可） ---
eq('',     \ebi\Validator::attr_suffix(''));
eq('[]',   \ebi\Validator::attr_suffix('a'));
eq('{}',   \ebi\Validator::attr_suffix('h'));
eq('[][]', \ebi\Validator::attr_suffix('aa'));   // 外→内 = array,array
eq('[]{}', \ebi\Validator::attr_suffix('ha'));   // 外→内 = map,array = map<string, X[]>

// --- ランタイム検証: 正準形 attr（items 配列が生む形。多段もこの attr で検証する） ---
// attr:'a' = X[]。各要素をキャスト
eq([1, 2, 3], \ebi\Validator::type('t', ['1', '2', '3'], ['type' => 'int', 'attr' => 'a']));

// attr:'aa' = X[][]（多段）。各葉をキャスト
eq([[1, 2], [3]], \ebi\Validator::type('g', [['1', '2'], ['3']], ['type' => 'int', 'attr' => 'aa']));

// type:'map'（attr:'h'）= 連想。値型で各値を検証しキーは保持
eq(['x' => 1, 'y' => 2], \ebi\Validator::type('m', ['x' => '1', 'y' => '2'], ['type' => 'int', 'attr' => 'h']));

// 混在 attr:'ha' = map<string, int[]>
eq(['x' => [1, 2]], \ebi\Validator::type('mm', ['x' => ['1', '2']], ['type' => 'int', 'attr' => 'ha']));

// map が配列でない → 例外（メッセージは "must be an map"）
try{
	\ebi\Validator::type('m', 'foo', ['type' => 'int', 'attr' => 'h']);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('m', $e->getMessage());
	meq('map', $e->getMessage());
}

// 多段の葉で型不一致 → 位置つきメッセージ（g[0][1]）
try{
	\ebi\Validator::type('g', [[1, 'abc']], ['type' => 'int', 'attr' => 'aa']);
	fail('例外でるはず');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('g[0][1]', $e->getMessage());
	meq('int', $e->getMessage());
}

// 要素型を指定しない type:'map'（items/attr 無し）は中身任意でそのまま返す
eq(['a' => 1, 'b' => 'x'], \ebi\Validator::type('m', ['a' => 1, 'b' => 'x'], ['type' => 'map']));

// type:'array'/'map' + items（attr 未展開のメタ）も正準形へ畳んで検証する
eq([1, 2], \ebi\Validator::type('t', ['1', '2'], ['type' => 'array', 'items' => 'int']));
eq(['x' => 1], \ebi\Validator::type('m', ['x' => '1'], ['type' => 'map', 'items' => 'int']));

// サフィックス文字列は型指定として解釈されない（コンテナは type+items で表す）
try{
	\ebi\Validator::type('t', ['1', '2'], ['type' => 'int[]']);
	fail('int[] はクラス型扱いになり instanceof に失敗する想定');
}catch(\ebi\exception\InvalidArgumentException $e){
	meq('int[]', $e->getMessage());
}

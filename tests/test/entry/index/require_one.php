<?php
// #[OneOf(['id','code','email'])] = 列挙のうち「ちょうど1つ」が必須（0個でも2個以上でもエラー）
$b = b();

// 0個 → 必須エラー（RequiredException）
$b->do_get('index::require_one');
eq(true, strpos($b->body(), 'RequiredException') !== false);

// 1個(id) → OK（例外なし・アクション結果を返す）
$b->vars('id', '5');
$b->do_get('index::require_one');
eq(200, $b->status());
eq(false, strpos($b->body(), 'RequiredException') !== false);
eq(true, strpos($b->body(), '"ok":1') !== false);

// 2個(id+code) → 複数不可エラー（排他）。未選択(Required)とは別の InvalidArgumentException。
$b->vars('id', '5');
$b->vars('code', 'abc');
$b->do_get('index::require_one');
eq(true, strpos($b->body(), 'InvalidArgumentException') !== false);
eq(false, strpos($b->body(), 'RequiredException') !== false);

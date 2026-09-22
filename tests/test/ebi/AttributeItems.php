<?php

// items は正準形（type=基底型 + attr=コンテナ種別列）へ畳まれ、items 自体はメタに残らない。
// attr は外側から内側へ 'a'(配列) / 'h'(連想) を並べた文字列で、長さがそのまま段数。
$params = \ebi\AttributeReader::get_method(\test\ItemsFixture::class, 'action', 'request');

eq('string', $params['tags']['type']);
eq('a', $params['tags']['attr']);
eq(false, isset($params['tags']['items']));

eq('int', $params['ids']['type']);
eq('a', $params['ids']['attr']);

// 多段: items を配列で包むと1段深くなる（[X] = X[]、[[X]] = X[][]）。多段はこの形で表す。
eq('int', $params['grid']['type']);
eq('aa', $params['grid']['attr']);
eq('string', $params['legacy']['type']);
eq('aa', $params['legacy']['attr']);

// map は 'h'。内側に配列を持つ混在コンテナも表現できる
eq('int', $params['dict']['type']);
eq('h', $params['dict']['attr']);
eq('int', $params['pages']['type']);
eq('ha', $params['pages']['attr']);

// コンテナでない型は attr を持たない
eq('string', $params['plain']['type']);
eq(false, isset($params['plain']['attr']));

// Response 属性も同じ規則
$contexts = \ebi\AttributeReader::get_method(\test\ItemsFixture::class, 'action', 'context');
eq('string', $contexts['names']['type']);
eq('a', $contexts['names']['attr']);
eq('int', $contexts['counts']['type']);
eq('a', $contexts['counts']['attr']);

// Attribute インスタンス自体は items をそのまま保持する（未指定は null）
$single = new \ebi\Attribute\Parameter(name: 'foo', type: 'string');
eq(null, $single->items);

<?php

// Parameter 属性: items が読み取れる
$params = \ebi\AttributeReader::get_method(\test\ItemsFixture::class, 'action', 'request');
eq('array', $params['tags']['type']);
eq('string', $params['tags']['items']);
eq('array', $params['ids']['type']);
eq('int', $params['ids']['items']);

// Response 属性: items が読み取れる
$contexts = \ebi\AttributeReader::get_method(\test\ItemsFixture::class, 'action', 'context');
eq('array', $contexts['names']['type']);
eq('string', $contexts['names']['items']);
eq('array', $contexts['counts']['type']);
eq('int', $contexts['counts']['items']);

// items 未指定の場合は null（array_filter で除去される）
$single = new \ebi\Attribute\Parameter(name: 'foo', type: 'string');
eq(null, $single->items);

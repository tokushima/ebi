<?php
/**
 * @author tokushima
 * 
 * GETリクエスト
 */
$b = new \ebi\Browser();
$b->do_get(test_map_url('test_index::template_abc'));

$explode_head = $b->explode_head();
eq(true,!empty($explode_head));
eq(true,is_array($explode_head));

$head = $b->head();
eq(true,!empty($head));
eq(true,is_string($head));


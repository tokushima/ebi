<?php
$b = new \ebi\Browser();
$b->do_get('http://localhost/ebi/abc.php');

$explode_head = $b->explode_head();
eq(true,!empty($explode_head));
eq(true,is_array($explode_head));

$head = $b->head();
eq(true,!empty($head));
eq(true,is_string($head));


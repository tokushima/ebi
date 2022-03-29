<?php
$b = b();

$b->do_get('index::ABC/after');
eq('http://localhost:8000/index.php/ABC/after_a', $b->url());


$b->do_post('index::ABC/after');
eq('http://localhost:8000/index.php/ABC/after_b', $b->url());


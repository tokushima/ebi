<?php
$array = [
	1000000=>0,
	1000030=>2,
	1000050=>3,
	1000010=>1,
	1000100=>4,
];

eq(1,\ebi\Util::array_range_search(1000015, $array));


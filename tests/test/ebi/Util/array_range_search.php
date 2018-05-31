<?php
$array = [
	1000000=>100,
	1000030=>2,
	1000050=>3,
	1000010=>1,
	1000100=>4,
	1000200=>5,
];


eq(1,\ebi\Util::array_range_search(1000015, $array));
eq(2,\ebi\Util::array_range_search(1000035, $array));
eq(5,\ebi\Util::array_range_search(1000210, $array));
eq(100,\ebi\Util::array_range_search(1, $array));


$array = [
	1=>1,
	2=>2,
	3=>3,
	4=>4,
	5=>5,
	6=>6,
];

eq(1,\ebi\Util::array_range_search(0, $array));
eq(1,\ebi\Util::array_range_search(1, $array));
eq(4,\ebi\Util::array_range_search(4, $array));
eq(6,\ebi\Util::array_range_search(6, $array));
eq(6,\ebi\Util::array_range_search(7, $array));



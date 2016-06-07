<?php

$data1 = <<< _DATA
a
b
c
d
_DATA;

$data2 = <<< _DATA
a
b
x
c
_DATA;


$diff = \ebi\Diff::parse($data1, $data2);
eq([
	[0,1,1,'a'],
	[0,2,2,'b'],
	[1,null,3,'x'],
	[0,3,4,'c'],
	[-1,4,null,'d']	
],$diff);


$diff = \ebi\Diff::parse($data2,$data1);
eq([
	[0,1,1,'a'],
	[0,2,2,'b'],
	[-1,3,null,'x'],
	[0,4,3,'c'],
	[1,null,4,'d']
],$diff);


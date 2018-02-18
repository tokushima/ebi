<?php
$filename = \testman\Resource::path('test.jpg');

$info = \ebi\Image::get_info($filename);
eq(922,$info['width']);
eq(922,$info['height']);
eq('image/jpeg',$info['mime']);
eq(8,$info['bits']);
eq(3,$info['channels']);


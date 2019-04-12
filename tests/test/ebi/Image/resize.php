<?php
$out = \ebi\Conf::work_path('resize.jpg');
$out_png = \ebi\Conf::work_path('resize.png');

//$out = \ebi\WorkingStorage::path('resize.jpg');
//$out_png = \ebi\WorkingStorage::path('resize.png');


$filename = \testman\Resource::path('test.jpg');
$image = new \ebi\Image($filename);
eq(\ebi\Image::ORIENTATION_SQUARE,$image->get_orientation());
$image->resize(100,50)->write($out);
eq(\ebi\Image::ORIENTATION_SQUARE,$image->get_orientation());

eq(file_get_contents(\testman\Resource::path('resize_gd.jpg')) == file_get_contents($out));



$filename = \testman\Resource::path('mm.png');
$image = new \ebi\Image($filename);
$image->resize(100,50)->write($out_png);



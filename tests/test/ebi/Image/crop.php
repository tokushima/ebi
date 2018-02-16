<?php
$filename = \testman\Resource::path('test.jpg');
$out = \ebi\WorkingStorage::path(base64_encode(__FILE__).'.jpg');

$image = new \ebi\Image($filename);
eq(\ebi\Image::ORIENTATION_SQUARE,$image->get_orientation());
$image->crop(100,50)->write($out);
eq(\ebi\Image::ORIENTATION_LANDSCAPE,$image->get_orientation());

eq(true,
	(file_get_contents(\testman\Resource::path('crop_gd.jpg')) == file_get_contents($out)) ||
	(file_get_contents(\testman\Resource::path('crop_im.jpg')) == file_get_contents($out))
);






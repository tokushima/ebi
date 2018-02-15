<?php
$filename = \testman\Resource::path('test.jpg');
//$out = \ebi\WorkingStorage::path(base64_encode(__FILE__).'.jpg');
$out = \ebi\Conf::work_path(base64_encode(__FILE__).'.jpg');

$image = \ebi\Image::jpeg($filename);
$image->crop(100,50)->output($out);






// \ebi\Image::cropping_jpeg($image_file, 100, 50,$out);

eq(true,
	(file_get_contents(\testman\Resource::path('test_crop_gd.jpg')) == file_get_contents($out)) ||
	(file_get_contents(\testman\Resource::path('test_crop_im.jpg')) == file_get_contents($out))
);



// $image_src = file_get_contents($image_file);
// \ebi\Image::cropping_jpeg_from_string($image_src, 100, 50,$out);

// eq(true,
// 	(file_get_contents(\testman\Resource::path('test_crop_gd.jpg')) == file_get_contents($out)) ||
// 	(file_get_contents(\testman\Resource::path('test_crop_im.jpg')) == file_get_contents($out))
// );





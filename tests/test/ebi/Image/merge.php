<?php

// $out_png = \ebi\Conf::work_path('merge_png.png');
// $out_text = \ebi\Conf::work_path('merge_text.png');
$out_png = \ebi\WorkingStorage::path('merge_png.png');
$out_text = \ebi\WorkingStorage::path('merge_text.png');


\ebi\Image::set_font('/System/Library/Fonts/ヒラギノ明朝 ProN.ttc','HIRAMIN');

$img_jpg = new \ebi\Image(\testman\Resource::path('wani.jpg'));
$img_png = new \ebi\Image(\testman\Resource::path('mm.png'));

$img_jpg->merge(10, 10,$img_png);
$img_jpg->write($out_png);




$img_jpg = new \ebi\Image(\testman\Resource::path('wani.jpg'));
$img_text = \ebi\Image::create(300,100);
$img_text->text(16, 40, '#FF0000',16,'HIRAMIN', 'This is a sample.');


$img_jpg->merge(10, 10,$img_text);
$img_jpg->write($out_text);

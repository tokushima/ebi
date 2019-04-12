<?php
$outpath_png = \ebi\Conf::work_path('transparent.png');
$outpath_jpg = \ebi\Conf::work_path('transparent.jpg');
$outpath_mix_jpg = \ebi\Conf::work_path('transparent_mix.jpg');
// $outpath_png = \ebi\WorkingStorage::path('transparent.png');
// $outpath_jpg = \ebi\WorkingStorage::path('transparent.jpg');
// $outpath_mix_jpg = \ebi\WorkingStorage::path('transparent_mix.jpg');

\ebi\Image::set_font('/System/Library/Fonts/ヒラギノ明朝 ProN.ttc','HIRAMIN');


$img_text = \ebi\Image::create(300,100);
$img_text->text(16, 40, '#FF0000',16,'HIRAMIN', 'This is a sample.');
$img_text->text(16, 60, '#0000FF',16,'HIRAMIN', 'This is a sample.');
$img_text->write($outpath_png);
$img_text->write($outpath_jpg);



$img_jpg = new \ebi\Image(\testman\Resource::path('wani.jpg'));
$img_jpg->merge(10, 10,$img_text);

$img_jpg->write($outpath_mix_jpg);


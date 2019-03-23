<?php
$filename = \testman\Resource::path('wani.jpg');

$out_format = \ebi\WorkingStorage::tmpdir().'/'.base64_encode(__FILE__).'_%s.jpg';
//$out_format = \ebi\Conf::work_path(base64_encode(__FILE__).'_%s.jpg');


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_NEGATE);
$image->write(sprintf($out_format,'negate'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_GRAYSCALE);
$image->write(sprintf($out_format,'grayscale'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_EDGEDETECT);
$image->write(sprintf($out_format,'edgedetect'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_EMBOSS);
$image->write(sprintf($out_format,'emboss'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_GAUSSIAN_BLUR);
$image->write(sprintf($out_format,'gauss'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_SELECTIVE_BLUR);
$image->write(sprintf($out_format,'blur'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_MEAN_REMOVAL);
$image->write(sprintf($out_format,'mean'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_BRIGHTNESS,128);
$image->write(sprintf($out_format,'brightness'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_CONTRAST,128);
$image->write(sprintf($out_format,'contrast'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_SMOOTH,4);
$image->write(sprintf($out_format,'smooth'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_PIXELATE,10,true);
$image->write(sprintf($out_format,'pixelate'));


$image = new \ebi\Image($filename);
$image->filter(IMG_FILTER_COLORIZE,128,128,128,30);
$image->write(sprintf($out_format,'colorize'));










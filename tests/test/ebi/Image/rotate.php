<?php
$filename = \testman\Resource::path('test.jpg');

$out_format = \ebi\WorkingStorage::tmpdir().'/'.base64_encode(__FILE__).'_%s.jpg';
//$out_format = \ebi\Conf::work_path(base64_encode(__FILE__).'_%s.jpg');

$image = new \ebi\Image($filename);
$image->rotate(90);
$image->write(sprintf($out_format,90));


$image = new \ebi\Image($filename);
$image->rotate(180);
$image->write(sprintf($out_format,180));


$image = new \ebi\Image($filename);
$image->rotate(270);
$image->write(sprintf($out_format,270));


$image = new \ebi\Image($filename);
$image->rotate(-90);
$image->write(sprintf($out_format,-90));

$image = new \ebi\Image($filename);
$image->rotate(-180);
$image->write(sprintf($out_format,-180));


$image = new \ebi\Image($filename);
$image->rotate(-270);
$image->write(sprintf($out_format,-270));











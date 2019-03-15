<?php

$img = \ebi\Image::create(100,50,'#000000');

list($w,$h) = $img->get_size();

eq(100,$w);
eq(50,$h);


$img->rectangle(5,5,20,10,'#FF0000');
$img->rectangle(5,20,20,10,'#FFFFFF',true);


//$img->write(\ebi\Conf::work_path('img_create.jpg'));
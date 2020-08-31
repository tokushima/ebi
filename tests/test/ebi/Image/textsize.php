<?php
\ebi\Image::set_font('/System/Library/Fonts/ヒラギノ明朝 ProN.ttc','HIRAMIN');

$image = \ebi\Image::create(100,100);
list($w,$h) = $image->get_text_size(16,'HIRAMIN','This is a sample.');
eq(165,$w);
eq(20,$h);


list($w,$h) = $image->get_text_size(16,'HIRAMIN','これはサンプルです。');
eq(192,$w);
eq(22,$h);


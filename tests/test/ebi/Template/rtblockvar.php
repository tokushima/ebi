<?php
$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/rtblockvar/block1');
eq('AAA',$src);


$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/rtblockvar/block2');
eq('BBB',$src);

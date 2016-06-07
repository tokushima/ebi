<?php
$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/allinone.html','abc');
meq('ABC',$src);
mneq('INDEX',$src);
mneq('DEF',$src);
meq('IFOOTER',$src);

$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/allinone.html','def');
meq('DEF',$src);
mneq('INDEX',$src);
mneq('ABC',$src);
mneq('IFOOTER',$src);
meq('DFOOTER',$src);

$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/allinone.html','xyz');
meq('XYZ',$src);
mneq('INDEX',$src);
mneq('ABC',$src);
meq('IFOOTER',$src);

$template = new \ebi\Template();
$src = $template->read(__DIR__.'/resources/allinone.html','index');
meq('INDEX',$src);
meq('IFOOTER',$src);
mneq('DFOOTER',$src);
mneq('ABC',$src);



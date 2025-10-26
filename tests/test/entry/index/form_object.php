<?php
$b = b();

$b->do_get('index::form_obj');
meq('ABC',$b->body());
meq(10,$b->body());
meq(999,$b->body());

$b->do_get(\testman\Util::url('index::form_obj').'?value=XYZ');
mneq('ABC',$b->body());
meq('XYZ',$b->body());
meq(10,$b->body());
meq(999,$b->body());


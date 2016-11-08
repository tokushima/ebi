<?php
$b = b();


$b->do_get(url('index::csrf'));
eq(200,$b->status());
meq('result',$b->body());

$b->do_post(url('index::csrf'));
eq(403,$b->status());
meq('error',$b->body());

$b->do_get(url('index::csrf'));
eq(200,$b->status());
meq('csrftoken',$b->body());
$no = null;
$json = json_decode($b->body(),true);
eq(true,isset($json['result']['csrftoken']));

$b->vars('csrftoken',$json['result']['csrftoken']);
$b->do_post(url('index::csrf'));
eq(200,$b->status());

$b->do_get(url('index::csrf_template'));
eq(200,$b->status());
meq('<form><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="post"><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="get"><input type="hidden" name="csrftoken"',$b->body());
meq(sprintf('<form action="%s"><input type="hidden" name="csrftoken"',url('index::csrf')),$b->body());
meq('<form action="http://localhost"><input type="text" name="aaa" /><input type="submit" /></form>',$b->body());


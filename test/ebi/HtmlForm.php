<?php
$b = new \ebi\Browser();
$b->do_get(url('test_index::form1'));


$b->vars('id1','abc');
\ebi\HtmlForm::submit($b);
meq('ID1=abc',$b->body());

$b->vars('id2','def');
\ebi\HtmlForm::submit($b,'next_form');
meq('ID2=def',$b->body());

$b->vars('id3','ghi');
\ebi\HtmlForm::submit($b);
meq('ID3=ghi',$b->body());


$b->do_get(url('test_index::form_select'));
meq('<select name="data_id"><option value="10">AAA</option><option value="20" selected="selected">BBB</option><option value="30">CCC</option></select>',$b->body());

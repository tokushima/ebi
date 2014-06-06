<?php
$b = new \ebi\Browser();
$b->do_get(test_map_url('test_index::form1'));


$b->vars('id1','abc');
\ebi\HtmlForm::submit($b);
meq('ID1=abc',$b->body());

$b->vars('id2','def');
\ebi\HtmlForm::submit($b,'next_form');
meq('ID2=def',$b->body());

$b->vars('id3','ghi');
\ebi\HtmlForm::submit($b);
meq('ID3=ghi',$b->body());


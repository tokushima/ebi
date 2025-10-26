<?php
// next
$p = new \ebi\Paginator(10,1,100);
eq(true,$p->is_next());
$p = new \ebi\Paginator(10,9,100);
eq(true,$p->is_next());
$p = new \ebi\Paginator(10,10,100);
eq(false,$p->is_next());

$p = new \ebi\Paginator(10,1,100);
eq(2,$p->next());



// prev
$p = new \ebi\Paginator(10,1,100);
eq(false,$p->is_prev());
$p = new \ebi\Paginator(10,9,100);
eq(true,$p->is_prev());
$p = new \ebi\Paginator(10,10,100);
eq(true,$p->is_prev());


$p = new \ebi\Paginator(10,2,100);
eq(1,$p->prev());

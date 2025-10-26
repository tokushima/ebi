<?php
$p = new \ebi\Paginator(10);
eq(10,$p->limit());
eq(1,$p->first());
$p->total(100);
eq(100,$p->total());
eq(10,$p->last());
eq(1,$p->which_first(3));
eq(3,$p->which_last(3));

$p->current(3);
eq(20,$p->offset());
eq(true,$p->is_next());
eq(true,$p->is_prev());
eq(4,$p->next());
eq(2,$p->prev());
eq(1,$p->first());
eq(10,$p->last());
eq(2,$p->which_first(3));
eq(4,$p->which_last(3));

$p->current(1);
eq(0,$p->offset());
eq(true,$p->is_next());
eq(false,$p->is_prev());

$p->current(6);
eq(5,$p->which_first(3));
eq(7,$p->which_last(3));

$p->current(10);
eq(90,$p->offset());
eq(false,$p->is_next());
eq(true,$p->is_prev());
eq(8,$p->which_first(3));
eq(10,$p->which_last(3));


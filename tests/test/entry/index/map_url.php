<?php
$b = b();
$b->do_get(url('index::map_url'));

meq(url('index::template_abc'),$b->body());
mneq(url('index::abc'),$b);


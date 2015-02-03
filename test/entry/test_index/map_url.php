<?php
$b = b();
$b->do_get(url('test_index::map_url'));

meq(url('test_index::template_abc'),$b->body());
mneq(url('test_index::abc'),$b);


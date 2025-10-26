<?php
$b = b();
$b->do_get('index::map_url');

meq(\testman\Util::url('index::template_abc'),$b->body());
mneq(\testman\Util::url('index::abc'),$b);


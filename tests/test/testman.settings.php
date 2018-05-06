<?php
\ebi\Conf::set(\ebi\Db::class,'autocommit',true);

\testman\Conf::set('urls',\ebi\Dt::get_urls());
\testman\Conf::set('ssl-verify',false);


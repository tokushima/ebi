<?php
\ebi\Conf::set('ebi.Db','autocommit',true);

\testman\Conf::set('urls',\ebi\Dt::get_urls());
\testman\Conf::set('ssl-verify',false);
\testman\Conf::set('coverage-dir',dirname(__DIR__).'/src');



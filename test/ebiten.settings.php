<?php
\ebi\Conf::set('ebi.Db','autocommit',true);

\ebiten\Conf::set('urls',\ebi\Dt::get_urls());
\ebiten\Conf::set('ssl-verify',false);
\ebiten\Conf::set('coverage-dir',dirname(__DIR__).'/src');



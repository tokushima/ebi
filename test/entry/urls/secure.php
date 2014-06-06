<?php
/*
meq('http://',test_map_url('urls::newapp#nosecure'));
meq('https://',test_map_url('urls::newapp#secure'));
meq('https://',test_map_url('urls::secure#secure'));

$result_http = <<< PRE
<html>
<body>
<a href="http://localhost/ebi/urls.php/app/nosecure">no</a>
<a href="https://localhost/ebi/urls.php/app/secure">yes</a>
<a href="https://localhost/ebi/urls.php/secure/secure">yes</a>
<a href="http://localhost/abc/def">no</a>
<img src="http://localhost/ebi/resources/media/images/abc.jpg" />yes
<img src="http://localhost/ebi/resources/media/images/abc.jpg" />yes
<img src="http://localhost/images/abc.jpg" />no
</body>
</html>
PRE;

$b = new \testman\Browser();
$b->do_get(test_map_url('urls::newapp#nosecure'));
eq(200,$b->status());
eq($result_http,$b->body());

*/

$result_https = <<< PRE
<html>
<body>
<a href="http://localhost/ebi/urls.php/app/nosecure">no</a>
<a href="https://localhost/ebi/urls.php/app/secure">yes</a>
<a href="https://localhost/ebi/urls.php/secure/secure">yes</a>
<a href="http://localhost/abc/def">no</a>
<img src="https://localhost/ebi/resources/media/images/abc.jpg" />yes
<img src="https://localhost/ebi/resources/media/images/abc.jpg" />yes
<img src="http://localhost/images/abc.jpg" />no
</body>
</html>
PRE;

$b = new \testman\Browser();
$b->do_get(test_map_url('urls::newapp#secure'));
eq(200,$b->status());
eq($result_https,$b->body());

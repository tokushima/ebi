<?php
$t = new \ebi\FlowHelper();
eq("a bc    d ef  g ",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>"));
eq("abcdefg",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>",""));
eq("a-bc----d-ef--g-",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>","-"));


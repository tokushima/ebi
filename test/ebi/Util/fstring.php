<?php
$params = array("A","B","C");
eq("aAbBcCde",\ebi\Util::fstring("a{1}b{2}c{3}d{4}e",$params));
eq("aAbBcAde",\ebi\Util::fstring("a{1}b{2}c{1}d{4}e",$params));
eq("aAbBcAde",\ebi\Util::fstring("a{1}b{2}c{1}d{4}e","A","B","C"));

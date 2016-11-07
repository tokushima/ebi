<?php
/**
 * \ebi\FlowHelper::http_build_query
 */
$t = new \ebi\FlowHelper();
eq('?A=1&B=2&C=3',$t->http_build_query(['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?A=1&B=2&C=3',$t->http_build_query('http://localhost/abc',['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?hoge=1&A=1&B=2&C=3',$t->http_build_query('http://localhost/abc?hoge=1',['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?hoge=1&A=1&B=2&C=3',$t->http_build_query('http://localhost/abc?hoge=1','ABC',['A'=>1,'B'=>2,'C'=>3]));

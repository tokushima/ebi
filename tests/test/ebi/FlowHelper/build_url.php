<?php
/**
 * \ebi\FlowHelper::build_url
 */
$t = new \ebi\FlowHelper();
eq('?A=1&B=2&C=3',$t->build_url(['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?A=1&B=2&C=3',$t->build_url('http://localhost/abc',['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?hoge=1&A=1&B=2&C=3',$t->build_url('http://localhost/abc?hoge=1',['A'=>1,'B'=>2,'C'=>3]));
eq('http://localhost/abc?hoge=1&A=1&B=2&C=3',$t->build_url('http://localhost/abc?hoge=1','ABC',['A'=>1,'B'=>2,'C'=>3]));

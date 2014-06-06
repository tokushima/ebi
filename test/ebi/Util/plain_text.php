<?php
$text = \ebi\Util::plain_text("\n\taaa\n\tbbb");
eq("aaa\nbbb",$text);


$text = \ebi\Util::plain_text("hoge\nhoge");
eq("hoge\nhoge",$text);


$text = \ebi\Util::plain_text("hoge\nhoge\nhoge\nhoge");
eq("hoge\nhoge\nhoge\nhoge",$text);



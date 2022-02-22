<?php
// testman
function eq($expected, $value=null){}
function neq($expected, $value=null){}
function meq($expected, $value=null){}
function mneq($expected, $value=null){}
function fail(?string $msg=null){}
function b(): \testman\Browser{
    return new \testman\Browser();
}
function url(string $map_name): string{
    return '';
}


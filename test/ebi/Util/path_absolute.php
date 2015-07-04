<?php
eq("http://www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute("http://www.ebi.org/","/doc/ja/index.html"));
eq("http://www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute("http://www.ebi.org/","../doc/ja/index.html"));
eq("http://www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute("http://www.ebi.org/","./doc/ja/index.html"));
eq("http://www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja/","./index.html"));
eq("http://www.ebi.org/doc/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja","./index.html"));
eq("http://www.ebi.org/doc/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja/","../index.html"));
eq("http://www.ebi.org/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja/","../../index.html"));
eq("http://www.ebi.org/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja/","../././.././index.html"));
eq("/www/ebi/index.html",\ebi\Util::path_absolute("/www/ebi/","index.html"));
eq("/www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute("/www.ebi.org/doc/ja/","index.html"));
eq("/www.ebi.org/doc/index.html",\ebi\Util::path_absolute("/www.ebi.org/doc/ja/","../index.html"));
eq("/www.ebi.org/doc/ja/action.html/index.html",\ebi\Util::path_absolute('/www.ebi.org/doc/ja/action.html','index.html'));
eq("/www.ebi.org/index.html",\ebi\Util::path_absolute("/www.ebi.org/doc/ja/","../../index.html"));
eq("/www.ebi.org/index.html",\ebi\Util::path_absolute("/www.ebi.org/doc/ja/","../././.././index.html"));
eq("c:/www.ebi.org/doc/index.html",\ebi\Util::path_absolute("c:/www.ebi.org/doc/ja/","../index.html"));
eq("http://www.ebi.org/index.html",\ebi\Util::path_absolute("http://www.ebi.org/doc/ja","/index.html"));
eq("http://www.ebi.org/doc/ja/index.html",\ebi\Util::path_absolute('http://www.ebi.org/doc/ja/action.html','index.html'));
eq("http://www.ebi.org/doc/ja/sample.cgi?param=test",\ebi\Util::path_absolute('http://www.ebi.org/doc/ja/sample.cgi?query=key','?param=test'));
eq("http://www.ebi.org/doc/index.html",\ebi\Util::path_absolute('http://www.ebi.org/doc/ja/action.html', '../../index.html'));
eq("http://www.ebi.org/?param=test",\ebi\Util::path_absolute('http://www.ebi.org/doc/ja/sample.cgi?query=key', '../../../?param=test'));
eq("/doc/ja/index.html",\ebi\Util::path_absolute('/',"/doc/ja/index.html"));
eq("/index.html",\ebi\Util::path_absolute('/',"index.html"));
eq("http://www.ebi.org/login",\ebi\Util::path_absolute("http://www.ebi.org","/login"));
eq("http://www.ebi.org/login",\ebi\Util::path_absolute("http://www.ebi.org/login",""));
eq("http://www.ebi.org/login.cgi",\ebi\Util::path_absolute("http://www.ebi.org/logout.cgi","login.cgi"));
eq("http://www.ebi.org/hoge/login.cgi",\ebi\Util::path_absolute("http://www.ebi.org/hoge/logout.cgi","login.cgi"));
eq("http://www.ebi.org/hoge/login.cgi",\ebi\Util::path_absolute("http://www.ebi.org/hoge/#abc/aa","login.cgi"));
eq("http://www.ebi.org/hoge/abc.html#login",\ebi\Util::path_absolute("http://www.ebi.org/hoge/abc.html","#login"));
eq("http://www.ebi.org/hoge/abc.html#login",\ebi\Util::path_absolute("http://www.ebi.org/hoge/abc.html#logout","#login"));
eq("http://www.ebi.org/hoge/abc.html?abc=aa#login",\ebi\Util::path_absolute("http://www.ebi.org/hoge/abc.html?abc=aa#logout","#login"));
eq("javascript::alert('')",\ebi\Util::path_absolute("http://www.ebi.org/hoge/abc.html","javascript::alert('')"));
eq("mailto::hoge@ebi.org",\ebi\Util::path_absolute("http://www.ebi.org/hoge/abc.html","mailto::hoge@ebi.org"));
eq("http://www.ebi.org/hoge/login.cgi",\ebi\Util::path_absolute("http://www.ebi.org/hoge/?aa=bb/","login.cgi"));
eq("http://www.ebi.org/login",\ebi\Util::path_absolute("http://ebi.org/hoge/hoge","http://www.ebi.org/login"));
eq("http://localhost:8888/spec/css/style.css",\ebi\Util::path_absolute("http://localhost:8888/spec/","./css/style.css"));

eq('phar://C:/abc/def/ghi/xyz.html',\ebi\Util::path_absolute("phar://C:/abc/def/ghi/","xyz.html"));
eq('phar://C:/abc/def/xyz.html',\ebi\Util::path_absolute("phar://C:/abc/def/ghi","../xyz.html"));




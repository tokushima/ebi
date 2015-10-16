<?php
eq("http://www.email.address/doc/ja/index.html",\ebi\Util::path_absolute("http://www.email.address/","/doc/ja/index.html"));
eq("http://www.email.address/doc/ja/index.html",\ebi\Util::path_absolute("http://www.email.address/","../doc/ja/index.html"));
eq("http://www.email.address/doc/ja/index.html",\ebi\Util::path_absolute("http://www.email.address/","./doc/ja/index.html"));
eq("http://www.email.address/doc/ja/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja/","./index.html"));
eq("http://www.email.address/doc/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja","./index.html"));
eq("http://www.email.address/doc/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja/","../index.html"));
eq("http://www.email.address/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja/","../../index.html"));
eq("http://www.email.address/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja/","../././.././index.html"));
eq("/www/ebi/index.html",\ebi\Util::path_absolute("/www/ebi/","index.html"));
eq("/www.email.address/doc/ja/index.html",\ebi\Util::path_absolute("/www.email.address/doc/ja/","index.html"));
eq("/www.email.address/doc/index.html",\ebi\Util::path_absolute("/www.email.address/doc/ja/","../index.html"));
eq("/www.email.address/doc/ja/action.html/index.html",\ebi\Util::path_absolute('/www.email.address/doc/ja/action.html','index.html'));
eq("/www.email.address/index.html",\ebi\Util::path_absolute("/www.email.address/doc/ja/","../../index.html"));
eq("/www.email.address/index.html",\ebi\Util::path_absolute("/www.email.address/doc/ja/","../././.././index.html"));
eq("c:/www.email.address/doc/index.html",\ebi\Util::path_absolute("c:/www.email.address/doc/ja/","../index.html"));
eq("http://www.email.address/index.html",\ebi\Util::path_absolute("http://www.email.address/doc/ja","/index.html"));
eq("http://www.email.address/doc/ja/index.html",\ebi\Util::path_absolute('http://www.email.address/doc/ja/action.html','index.html'));
eq("http://www.email.address/doc/ja/sample.cgi?param=test",\ebi\Util::path_absolute('http://www.email.address/doc/ja/sample.cgi?query=key','?param=test'));
eq("http://www.email.address/doc/index.html",\ebi\Util::path_absolute('http://www.email.address/doc/ja/action.html', '../../index.html'));
eq("http://www.email.address/?param=test",\ebi\Util::path_absolute('http://www.email.address/doc/ja/sample.cgi?query=key', '../../../?param=test'));
eq("/doc/ja/index.html",\ebi\Util::path_absolute('/',"/doc/ja/index.html"));
eq("/index.html",\ebi\Util::path_absolute('/',"index.html"));
eq("http://www.email.address/login",\ebi\Util::path_absolute("http://www.email.address","/login"));
eq("http://www.email.address/login",\ebi\Util::path_absolute("http://www.email.address/login",""));
eq("http://www.email.address/login.cgi",\ebi\Util::path_absolute("http://www.email.address/logout.cgi","login.cgi"));
eq("http://www.email.address/hoge/login.cgi",\ebi\Util::path_absolute("http://www.email.address/hoge/logout.cgi","login.cgi"));
eq("http://www.email.address/hoge/login.cgi",\ebi\Util::path_absolute("http://www.email.address/hoge/#abc/aa","login.cgi"));
eq("http://www.email.address/hoge/abc.html#login",\ebi\Util::path_absolute("http://www.email.address/hoge/abc.html","#login"));
eq("http://www.email.address/hoge/abc.html#login",\ebi\Util::path_absolute("http://www.email.address/hoge/abc.html#logout","#login"));
eq("http://www.email.address/hoge/abc.html?abc=aa#login",\ebi\Util::path_absolute("http://www.email.address/hoge/abc.html?abc=aa#logout","#login"));
eq("javascript::alert('')",\ebi\Util::path_absolute("http://www.email.address/hoge/abc.html","javascript::alert('')"));
eq("mailto::hoge@email.address",\ebi\Util::path_absolute("http://www.email.address/hoge/abc.html","mailto::hoge@email.address"));
eq("http://www.email.address/hoge/login.cgi",\ebi\Util::path_absolute("http://www.email.address/hoge/?aa=bb/","login.cgi"));
eq("http://www.email.address/login",\ebi\Util::path_absolute("http://email.address/hoge/hoge","http://www.email.address/login"));
eq("http://localhost:8888/spec/css/style.css",\ebi\Util::path_absolute("http://localhost:8888/spec/","./css/style.css"));

eq('phar://C:/abc/def/ghi/xyz.html',\ebi\Util::path_absolute("phar://C:/abc/def/ghi/","xyz.html"));
eq('phar://C:/abc/def/xyz.html',\ebi\Util::path_absolute("phar://C:/abc/def/ghi","../xyz.html"));




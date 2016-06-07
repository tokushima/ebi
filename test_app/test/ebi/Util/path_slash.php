<?php
eq("/abc/",\ebi\Util::path_slash("/abc/",null,null));
eq("/abc/",\ebi\Util::path_slash("abc",true,true));
eq("/abc/",\ebi\Util::path_slash("/abc/",true,true));
eq("abc/",\ebi\Util::path_slash("/abc/",false,true));
eq("/abc",\ebi\Util::path_slash("/abc/",true,false));
eq("abc",\ebi\Util::path_slash("/abc/",false,false));

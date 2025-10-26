<?php
/**
 * automapのアノテーションでsecure=falseの指定
 * 
 */
meq('https://',\testman\Util::url('https::auto/index'));
meq('https://',\testman\Util::url('https::auto/def'));
meq('https://',\testman\Util::url(['https::auto/ghi','A']));
meq('https://',\testman\Util::url(['https::auto/jkl','A','B']));
meq('http://',\testman\Util::url('https::auto/nosecure'));

meq('http://',\testman\Util::url('https::abc'));
meq('https://',\testman\Util::url('https::def'));




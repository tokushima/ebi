<?php
/**
 * automapのアノテーションでsecure=falseの指定
 * 
 */
meq('https://',url('https::auto/index'));
meq('https://',url('https::auto/def'));
meq('https://',url('https::auto/ghi','A'));
meq('https://',url('https::auto/jkl','A','B'));
meq('http://',url('https::auto/nosecure'));

meq('http://',url('https::abc'));
meq('https://',url('https::def'));




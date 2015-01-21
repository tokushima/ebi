<?php
namespace ebi\flow\plugin;
/**
 * 拡張タグをTwitter BootstrapのCSSに変換するTemaplteプラグイン
 * @author tokushima
 */
class TwitterBootstrap3Helper{
	private function before_exhtml($src){
		return \ebi\Xml::find_replace($src,'pre|cli|tree',function($xml){
			$plain = $xml->plain();
			$tag = strtolower($xml->name());
			$xml->escape(false);
			$caption = $xml->in_attr('caption');
			$xml->rm_attr('caption');
			$style = $xml->in_attr('style');
			
			if($tag == 'cli'){
				$xml->name('pre');
				$xml->attr('style','background-color:#fff; color:#000; border-color:#000; padding:5px;'.$style);
			}else if($tag == 'tree'){
				$xml->name('pre');
				$xml->attr('style','padding: 5px; line-height: 20px;'.$style);
				$xml->attr('class','prettyprint lang-c');
			}else{
				$xml->attr('class','prettyprint');
			}
			if(empty($caption)){
				$xml->attr('style','margin-top: 20px; '.$xml->in_attr('style'));
			}
			$value = $xml->value();
			$value = preg_replace("/<(rt:.+?)>/ms","&lt;\\1&gt;",$value);
			$value = str_replace(array('<php>','</php>'),array('<?php','?>'),$value);
			$value = $this->pre($value);
			if(empty($value)){
				$value = PHP_EOL;
			}
			
			if($tag == 'tree'){
				$tree = array();
				$len = 0;
				$v = '';
				foreach(explode("\n",$value) as $k => $line){
					if(preg_match("/^(\s*)([\.\w\{\}\[\]\(\)]+)[:]{0,1}(.*)$/",$line,$m)){
						$tree[$k] = array(strlen(str_replace("\t",' ',$m[1])),trim($m[2]),trim($m[3]));
						$tree[$k][3] = strlen($tree[$k][1]);
						if($len < ($tree[$k][3] + $tree[$k][0])) $len = $tree[$k][3] + $tree[$k][0];
					}
				}
				if(!empty($caption)) $v = $caption.PHP_EOL;
				$v .= '.'.PHP_EOL;
				$last = sizeof($tree) - 1;
				foreach($tree as $k => $t){
					$v .= str_repeat('| ',$t[0]);
					$v .= (($t[0] > 0 && isset($tree[$k+1]) && $tree[$k+1][0] < $t[0]) || $k == $last) ? '`' : '|';
					$v .= '-- '.$t[1].str_repeat(' ',$len - $t[3] - ($t[0]*2) + 4).(empty($t[2]) ? '' : ' .. ').$t[2].PHP_EOL;
				}
				$xml->value($v);
				$plain = $xml->get();
			}else{
				$format = $xml->in_attr('format');
				$xml->rm_attr('format');
			
				if($format == 'plain'){
					$plain = $xml->get();
				}else{
					$value = str_replace("\t","&nbsp;&nbsp;",$value);
					$value = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value);
					$xml->value($value);
					$plain = str_replace(array('$','='),array('__RTD__','__RTE__'),$xml->get());
				}
				if(!empty($caption)){
					$plain = '<div style="margin-top:20px; color:#7a43b6; font-weight: bold;">'.$caption.'</div>'.$plain;
				}
			}
		});
	}
	private function after_exec_exhtml($src){
		$src = preg_replace("/<alert>(.+?)<\/alert>/ms",'<p class="alert alert-error">\\1</p>',$src);
		$src = preg_replace("/<information>(.+?)<\/information>/ms",'<p class="alert alert-info">\\1</p>',$src);
		$src = preg_replace("/!!(.+?)!!/ms",'<span class="label label-danger">\\1</span>',$src);
		$src = preg_replace("/##(.+?)##/ms",'<span class="label label-warning">\\1</span>',$src);
		$src = str_replace('<table>','<table class="table table-striped table-bordered table-condensed">',$src);
		$src = str_replace(array('__RTD__','__RTE__'),array('$','='),$src);
		return $src;		
	}
	private function pre($text){
		if(!empty($text)){
			$lines = explode("\n",$text);
			if(sizeof($lines) > 2){
				if(trim($lines[0]) == '') array_shift($lines);
				if(trim($lines[sizeof($lines)-1]) == '') array_pop($lines);
				return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode("\n",$lines)) : implode("\n",$lines);
			}
		}
		return $text;
	}
	/**	
	 * @plugin ebi.Template
	 * @param string $src
	 * @return string
	 */
	public function before_template($src){
		$src = $this->before_exhtml($src);
		return $src;
	}
	/**
	 * @plugin ebi.Template
	 * @param string $src
	 * @return string
	 */	
	public function after_exec_template($src){
		$src = $this->after_exec_exhtml($src);
		return $src;
	}
}
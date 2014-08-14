<?php
namespace ebi\flow\plugin;
/**
 * テンプレートパーツを扱う
 * @author tokushima
 *
 */
class TemplateParts{
	public function init_template($src){
		$path = \ebi\Util::path_slash(\ebi\Conf::get('path',\ebi\Conf::resource_path('parts')),null,true);
		
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:parts');
				$href = \ebi\Util::path_absolute($path,$tag->in_attr('href'));
				
				if(!is_file($href)){
					throw new \InvalidArgumentException($href.' not found');
				}
				$src = str_replace($tag->plain(),file_get_contents($href),$src);
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
}
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
		
		return \ebi\Xml::find_replace($src, 'rt:parts', function($xml) use($path){
			$href = \ebi\Util::path_absolute($path,$xml->in_attr('href'));
			
			if(!is_file($href)){
				throw new \InvalidArgumentException($href.' not found');
			}
			return file_get_contents($href);
		});
	}
}
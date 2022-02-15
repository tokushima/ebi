<?php
namespace ebi\flow\plugin;

class HtmlMinifier{
	/**
	 * @plugin \ebi\Template
	 * @param string $src
	 * @return mixed
	 */
	public function after_exec_template($src){
		$src = preg_replace('/>\s+</','><',$src);
		$src = preg_replace('/\s+</','<',$src);
		$src = preg_replace('/>\s+/','>',$src);
		
		return $src;
	}
}

<?php
namespace ebi\flow\plugin;

class HtmlMinifier{
	/**
	 * @plugin \ebi\Template
	 */
	public function after_exec_template(string $src): string{
		$src = preg_replace('/>\s+</','><',$src);
		$src = preg_replace('/\s+</','<',$src);
		$src = preg_replace('/>\s+/','>',$src);
		
		return $src;
	}
}

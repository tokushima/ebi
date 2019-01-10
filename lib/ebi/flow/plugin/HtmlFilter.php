<?php
namespace ebi\flow\plugin;
/**
 * htmlのフィルタ
 *  - 自動エスケープ処理
 * @author tokushima
 */
class HtmlFilter{
	/**
	 * @plugin ebi.Template
	 * @param string $src
	 * @return string|mixed
	 */	
	public function before_exec_template($src){
		$match = [];
		
		if(preg_match_all('/\$_t_->print_variable\((.+?)\);/ms',$src,$match)){
			$src = str_replace($match[0],array_map(function($value){
				if(strpos($value,'$_t_->htmlencode(') === false
					&& strpos($value,'$t->html(') === false
					&& strpos($value,'$t->text(') === false
					&& strpos($value,'$t->noop(') !== 0
				){
					$value = '$_t_->htmlencode('.$value.')';
				}
				return '$_t_->print_variable('.$value.');';
			},$match[1]),$src);
		}
		return $src;
	}
}
<?php
/**
 * publish API Document
 * @param string $in 対象のエントリファイル @['require'=>true]
 * @param string $out 出力フォルダ
 */
if(empty($out)){
	$out = getcwd().'/classdoc';
}
$resources = dirname(dirname(__DIR__)).'/Dt/resources/apidoc/';

$get_template = function($vars){
	$template = new \ebi\Template();
	$template->cp($vars);
	$template->vars('f',new \ebi\Dt\Helper());
	$template->vars('t',new \ebi\FlowHelper());
	$template->set_class_plugin(new \ebi\flow\plugin\TwitterBootstrap3Helper());
	
	return $template;
};

$class_doc = function($type) use(&$class_doc,$get_template,$out,$resources){
	try{
		$class_vars = (new \ebi\Dt())->class_doc($type);
		$classout = $out.'/classes/'.$class_vars['package'].'.html';
		
		if(!is_file($classout)){
			\cmdman\Std::println('Written '.$class_vars['package']);
			
			$template = $get_template($class_vars);
			\ebi\Util::file_write($out.'/classes/'.$class_vars['package'].'.html',$template->read($resources.'class_doc.html'));
			
			foreach($class_vars['properties'] as $prop){
				$class_doc($prop[0]);
			}
		}
	}catch(\ReflectionException $e){
	}
};

\ebi\Util::rm($out,false);
$entry_vars = (new \ebi\Dt())->index($in);
$template = $get_template($entry_vars);
\ebi\Util::file_write($out.'/index.html',$template->read($resources.'index.html'));

foreach($entry_vars['map_list'] as $info){
	if(isset($info['class']) && isset($info['method'])){
		$method_vars = (new \ebi\Dt())->method_doc($info['class'],$info['method']);
		$template = $get_template($method_vars);
		\ebi\Util::file_write($out.'/classes/'.$info['class'].'/'.$info['method'].'.html',$template->read($resources.'method_doc.html'));
		
		foreach($method_vars['context'] as $context){
			$type = (preg_match('/[\[\]\{\}]{2}$/',$context[0])) ? substr($context[0],0,-2) : $context[0];
			$class_doc($type);
		}
	}
}

\cmdman\Std::println_success('Written '.realpath($out));





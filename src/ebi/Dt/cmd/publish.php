<?php
/**
 * publish API Document
 * @param string $in エントリファイル @['require'=>true]
 * @param string $out 出力フォルダ
 */


$out = getcwd().'/publish';
$resources = dirname(dirname(__DIR__)).'/Dt/resources/publish/';
$self = new \ebi\Dt();

$get_template = function($vars){
	$template = new \ebi\Template();
	$template->cp($vars);
	$template->vars('f',new \ebi\Dt\Helper());
	$template->vars('t',new \ebi\FlowHelper());
	$template->set_class_plugin(new \ebi\flow\plugin\TwitterBootstrap3Helper());
	
	return $template;
};

$class_doc = function($type) use(&$class_doc,$self,$get_template,$out,$resources){
	try{
		$class_vars = $self->class_doc($type);
		$template = $get_template($class_vars);
		\ebi\Util::file_write($out.'/classes/'.$class_vars['package'].'.html',$template->read($resources.'class_doc.html'));
		
		foreach($class_vars['properties'] as $prop){
			$class_doc($prop[0]);
		}
	}catch(\ReflectionException $e){
	}
};

$entry_vars = $self->index($in);
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




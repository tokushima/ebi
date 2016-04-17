<?php
/**
 * publish Classes Document
 * @param string $out 出力フォルダ
 */
$resources = dirname(dirname(__DIR__)).'/Dt/resources/classdoc/';

if(empty($out)){
	$out = getcwd().'/classdoc';
}

$get_template = function($vars){
	$template = new \ebi\Template();
	$template->cp($vars);
	$template->vars('f',new \ebi\Dt\Helper());
	$template->vars('t',new \ebi\FlowHelper());
	$template->set_class_plugin(new \ebi\flow\plugin\TwitterBootstrap3Helper());
	
	return $template;
};

$class_doc = function($class) use(&$class_doc,$get_template,$out,$resources){
	try{
		$class_vars = (new \ebi\Dt())->class_doc($class);
		$classout = $out.'/classes/'.$class_vars['package'].'.html';
		
		if(!is_file($classout)){
			\cmdman\Std::println('Written '.$class_vars['package']);
			
			$template = $get_template($class_vars);
			\ebi\Util::file_write($classout,$template->read($resources.'class_doc.html'));
			
			foreach($class_vars['properties'] as $prop){
				$class_doc($prop[0]);
			}
			foreach([
				'static_methods',
				'methods',
				'protected_static_methods',
				'protected_methods',
				'inherited_static_methods',
				'inherited_methods',
				'inherited_protected_static_methods',
				'inherited_protected_methods',
			] as $info_name){
				foreach($class_vars[$info_name] as $method => $summary){
					$method_vars = (new \ebi\Dt())->method_doc($class_vars['package'],$method);
					$template = $get_template($method_vars);
					\ebi\Util::file_write($out.'/classes/'.$class_vars['package'].'/'.$method.'.html',$template->read($resources.'method_doc.html'));
				}
			}
			foreach($class_vars['plugins'] as $plugin_name => $info){
				$plugin_vars = [
					'package'=>$class,
					'plugin_name'=>$plugin_name,
					'description'=>$info[0],
					'params'=>$info[1],
					'return'=>$info[2],
				];
				$template = $get_template($plugin_vars);
				\ebi\Util::file_write($out.'/classes/'.$class_vars['package'].'/plugins/'.$plugin_name.'.html',$template->read($resources.'plugin_doc.html'));
			}
			foreach($class_vars['conf_list'] as $conf_name => $info){
				$conf_vars = [
					'package'=>$class,
					'conf_name'=>$conf_name,
					'description'=>$info[0],
					'params'=>$info[1],
					'return'=>$info[2],
				];
				$template = $get_template($conf_vars);
				\ebi\Util::file_write($out.'/classes/'.$class_vars['package'].'/config/'.$conf_name.'.html',$template->read($resources.'conf_doc.html'));
			}
		}
	}catch(\ReflectionException $e){
	}
};

\ebi\Util::rm($out,false);
$class_list_vars = (new \ebi\Dt())->class_list();
$template = $get_template($class_list_vars);
\ebi\Util::file_write($out.'/index.html',$template->read($resources.'class_list.html'));

foreach($class_list_vars['class_list'] as $class => $summary){
	$class_doc($class);
}


\cmdman\Std::println_success('Written '.realpath($out));





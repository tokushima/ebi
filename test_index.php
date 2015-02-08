<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'ABC'=>['action'=>'test.flow.AutoAction'],
	'DEF/(.+)/(.+)'=>['action'=>'test.flow.AutoAction::jkl'],
	'template_abc'=>['name'=>'template_abc','template'=>'abc.html'],
	'template_abc/def'=>['name'=>'template_def','template'=>'abc.html'],
	'template_abc/def/(.+)'=>['name'=>'template_def_arg1','template'=>'abc.html'],
	'template_abc/def/(.+)/(.+)'=>['name'=>'template_def_arg2','template'=>'abc.html'],
	'redirect_url'=>['redirect'=>'http://ebi.org'],
	'redirect_map'=>['redirect'=>'template_defa'],
	'package/action'=>['action'=>'test.flow.PackageAction'],
	'request'=>['action'=>'test.flow.RequestAction::index'],
	'request/redirect'=>['action'=>'test.flow.RequestAction::redirect'],
	'group'=>[
		'patterns'=>[
			'aaa'=>['name'=>'group_aaa','action'=>'test.flow.Action::abc'],
			'bbb'=>['name'=>'group_bbb','action'=>'test.flow.Action::abc'],
		],
	],
	'template_parent'=>['name'=>'template_parent','template'=>'parent.html'],
	'template_child'=>['name'=>'template_child','template'=>'child.html'],
	'template_grandchild'=>['name'=>'template_grandchild','template'=>'grandchild.html'],
	'template_grandchild_super'=>['name'=>'template_grandchild_super','template'=>'grandchild.html','template_super'=>'super.html'],
	
	'map_url'=>['template'=>'map_url.html'],
	'xml'=>[
		'patterns'=>[
			'aaa'=>['name'=>'group_aaa_xml','action'=>'test.flow.Action::abc'],
			'bbb'=>['name'=>'group_bbb_xml','action'=>'test.flow.Action::abc'],
		],
		'plugins'=>['ebi.flow.plugin.Xml'],
	],
	
	'after'=>['name'=>'after','action'=>'test.flow.Sample::after_redirect','after'=>'after_to'],
	'after/to'=>['name'=>'after_to','action'=>'test.flow.Sample::after_to'],
	'after/to/arg1'=>['name'=>'after_arg1','action'=>'test.flow.Sample::after_redirect','after'=>['after_to_arg1','next_var_A']],
	'after/to/(.+)'=>['name'=>'after_to_arg1','action'=>'test.flow.Sample::after_to'],
	'after/to/arg2'=>['name'=>'after_arg2','action'=>'test.flow.Sample::after_redirect','after'=>['after_to_arg2','next_var_A','next_var_B']],
	'after/to/(.+)/(.+)'=>['name'=>'after_to_arg2','action'=>'test.flow.Sample::after_to'],
	
	'post_after'=>['name'=>'post_after','action'=>'test.flow.Sample::after_redirect','post_after'=>'post_after_to'],
	'post_after/to'=>['name'=>'post_after_to','action'=>'test.flow.Sample::after_to'],
	'post_after/to/arg1'=>['name'=>'post_after_arg1','action'=>'test.flow.Sample::after_redirect','after'=>['post_after_to_arg1','next_var_A']],
	'post_after/to/(.+)'=>['name'=>'post_after_to_arg1','action'=>'test.flow.Sample::after_to'],
	'post_after/to/arg2'=>['name'=>'post_after_arg2','action'=>'test.flow.Sample::after_redirect','after'=>['post_after_to_arg2','next_var_A','next_var_B']],
	'post_after/to/(.+)/(.+)'=>['name'=>'post_after_to_arg2','action'=>'test.flow.Sample::after_to'],
	
	'helper/range'=>['name'=>'helper_range','template'=>'helper/range.html','vars'=>['max'=>5]],
	'raise'=>['name'=>'raise','action'=>'test.flow.Action::raise'],
	'raise/template'=>['name'=>'raise_template','action'=>'test.flow.Action::raise','error_template'=>'exceptions.html'],
	'raise/template/parent'=>['name'=>'raise_template_parent','action'=>'test.flow.Action::raise','error_template'=>'exceptions_parent.html'],
	'exceptions'=>['name'=>'exceptions','action'=>'test.flow.Action::exceptions'],

	'html_filter'=>[
		'name'=>'html_filter',
		'template'=>'html_filter.html',
		'vars'=>[
			'aaa'=>'hogehoge',
			'ttt'=>'<tag>ttt</tag>',
			'bbb'=>'hoge',
			'XYZ'=>'B',
			'xyz'=>['A'=>'456','B'=>'789','C'=>'010'],
			'ddd'=>['456','789'],
			'eee'=>true,
			'fff'=>false,
	
			'ppp'=>'PPPPP',
			'qqq'=>'<tag>QQQ</tag>',
		],
		'plugins'=>['ebi.flow.plugin.HtmlFilter']
	],
	'csrf'=>[
		'name'=>'csrf',
		'action'=>'ebi.flow.Request::noop',
		'plugins'=>['ebi.flow.plugin.Csrf'],
	],
	'csrf_template'=>[
		'name'=>'csrf_template',
		'action'=>'ebi.flow.Request::noop',
		'plugins'=>['ebi.flow.plugin.Csrf'],
		'template'=>'csrf.html',
	],
	'log'=>[
		'name'=>'log',
		'action'=>'test.flow.Action::log',
		'template'=>'log.html',	
	],
	
	'status/400'=>['name'=>'status400','action'=>'ebi.flow.HttpStatus::bad_request'],
	'status/403'=>['name'=>'status403','action'=>'ebi.flow.HttpStatus::forbidden'],
	'status/404'=>['name'=>'status404','action'=>'ebi.flow.HttpStatus::not_found'],
	'status/405'=>['name'=>'status405','action'=>'ebi.flow.HttpStatus::method_not_allowed'],
	'status/406'=>['name'=>'status406','action'=>'ebi.flow.HttpStatus::not_acceptable'],
	'status/409'=>['name'=>'status409','action'=>'ebi.flow.HttpStatus::conflict'],
	'status/410'=>['name'=>'status410','action'=>'ebi.flow.HttpStatus::gone'],
	'status/415'=>['name'=>'status415','action'=>'ebi.flow.HttpStatus::unsupported_media_type'],
	'status/500'=>['name'=>'status500','action'=>'ebi.flow.HttpStatus::internal_server_error'],
	'status/503'=>['name'=>'status503','action'=>'ebi.flow.HttpStatus::service_unavailable'],
	
	
	'form1'=>['name'=>'form1','template'=>'form1.html','action'=>'ebi.flow.Request::noop'],
	'form2'=>['name'=>'form2','template'=>'form2.html','action'=>'ebi.flow.Request::noop'],
	
	'abc'=>['name'=>'abc','action'=>'test.db.Abc::create'],
	
	'get_method'=>['name'=>'ge_method','action'=>'test.flow.Action::get_method'],
	'dt'=>['action'=>'ebi.Dt']
]);



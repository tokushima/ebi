<?php
include_once('bootstrap.php');
/**
 * テストエントリ
 */

\ebi\Flow::app([
	'ABC'=>[
		'action'=>'test.flow.AutoAction',
		'version'=>'20161123'
	],
	'DEF/(.+)/(.+)'=>['action'=>'test.flow.AutoAction::jkl'],
	'template_abc'=>['name'=>'template_abc','template'=>'abc.html'],
	'template_abc/def'=>['name'=>'template_def','template'=>'abc.html'],
	'template_abc/def/(.+)'=>['name'=>'template_def_arg1','template'=>'abc.html'],
	'template_abc/def/(.+)/(.+)'=>['name'=>'template_def_arg2','template'=>'abc.html'],
	'redirect_url'=>['redirect'=>'http://email.address'],
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
			'eee'=>['name'=>'group_eee_xml','action'=>'test.flow.Action::raise'],			
		],
		'plugins'=>['ebi.flow.plugin.Xml'],
	],
	
	'after'=>['name'=>'after','action'=>'test.flow.Sample::after_redirect','after'=>'after_to'],
	'after/to'=>['name'=>'after_to','action'=>'test.flow.Sample::after_to'],
	'after/to/arg1'=>['name'=>'after_arg1','action'=>'test.flow.Sample::after_redirect','after'=>['after_to_arg1','@next_var_A']],
	'after/to/(.+)'=>['name'=>'after_to_arg1','action'=>'test.flow.Sample::after_to'],
	'after/to/arg2'=>['name'=>'after_arg2','action'=>'test.flow.Sample::after_redirect','after'=>['after_to_arg2','@next_var_A','@next_var_B']],
	'after/to/(.+)/(.+)'=>['name'=>'after_to_arg2','action'=>'test.flow.Sample::after_to'],
	
	'post_after'=>['name'=>'post_after','action'=>'test.flow.Sample::after_redirect','post_after'=>'post_after_to'],
	'post_after/to'=>['name'=>'post_after_to','action'=>'test.flow.Sample::after_to'],
	'post_after/to/arg1'=>[
		'name'=>'post_after_arg1',
		'action'=>'test.flow.Sample::after_redirect',
		'after'=>['post_after_to_arg1','@next_var_A'],
	],
	'post_after/to/(.+)'=>['name'=>'post_after_to_arg1','action'=>'test.flow.Sample::after_to'],
	'post_after/to/arg2'=>['name'=>'post_after_arg2','action'=>'test.flow.Sample::after_redirect','after'=>['post_after_to_arg2','@next_var_A','@next_var_B']],
	'post_after/to/(.+)/(.+)'=>['name'=>'post_after_to_arg2','action'=>'test.flow.Sample::after_to'],
	
	'helper/range'=>['name'=>'helper_range','template'=>'helper/range.html','vars'=>['max'=>5]],
	'raise'=>['name'=>'raise','action'=>'test.flow.Action::raise'],
	'raise/template'=>['name'=>'raise_template','action'=>'test.flow.Action::raise','error_template'=>'exceptions.html'],
	'raise/template/parent'=>['name'=>'raise_template_parent','action'=>'test.flow.Action::raise','error_template'=>'exceptions_parent.html'],
	'exceptions'=>['name'=>'exceptions','action'=>'test.flow.Action::exceptions'],
	'exceptions/403'=>['name'=>'exceptions403','action'=>'test.flow.Action::exceptions','error_status'=>403],
	'exceptions/405'=>['name'=>'exceptions405','action'=>'test.flow.Action::exceptions405'],
	'exceptions/group'=>['name'=>'exceptions_group','action'=>'test.flow.Action::exceptions_group'],
	
		
	'model_list'=>['name'=>'model_list','action'=>'test.flow.Sample::model_list'],
		
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
	'form/obj'=>['name'=>'form_obj','template'=>'form_obj.html','action'=>'test.flow.Action::form_obj'],
	'form1/esc'=>['name'=>'form1esc','template'=>'form1ref.html','action'=>function(){
		return ['id1'=>'A\'"<>','id2'=>'ABC'];
	}],
	'form/select'=>['name'=>'form_select','template'=>'form_select.html','action'=>'test.flow.Action::select'],
	'form/select/obj'=>['name'=>'form_select_obj','template'=>'form_select_obj.html','action'=>'test.flow.Action::select_obj'],
		
	'abc'=>['name'=>'abc','action'=>'test.db.Abc::create'],
	
	'form/file'=>['name'=>'file_form','template'=>'file.html'],
	'form/file/upload'=>['name'=>'file_upload','action'=>'test.flow.RequestFlow::file_upload'],
		
	'get_method'=>['name'=>'ge_method','action'=>'test.flow.Action::get_method'],
	
	'flow/request/require/vars'=>['name'=>'require_vars','action'=>'test.flow.RequestFlow::require_vars'],
	'flow/request/require/post'=>['name'=>'require_post','action'=>'test.flow.RequestFlow::require_post'],
	'flow/request/require/get'=>['name'=>'require_get','action'=>'test.flow.RequestFlow::require_get'],
	'flow/request/require/invalid/annon'=>['name'=>'require_vars_invalid_anon','action'=>'test.flow.RequestFlow::require_vars_annotation_error'],
	'flow/request/type/email'=>['name'=>'require_type_email','action'=>'test.flow.RequestFlow::require_var_type'],
	'flow/request/vars'=>[
		'name'=>'requestflow_vars',
		'action'=>'test.flow.RequestFlow::get_vars',
	],
	'flow/request/vars/template'=>[
		'name'=>'requestflow_vars_template',
		'action'=>'test.flow.RequestFlow::get_vars',
		'template'=>'requestflow_vars.html'
	],
	'flow/request/vars/callback'=>[
		'name'=>'requestflow_vars_callback',
		'action'=>'test.flow.RequestFlow::get_vars',
		'plugins'=>[
			'test.flow.plugin.Callback',
			\test\flow\plugin\Callback2::class,
		],
	],
	'flow/request/vars/callback_addvars'=>[
		'name'=>'requestflow_vars_callback_addvars',
		'action'=>'test.flow.RequestFlow::get_vars',
		'plugins'=>['test.flow.plugin.Callback','test.flow.plugin.AddVars'],
	],
	'flow/request/mail'=>[
		'name'=>'requestflow_mail',
		'action'=>'test.flow.RequestFlow::sendmail',
	],
	
	'http/method/vars'=>[
		'name'=>'http_method_vars',
		'action'=>function(){
			return [
				'post'=>(isset($_POST) ? $_POST : []),
				'get'=>(isset($_GET) ? $_GET : []),
				'raw'=>file_get_contents('php://input'),
			];
		},
	],
	'deprecated/method'=>[
		'name'=>'deprecated_method',
		'action'=>'test.flow.Action::deprecated',
	],
	'deprecated/request'=>[
		'name'=>'deprecated_request',
		'action'=>'test.flow.Action::request_deprecated',
	],
	'deprecated/context'=>[
		'name'=>'context_deprecated',
		'action'=>'test.flow.Action::context_deprecated',
		'version'=>'20161111',
	],
	
	'deprecated/entry'=>[
		'name'=>'deprecated',
		'action'=>'\\test\\flow\\Action::abc',
		'deprecated'=>true,
		'version'=>'20161112',
	],
	
	
	'working'=>[
		'name'=>'working_storage',
		'action'=>'test.flow.Action::working_storage',
	],
	'dt'=>['action'=>'ebi.Dt','mode'=>'@dev'],
]);



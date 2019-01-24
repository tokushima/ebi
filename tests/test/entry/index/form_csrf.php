<?php
$b = b();

$b->do_get('index::form_csrf');
meq('name="csrftoken"',$b->body());

$bool = false;
foreach($b->xml('form')->find('input') as $input){
	if($input->in_attr('name') == 'csrftoken'){
		$csrftoken = $input->in_attr('value');
		
		$b->vars('csrftoken',$csrftoken);
		$b->vars('id1','AAAA');
		$b->do_post('index::form_csrf');
		
		eq(200,$b->status());
		meq('AAAA',$b->body());
		$bool = true;
	}
}
eq(true,$bool);


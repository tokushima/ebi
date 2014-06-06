<?php
\ebi\Queue::set_class_plugin(new \ebi\queue\plugin\Dao());

for($i=1;$i<=5;$i++){
	\ebi\Queue::insert('test',$i);
}
for($i=1;$i<=5;$i++){
	$model = \ebi\Queue::get('test');
	eq($i,$model->data());
	\ebi\Queue::finish($model);
}




for($i=1;$i<=5;$i++){
	\ebi\Queue::insert('test',$i);
}

$i = 0;
foreach(\ebi\Queue::gets(5,'test') as $model){
	$i++;	
	eq($i,$model->data()); // ロックだけする
}
eq(5,$i);
\ebi\Queue::reset('test',-86400); // 未来を指定してリセット

$i = 0;
foreach(\ebi\Queue::gets(5,'test') as $model){
	$i++;
	eq($i,$model->data());
	\ebi\Queue::finish($model);
}
eq(5,$i);

eq(10,\ebi\queue\plugin\Dao\QueueDao::find_count());
\ebi\Queue::clean('test',time(),3);

eq(7,\ebi\queue\plugin\Dao\QueueDao::find_count());
\ebi\Queue::clean('test');
eq(0,\ebi\queue\plugin\Dao\QueueDao::find_count());

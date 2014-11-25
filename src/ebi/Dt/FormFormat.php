<?php
namespace ebi\Dt;

class FormFormat{
	public function after_exec_template($src){
		$xml = \ebi\Xml::anonymous($src);
		
		foreach($xml->find('input') as $form){
			$type = $form->in_attr('rtdt:type');
			$v = $form->in_attr('value');

			if(!empty($type)){
				if(!empty($v)){

					switch($type){
						case 'date':
							$form->attr('value',date('Y/m/d',$v));
							break;
						case 'timestamp':
							$form->attr('value',date('Y/m/d H:i:s',$v));
							break;
						case 'time':
							$h = floor($v / 3600);
							$i = floor(($v - ($h * 3600)) / 60);
							$s = floor($v - ($h * 3600) - ($i * 60));
							$m = str_replace(' ','0',rtrim(str_replace('0',' ',(substr(($v - ($h * 3600) - ($i * 60) - $s),2,12)))));
							$form->attr('value',(($h == 0) ? '' : $h.':').(sprintf('%02d:%02d',$i,$s)).(($m == 0) ? '' : '.'.$m));
							break;
					}
				}
				$form->rm_attr('rtdt:type');
				$src = str_replace($form->plain(),$form->get(),$src);
			}
		}
		return $src;
	}
}

<?php
namespace ebi;
/**
 * formタグを処理する
 * @author tokushima
 */
class HtmlForm{
	private static function parse($html,$url){
		$forms = [];
		try{
			foreach(\ebi\Xml::extract($html,'body')->find('form') as $key => $formtag){
				$form = new \stdClass();
				$form->name = $formtag->in_attr('name',$formtag->in_attr('id',$key));
				$form->action = \ebi\Util::path_absolute($url,$formtag->in_attr('action',$url));
				$form->method = strtolower($formtag->in_attr('method','get'));
				$form->multiple = false;
				$form->element = [];
				
				foreach($formtag->find('input') as $count => $input){
					$obj = new \stdClass();
					$obj->name = $input->in_attr('name',$input->in_attr('id','input_'.$count));
					$obj->type = strtolower($input->in_attr('type','text'));
					$obj->value = self::htmldecode($input->in_attr('value'));
					$obj->selected = ('selected' === strtolower($input->in_attr('checked',$input->in_attr('checked'))));
					$obj->multiple = false;
					$form->element[] = $obj;
				}
				foreach($formtag->find('textarea') as $count => $input){
					$obj = new \stdClass();
					$obj->name = $input->in_attr('name',$input->in_attr('id','textarea_'.$count));
					$obj->type = 'textarea';
					$obj->value = self::htmldecode($input->value());
					$obj->selected = true;
					$obj->multiple = false;
					$form->element[] = $obj;
				}
				foreach($formtag->find('select') as $count => $input){
					$obj = new \stdClass();
					$obj->name = $input->in_attr('name',$input->in_attr('id','select_'.$count));
					$obj->type = 'select';
					$obj->value = [];
					$obj->selected = true;
					$obj->multiple = ('multiple' == strtolower($input->param('multiple',$input->attr('multiple'))));
		
					foreach($input->find('option') as $count => $option){
						$op = new \stdClass();
						$op->value = self::htmldecode($option->in_attr('value',$option->value()));
						$op->selected = ('selected' == strtolower($option->in_attr('selected',$option->in_attr('selected'))));
						$obj->value[] = $op;
					}
					$form->element[] = $obj;
				}
				$forms[] = $form;
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $forms;
	}
	/**
	 * formをsubmitする
	 * @param \ebi\Browser $b
	 * @param string $form FORMタグの名前、または順番
	 * @param string $submit 実行するINPUTタグ(type=submit)の名前
	 * @return \ebi\Browser
	 */
	public static function submit(\ebi\Browser $b,$form=0,$submit=null){
		$forms = self::parse($b->body(),$b->url());
		
		foreach($forms as $key => $f){
			if($f->name === $form || $key === $form){
				$form = $key;
				break;
			}
		}
		if(isset($forms[$form])){
			$inputcount = 0;
			$onsubmit = ($submit === null);
			
			foreach($forms[$form]->element as $element){
				switch($element->type){
					case 'hidden':
					case 'textarea':
						if(!$b->has_vars($element->name)){
							$b->vars($element->name,$element->value);
						}
						break;
					case 'text':
					case 'password':
						$inputcount++;
						if(!$b->has_vars($element->name)) $b->vars($element->name,$element->value); break;
						break;
					case 'checkbox':
					case 'radio':
						if($element->selected !== false){
							if(!$b->has_vars($element->name)) $b->vars($element->name,$element->value);
						}
						break;
					case 'submit':
					case 'image':
						if(($submit === null && $onsubmit === false) || $submit == $element->name){
							$onsubmit = true;
							if(!$b->has_vars($element->name)) $b->vars($element->name,$element->value);
							break;
						}
						break;
					case 'select':
						if(!$b->has_vars($element->name)){
							if($element->multiple){
								$list = [];
								foreach($element->value as $option){
									if($option->selected) $list[] = $option->value;
								}
								$b->vars($element->name,$list);
							}else{
								foreach($element->value as $option){
									if($option->selected){
										$b->vars($element->name,$option->value);
									}
								}
							}
						}
						break;
					case 'button':
						break;
				}
			}
			if($onsubmit || $inputcount == 1){
				return ($forms[$form]->method == 'post') ?
				$b->do_post($forms[$form]->action) :
				$b->do_get($forms[$form]->action);
			}
		}
		return $b;
	}
	private static function htmldecode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			$value = preg_replace_callback("/&#[xX]([0-9a-fA-F]+);/u",function($m){return '&#'.hexdec($m[1]).';';},$value);
			$value = mb_decode_numericentity($value,[0x0,0x10000,0,0xfffff],'UTF-8');
			$value = html_entity_decode($value,ENT_QUOTES,"UTF-8");
			$value = str_replace(["\\\"","\\'","\\\\"],["\"","\'","\\"],$value);
		}
		return $value;
	}
}

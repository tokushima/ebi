<?php
namespace ebi\flow\plugin;
/**
 * Xmlで出力するFlowプラグイン
 * @author tokushima
 */
class Xml{
	/*
	 * @plugin ebi.Flow
	 */
	public function flow_output($array){
		if(strpos(strtolower((new \ebi\Env())->get('HTTP_ACCEPT')),'application/json') !== false){
			\ebi\HttpHeader::send('Content-Type','application/json');
			print(\ebi\Json::encode(['result'=>\ebi\Util::to_primitive($array)]));
		}else{
			$xml = new \ebi\Xml('result');
			$xml->add($array);
			
			\ebi\HttpHeader::send('Content-Type','application/xml');
			/**
			 * @param string $encoding XMLのencodingに指定するエンコード名
			 */
			print($xml->get(\ebi\Conf::get('encoding')));
		}
	}
	/**
	 * @plugin ebi.Flow
	 * @param \Exception $exception
	 */
	public function flow_exception(\Exception $exception){
		if(!($exception instanceof \ebi\Exceptions)){
			$exception = [''=>$exception];
		}
		if(strpos(strtolower((new \ebi\Env())->get('HTTP_ACCEPT')),'application/json') !== false){
			$message = [];
				
			foreach($exception as $g => $e){
				$em = [
					'message'=>$e->getMessage(),
					'type'=>basename(str_replace("\\",'/',get_class($e)))
				];
				if(!empty($g)){
					$em['group'] = $g;
				}
				$message[] = $em;
			}
			\ebi\HttpHeader::send('Content-Type','application/json');
			print(\ebi\Json::encode(['error'=>$message]));
		}else{
			$xml = new \ebi\Xml('error');
			
			foreach($exception as $g => $e){
				$message = new \ebi\Xml('message',$e->getMessage());
				$type = basename(str_replace("\\",'/',get_class($e)));
				
				if(!empty($g)){
					$message->add('group',$g);
				}
				$message->add('type',$type);
				$xml->add($message);
			}
			\ebi\HttpHeader::send('Content-Type','application/xml');
			/**
			 * @param string $encoding XML宣言のencoding
			 */
			print($xml->get(\ebi\Conf::get('encoding')));
		}
	}
}
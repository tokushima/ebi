<?php
namespace ebi;
/**
 * Server-sent events
 * ```
 * var es = new EventSource('aerver_sent_events.php');
 * es.addEventListener('close', function (event) {
 * 	es.close();
 * });
 * es.addEventListener("message",function(payload){
 * 	var data = JSON.parse(payload.data);
 * 	document.getElementById("view").innerHTML = data.value;
 * });
 * 
 * ```
 * 
 * @author tokushima
 * @see https://developer.mozilla.org/ja/docs/Web/API/EventSource
 *
 */
class EventStream{
	public function __construct(){
		\ebi\HttpHeader::send('Cache-Control','no-cache');
		\ebi\HttpHeader::send('Content-Type','text/event-stream');
	}
	public function __destruct(){
		$this->close();
	}
	
	/**
	 * Send message
	 * @param array $data
	 */
	public function message(array $data){
		$this->send('message', $data);
	}
	
	/**
	 * Send close message
	 */
	public function close(){
		$this->send('close',[]);
	}

	/**
	 * send custome event message
	 * @param string $event
	 * @param array $data
	 */
	public function send($event,array $data){
		print('event: '.$event.PHP_EOL);
		print('data: '.json_encode($data).PHP_EOL.PHP_EOL);

		if(ob_get_level() > 0){
			ob_flush();
		}
	}
}



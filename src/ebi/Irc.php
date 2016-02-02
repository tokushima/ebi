<?php
namespace ebi;
/**
 * IRCクライアント
 * @author tokushima
 *
 */
class Irc{
	use \ebi\Plugin;
	
	private $fp;
	private $ch;
	private $nick;
	
	private $channel;
	private $nickname;
	private $message;
	
	private $read = false;

	public function __construct($address,$port,$channel,$nick){
		$this->fp = fsockopen($address,$port,$errno,$errstr,1);	
		if(!empty($errno)){
			throw new \RuntimeException($errstr);
		}
		$this->nick = $nick;
		$this->ch = '#'.$channel;
		
		fwrite($this->fp,'NICK '.$this->nick."\r\n");
		fwrite($this->fp,'USER '.$this->nick.' localhost * : '.$this->nick."\r\n");
		fwrite($this->fp,'JOIN '.$this->ch."\r\n");

		while(true){
			$msg = fgets($this->fp);
			list(,$st) = explode(' ',$msg,4);
			
			if($st === '004'){
				break;
			}else if(substr($st,0,1) == '4'){
				throw new \RuntimeException($msg);
			}
		}
	}
	/**
	 * 発言されたチャンネル
	 * @return string
	 */
	public function channel(){
		return $this->channel;
	}
	/**
	 * 発言されたメッセージ
	 * @return string
	 */
	public function message(){
		return $this->message;
	}
	/**
	 * 発言されたニックネーム
	 * @return string
	 */
	public function nickname(){
		return $this->nickname;
	}
	/**
	 * 発言をまつ
	 */
	public function read(){
		if(!$this->read){
			$this->read = true;
			
			while(true){
				$msg = fgets($this->fp);
				if($msg !== false){
					if(strpos($msg,'PRIVMSG') !== false){
						list($nick,,$ch,$m) = explode(' ',$msg,4);
						list($this->nickname) = explode('!',substr($nick,1),2);
						$this->message = substr($m,1);
						$this->channel = $ch;
						
						if($this->has_object_plugin('privmsg')){
							/**
							 * PRIVMSG
							 * @param \ebi\Irc $arg1
							 */
							$this->call_object_plugin_funcs('privmsg',$this);
						}else{
							print(sprintf('%s: %s',$this->nickname,$this->message));
						}
					}else if(strpos($msg,'PING :') === 0){					
						fwrite($this->fp,'PONG :localhost'."\r\n");
					}
				}
			}
		}
	}
	/**
	 * 発言する
	 * @param string $msg
	 */
	public function write($msg){
		$this->message = $msg."\r\n";
		$this->channel = $this->ch;
		$this->nickname = $this->nick;
				
		fwrite($this->fp,'PRIVMSG '.$this->channel.' :'.$this->message);
		
		if($this->has_object_plugin('privmsg')){
			$this->call_object_plugin_funcs('privmsg',$this);
		}
	}
	/**
	 * 発言する
	 * @param string $msg
	 */
	public function notice($msg){
		fwrite($this->fp,'NOTICE '.$this->ch.' :'.$msg."\r\n");		
	}
	public function __destruct(){
		if(isset($this->fp)){
			@fwrite($this->fp,'PART '.$this->ch."\r\n");
			@fclose($this->fp);
		}
	}
}
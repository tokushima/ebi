<?php
namespace ebi;
/**
 * Mail
 * @author tokushima
 */
class Mail{
	use \ebi\Plugin;
	
	private $attach;
	private $media;
	
	private $from;
	private $sender_name;
	private $return_path;
	private $to = [];
	private $cc = [];
	private $bcc = [];
	private $header = [];
	
	private $subject;
	private $message;
	private $html;
	
	private $eol = "\n";
	private $boundary = ['mixed'=>'mixed','alternative'=>'alternative','related'=>'related'];

	public function __construct(){
		$this->boundary = ['mixed'=>'----=_Part_'.uniqid('mixed'),'alternative'=>'----=_Part_'.uniqid('alternative'),'related'=>'----=_Part_'.uniqid('related')];
	}
	/**
	 * Set from address
	 * @param string $address
	 * @param string $sender_name
	 */
	public function from($address,$sender_name=null){
		$this->sender_name = $sender_name;
		$this->from = $address;
		$this->return_path = $address;
	}
	/**
	 * Set from-Address
	 * @param string $address
	 * @param string $name
	 */
	public function to($address,$name=''){
		$this->to[$address] = $this->address($address,$name);
	}
	/**
	 * Add to-Address
	 * @param string $address
	 * @param string $name
	 */
	public function cc($address,$name=''){
		$this->cc[$address] = $this->address($address,$name);
	}
	/**
	 * Add bcc-Address
	 * @param string $address
	 * @param string $name
	 */
	public function bcc($address,$name=''){
		$this->bcc[$address] = $this->address($address,$name);
	}
	/**
	 * Set return-path-Address
	 * @param string $address
	 */
	public function return_path($address){
		$this->return_path = $address;
	}
	/**
	 * Set subject
	 * @param string $subject
	 */
	public function subject($subject){
		$this->subject = str_replace("\n","",str_replace(["\r\n","\r"],"\n",$subject));
	}
	/**
	 * Set message body
	 * @param text $message
	 */
	public function message($message){
		$this->message = $message;
	}
	/**
	 * Set HTML message body
	 * @param text $message
	 */
	public function html($message){
		$this->html = $message;
		
		if($this->message === null){
			$this->message(strip_tags($message));
		}
	}
	/**
	 * Add headers
	 * @param string $name
	 * @param string $val
	 */
	public function header($name,$val){
		$this->header[$name] = $val;
	}
	
	/**
	 * Get message header
	 * @return text
	 */
	public function message_header(){
		$rtn = '';
		
		$rtn .= $this->line('MIME-Version: 1.0');
		$rtn .= $this->line('To: '.$this->implode_address($this->to));
		$rtn .= $this->line('From: '.$this->address($this->from,$this->sender_name));
		
		if(!empty($this->cc)){
			$rtn .= $this->line('Cc: '.$this->implode_address($this->cc));
		}
		if(!empty($this->bcc)){
			$rtn .= $this->line('Bcc: '.$this->implode_address($this->bcc));
		}
		if(!empty($this->return_path)){
			$rtn .= $this->line('Return-Path: '.$this->return_path);
		}
		foreach($this->header as $n => $v){
			$n = ucwords(str_replace('_','-',$n));
			$rtn .= $this->line($n.': '.$v);
		}		
		$rtn .= $this->line('Date: '.date('D, d M Y H:i:s O',time()));
		$rtn .= $this->line('Subject: '.$this->jis($this->subject));

		if(!empty($this->attach)){
			$rtn .= $this->line(sprintf('Content-Type: multipart/mixed; boundary="%s"',$this->boundary['mixed']));
		}else if(!empty($this->html)){
			$rtn .= $this->line(sprintf('Content-Type: multipart/alternative; boundary="%s"',$this->boundary['alternative']));
		}else{
			$rtn .= $this->meta('plain');
		}
		return $rtn;
	}	
	private function body(){
		$send = '';
		$isattach = (!empty($this->attach));
		$ishtml = (!empty($this->html));

		if($isattach){
			$send .= $this->line('--'.$this->boundary['mixed']);

			if($ishtml){
				$send .= $this->line(sprintf('Content-Type: multipart/alternative; boundary="%s"',$this->boundary['alternative']));
				$send .= $this->line();
			}
		}
		$send .= (!$ishtml) ? (($isattach) ? $this->meta('plain').$this->line() : '').$this->line($this->enc($this->message)) : $this->alternative();
		
		if($isattach){
			foreach($this->attach as $attach){
				$send .= $this->line('--'.$this->boundary['mixed']);
				$send .= $this->attach_string($attach);
			}
			$send .= $this->line('--'.$this->boundary['mixed'].'--');
		}
		return $send;
	}
	private function alternative(){
		$send = '';
		$send .= $this->line('--'.$this->boundary['alternative']);
		$send .= $this->meta('plain');
		$send .= $this->line();
		$send .= $this->line($this->enc($this->message));
		$send .= $this->line('--'.$this->boundary['alternative']);
		
		if(empty($this->media)){
			$send .= $this->meta('html');
		}
		$send .= $this->line($this->enc((empty($this->media)) ? $this->line().$this->html : $this->related()));
		$send .= $this->line('--'.$this->boundary['alternative'].'--');
		return $send;
	}
	private function related(){
		$send = $this->line().$this->html;
		$html = $this->html;
		
		foreach(array_keys($this->media) as $name){
			$preg = '/(\s)(src|href)\s*=\s*(["\']?)'.preg_quote($name).'\3/';
			$replace = sprintf('\1\2=\3cid:%s\3', md5($name));
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
			$preg = '/url\(\s*(["\']?)'.preg_quote($name).'\1\s*\)/';
			$replace = sprintf('url(\1cid:%s\1)', md5($name));
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
		}
		if($html != $this->html){
			$send = '';
			$send .= $this->line(sprintf('Content-Type: multipart/related; boundary="%s"',$this->boundary['related']));
			$send .= $this->line();
			$send .= $this->line('--'.$this->boundary['related']);
			$send .= $this->meta('html');
			$send .= $this->line();
			$send .= $this->line($this->enc($html));

			foreach($this->media as $name => $media){
				$send .= $this->line('--'.$this->boundary['related']);
				$send .= $this->attach_string($media,md5($name));
			}
			$send .= $this->line('--'.$this->boundary['related'].'--');
		}
		return $send;
	}
	
	private function implode_address($list){
		return trim(implode(','.$this->eol.' ',is_array($list) ? $list : [$list]));
	}
	private function jis($str){
		return sprintf(
			'=?ISO-2022-JP?B?%s?=',
			base64_encode(mb_convert_encoding($str,'JIS',mb_detect_encoding($str)))
		);
	}
	private function meta($type){
		return $this->line(
			sprintf(
				'Content-Type: %s; charset="iso-2022-jp"',
				(($type == 'html') ? 'text/html' : 'text/plain'),
				(($type == 'html') ? 'text/html' : 'text/plain')
			)).
			$this->line('Content-Transfer-Encoding: 7bit');
	}
	private function enc($message){
		return mb_convert_encoding($message,'JIS',mb_detect_encoding($message));
	}
	private function line($value=''){
		return $value.$this->eol;
	}
	private function attach_string($list,$id=null){
		list($filename,$src,$type) = $list;
		$send = '';
		$send .= $this->line(sprintf('Content-Type: %s; name="%s"',(empty($type) ? 'application/octet-stream' : $type),$filename));
		$send .= $this->line(sprintf('Content-Transfer-Encoding: base64'));
		if(!empty($id)){
			$send .= $this->line(sprintf('Content-ID: <%s>', $id));
		}
		$send .= $this->line();
		
		if(substr($src,0,1) == '@' && is_file(substr($src,1))){
			$src = file_get_contents(substr($src,1));
		}
		$send .= $this->line(trim(chunk_split(base64_encode($src),76,$this->eol)));
		return $send;
	}
	private function address($address,$name){
		return '"'.(empty($name) ? $address : $this->jis($name)).'" <'.$address.'>';
	}
	/**
	 * Get properties
	 * @param string $name
	 * @return mixed{}
	 */
	public function get($name=null){
		$result = get_object_vars($this);
	
		if(!empty($name)){
			if(!isset($result[$name])){
				return null;
			}
			return $result[$name];
		}
		unset($result['eol'],$result['boundary']);
		return $result;
	}
	
	/**
	 * Get message 
	 * @param boolean $eol
	 * @return text
	 */
	public function manuscript($eol=true){
		$pre = $this->eol;
		$this->eol = ($eol) ? "\r\n" : "\n";
		$bcc = $this->bcc;
		$this->bcc = [];
		$send = $this->message_header().$this->line().$this->body();
		$this->bcc = $bcc;
		$this->eol = $pre;
		
		return $send;
	}

	/**
	 * Send mail
	 * @param string $subject
	 * @param string $message
	 * @return boolean
	 */
	public function send($subject=null,$message=null){
		if($subject !== null){
			$this->subject($subject);
		}
		if($message !== null){
			$this->message($message);
		}
		if($this->has_object_plugin('send_mail')){
			/**
			 * メール送信する
			 * @param \ebi\Mail $this 
			 */
			$this->call_object_plugin_funcs('send_mail',$this);
		}else if(self::has_class_plugin('send_mail')){
			/**
			 * メール送信する
			 * @param \ebi\Mail $this
			 */
			self::call_class_plugin_funcs('send_mail',$this);
		}else{
			if(empty($this->to)){
				throw new \ebi\exception\RequiredException('undefine to');
			}
			if(empty($this->from)){
				throw new \ebi\exception\RequiredException('undefine from');
			}
			$header = $this->message_header();
			$header = preg_replace('/'.$this->eol.'Subject: .+'.$this->eol.'/',"\n",$header);
			$header = preg_replace('/'.$this->eol.'To: .+'.$this->eol.'/',"\n",$header);
			mail($this->implode_address($this->to),$this->jis($this->subject),$this->body(),trim($header),'-f'.$this->from);
		}
	}
	/**
	 * Send mail (from template)
	 * @param string　$template_path Relative path from resource_path
	 * @param mixed{} $vars bind variables
	 * @return $this
	 */
	public function send_template($template_path,$vars=[]){
		return $this->set_template($template_path,$vars)->send();
	}
	/**
	 * Set template
	 * @param string　$template_path Relative path from resource_path
	 * @param mixed{} $vars bind variables
	 * @return $this
	 */
	public function set_template($template_path,$vars=[]){
		/**
		 * @param string $path Email template resources root
		 */
		$resource_path = \ebi\Conf::get('resource_path',\ebi\Conf::resource_path('mail'));
		$path = \ebi\Util::path_absolute($resource_path,$template_path);
		
		if(!is_file($path)){
			throw new \ebi\exception\InvalidArgumentException($template_path.' not found');
		}
		try{
			$xml = \ebi\Xml::extract(file_get_contents($path),'mail');
			
			try{
				$from = $xml->find_get('from');
				$this->from($from->in_attr('address'),$from->in_attr('name'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			foreach($xml->find('to') as $to){
				$this->to($to->in_attr('address'),$to->in_attr('name'));
			}
			try{
				$this->return_path($xml->find_get('return_path')->in_attr('address'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			$template_code = self::create_code($template_path);
			$this->header['X-T-Code'] = $template_code;
			$vars['x_t_code'] = $template_code;
			
			$subject = trim(str_replace(["\r\n","\r","\n"],'',$xml->find_get('subject')->value()));
			$template = new \ebi\Template();
			$template->cp($vars);
			$this->message(\ebi\Util::plain_text(PHP_EOL.$template->get($xml->find_get('body')->value()).PHP_EOL));
			$this->subject($template->get($subject));
			
			try{
				$html = $xml->find_get('html');
				$html_path = \ebi\Util::path_absolute(
					$resource_path,
					$html->in_attr('src',preg_replace('/^(.+)\.\w+$/','\\1',$path).'.html')
				);
				
				foreach($html->find('media') as $media){
					$file = \ebi\Util::path_absolute($resource_path,$media->in_attr('src'));
					if(!is_file($file)){
						throw new \ebi\exception\InvalidArgumentException($media->in_attr('src').' invalid media');
					}
					$this->media($media->in_attr('src'),file_get_contents($file));
				}
				$template = new \ebi\Template();
				$template->cp($vars);
				$this->html($template->read($html_path));
			}catch(\ebi\exception\NotFoundException $e){
			}
			foreach($xml->find('attach') as $attach){
				$file = \ebi\Util::path_absolute($resource_path,$attach->in_attr('src'));
				if(!is_file($file)){
					throw new \ebi\exception\InvalidArgumentException($attach->in_attr('src').' invalid media');
				}
				$this->attach($attach->in_attr('name',$attach->in_attr('src')),file_get_contents($file));				
			}
			return $this;
		}catch(\ebi\exception\NotFoundException $e){
			throw new \ebi\exception\InvalidArgumentException($template_path.' invalid data');
		}
	}
	/**
	 * Add attachment file
	 * @param string $filename
	 * @param string $src
	 * @param string $type mime-type
	 */
	public function attach($filename,$src,$type='application/octet-stream'){
		$this->attach[] = [basename($filename),$src,$type];
	}
	/**
	 * Add HTML resources file
	 * @param string $filename
	 * @param string $src
	 * @param string $type mime-type
	 */
	public function media($filename,$src,$type='application/octet-stream'){
		$this->media[$filename] = [basename($filename),$src,$type];
	}
	
	/**
	 * Get Template Code
	 * @param string $name
	 * @param integer $len
	 * @return string
	 */
	public static function create_code($val,$len=5){
		return strtoupper(substr(sha1(md5(str_repeat($val,5))),0,$len));
	}
}

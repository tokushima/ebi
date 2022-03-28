<?php
namespace ebi;

class Mail{	
	private $attach = [];
	private $media = [];
	
	private $from = '';
	private $sender_name = '';
	private $return_path = '';
	private $to = [];
	private $cc = [];
	private $bcc = [];
	private $unsubscribe = '';
	private $header = [];
	
	private $subject = '';
	private $message = '';
	private $html = '';
	
	private $eol = "\n";
	private $boundary = ['mixed'=>'mixed','alternative'=>'alternative','related'=>'related'];

	public function __construct(){
		$this->boundary = ['mixed'=>'----=_Part_'.uniqid('mixed'),'alternative'=>'----=_Part_'.uniqid('alternative'),'related'=>'----=_Part_'.uniqid('related')];
	}
	/**
	 * Set from address
	 */
	public function from(string $address, ?string $sender_name=null): void{
		$this->sender_name = $sender_name;
		$this->from = $address;

		if(empty($this->return_path)){
			$this->return_path = $address;
		}
	}
	/**
	 * Set from-Address
	 */
	public function to(string $address, ?string $name=''): void{
		$this->to[$address] = $this->address($address,$name);
	}
	/**
	 * Add to-Address
	 */
	public function cc(string $address, ?string $name=''): void{
		$this->cc[$address] = $this->address($address,$name);
	}
	/**
	 * Add bcc-Address
	 */
	public function bcc(string $address, ?string $name=''): void{
		$this->bcc[$address] = $this->address($address,$name);
	}
	/**
	 * Set List-Unsubscribe
	 */
	public function unsubscribe(string $url): void{
		if(strpos($url, '://') !== false){
			$this->unsubscribe = sprintf('<%s>', $url);
		}else if(strpos($url, '@') !== false){
			$this->unsubscribe = sprintf('<mailto:%s>', $url);
		}
	}
	/**
	 * Set return-path-Address
	 */
	public function return_path(string $address): void{
		$this->return_path = $address;
	}
	/**
	 * Set subject
	 */
	public function subject(string $subject): void{
		$this->subject = str_replace("\n","",str_replace(["\r\n","\r"],"\n",$subject));
	}
	/**
	 * Set message body
	 */
	public function message(string $message): void{
		$this->message = $message;
	}
	/**
	 * Set HTML message body
	 */
	public function html(string $message): void{
		$this->html = $message;
		
		if($this->message === null){
			$this->message(strip_tags($message));
		}
	}
	/**
	 * Add headers
	 */
	public function header(string $name, string $val): void{
		$this->header[$name] = $val;
	}
	
	/**
	 * Get message header
	 */
	public function message_header(): string{
		if(empty($this->from) || empty($this->to)){
			throw new \ebi\exception\RequiredException('from and to are required');
		}
		$rtn = '';
		
		$rtn .= $this->line('MIME-Version: 1.0');
		$rtn .= $this->line('To: '.$this->implode_address($this->to));
		$rtn .= $this->line('From: '.$this->address($this->from, $this->sender_name));
		
		if(!empty($this->cc)){
			$rtn .= $this->line('Cc: '.$this->implode_address($this->cc));
		}
		if(!empty($this->bcc)){
			$rtn .= $this->line('Bcc: '.$this->implode_address($this->bcc));
		}
		if(!empty($this->return_path)){
			$rtn .= $this->line('Return-Path: '.$this->return_path);
		}
		if(!empty($this->unsubscribe)){
			$rtn .= $this->line('List-Unsubscribe: '.$this->unsubscribe);
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

	private function body(): string{
		$send = '';
		$is_attach = (!empty($this->attach));
		$is_html = (!empty($this->html));

		if($is_attach){
			$send .= $this->line('--'.$this->boundary['mixed']);

			if($is_html){
				$send .= $this->line(sprintf('Content-Type: multipart/alternative; boundary="%s"',$this->boundary['alternative']));
				$send .= $this->line();
			}
		}
		$send .= (!$is_html) ? (($is_attach) ? 
			$this->meta('plain').$this->line() : '').$this->line($this->enc($this->message)) : 
			$this->alternative();
		
		if($is_attach){
			foreach($this->attach as $attach){
				$send .= $this->line('--'.$this->boundary['mixed']);
				$send .= $this->attach_string($attach);
			}
			$send .= $this->line('--'.$this->boundary['mixed'].'--');
		}
		return $send;
	}

	private function alternative(): string{
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

	private function related(): string{
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
	
	/**
	 * @param mixed $list (string|array)
	 */
	private function implode_address($list): string{
		return trim(implode(','.$this->eol.' ',is_array($list) ? $list : [$list]));
	}

	private function jis(string $str): string{
		return sprintf(
			'=?ISO-2022-JP?B?%s?=',
			base64_encode(mb_convert_encoding($str ?? '','JIS',mb_detect_encoding($str ?? '')))
		);
	}

	private function meta(string $type): string{
		return $this->line(
			sprintf(
				'Content-Type: %s; charset="iso-2022-jp"',
				(($type == 'html') ? 'text/html' : 'text/plain'),
				(($type == 'html') ? 'text/html' : 'text/plain')
			)).
			$this->line('Content-Transfer-Encoding: 7bit');
	}

	private function enc(string $message): string{
		return mb_convert_encoding($message ?? '','JIS',mb_detect_encoding($message ?? ''));
	}

	private function line(?string $value=''): string{
		return $value.$this->eol;
	}

	private function attach_string(array $list, ?string $id=null): string{
		[$filename, $src, $type] = $list;
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

	private function address(string $address, ?string $name): string{
		return '"'.(empty($name) ? $address : $this->jis($name)).'" <'.$address.'>';
	}

	/**
	 * Get properties
	 */
	public function get(): array{
		$result = get_object_vars($this);
		unset($result['eol'], $result['boundary']);
		return $result;
	}
	
	/**
	 * Get message 
	 */
	public function manuscript(bool $eol=true): string{
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
	 */
	public function send(?string $subject=null, ?string $message=null): void{
		if($subject !== null){
			$this->subject($subject);
		}
		if($message !== null){
			$this->message($message);
		}
		if(\ebi\Conf::defined_handler()){
			\ebi\Conf::call('send_mail', $this);
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
	 */
	public function send_template(string $template_path, array $bind_vars=[]): void{
		$this->set_template($template_path, $bind_vars)->send();
	}

	/**
	 * Set template
	 */
	public function set_template(string $template_path, array $bind_vars=[]): self{
		/**
		 * @param string $path Email template resources root
		 */
		$resource_path = \ebi\Conf::get('resource_path',\ebi\Conf::resource_path('mail'));
		$path = \ebi\Util::path_absolute($resource_path,$template_path);
		
		if(!is_file($path)){
			throw new \ebi\exception\InvalidArgumentException($template_path.' not found');
		}
		$xml = \ebi\Xml::extract(file_get_contents($path),'mail');
		
		try{
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
			
			/**
			 * @param string $xtc_name xtc(x-template-code) query key
			 */
			$xtc_name = \ebi\Conf::get('xtc_name','xtc');
			$xtc = self::xtc($template_path);
			$this->header['X-T-Code'] = $xtc;
			
			$bind_vars['t'] = new \ebi\FlowHelper();
			$bind_vars['xtc'] = [$xtc_name=>$xtc];
			
			$subject = trim(str_replace(["\r\n","\r","\n"],'',$xml->find_get('subject')->value()));
			$template = new \ebi\Template();
			
			if(is_array($bind_vars) || is_object($bind_vars)){
				foreach($bind_vars as $k => $v){
					$template->vars($k,$v);
				}
			}
			$body_xml = $xml->find_get('body');
			$signature = $body_xml->in_attr('signature');
			$signature_text = '';
			
			if(!empty($signature)){
				$sig_path = \ebi\Util::path_absolute($resource_path,$signature);
				
				if(!is_file($sig_path)){
					throw new \ebi\exception\InvalidArgumentException($signature.' not found');
				}
				$sig_xml = \ebi\Xml::extract(file_get_contents($sig_path),'mail');
				$signature_text = \ebi\Util::plain_text(PHP_EOL.$sig_xml->find_get('signature')->value().PHP_EOL);
			}
			$message = $template->get(\ebi\Util::plain_text(PHP_EOL.$body_xml->value().PHP_EOL).$signature_text,$resource_path);
			$this->message($message);
			$this->subject($template->get($subject,$resource_path));
			
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
				$this->html($template->read($html_path,$resource_path));
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
	 */
	public function attach(string $filename, string $src, string $mime_type='application/octet-stream'): void{
		$this->attach[] = [basename($filename), $src, $mime_type];
	}

	/**
	 * Add HTML resources file
	 */
	public function media(string $filename, string $src, string $mime_type='application/octet-stream'): void{
		$this->media[$filename] = [basename($filename),$src,$mime_type];
	}

	/**
	 * Template Code
	 */
	public static function xtc(string $template_path): string{
		/**
		 * @param string $xtc_length Template Code length
		 */
		$length = \ebi\Conf::get('xtc_length',5);
		return strtoupper(substr(sha1(md5(str_repeat($template_path,5))),0,$length));
	}
}

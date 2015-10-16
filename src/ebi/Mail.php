<?php
namespace ebi;
/**
 * メールを送信する
 * @author tokushima
 */
class Mail{
	use \ebi\Plugin;
	
	private $attach;
	private $media;

	private $encode = 'jis';
	private $message;
	private $html;
	private $name;
	private $from;
	
	private $to = [];
	private $cc = [];
	private $bcc = [];
	
	private $return_path;
	private $reply_to;
	private $errors_to;
	private $notification;
	private $subject;
	
	private $eol = "\n";
	private $boundary = ['mixed'=>'mixed','alternative'=>'alternative','related'=>'related'];
	private $resource_path;

	public function __construct(){
		$this->boundary = ['mixed'=>'----=_Part_'.uniqid('mixed'),'alternative'=>'----=_Part_'.uniqid('alternative'),'related'=>'----=_Part_'.uniqid('related')];
	}
	public function resource_path($path){
		$this->resource_path = $path;
	}
	public function from($mail,$name=null){
		$this->from = $mail;
		$this->name = $name;
		$this->return_path = $mail;
	}
	public function to($mail,$name=''){
		$this->to[$mail] = $this->address($mail,$name);
	}
	public function cc($mail,$name=''){
		$this->cc[$mail] = $this->address($mail,$name);
	}
	public function bcc($mail,$name=''){
		$this->bcc[$mail] = $this->address($mail,$name);
	}
	public function return_path($mail){
		$this->return_path = $mail;
	}
	public function errors_to($mail){
		$this->errors_to = $mail;
	}
	public function reply_to($mail){
		$this->reply_to = $mail;
	}
	
	/**
	 * 開封確認メール設定
	 * @param string $mail
	 */
	public function notification($mail){
		$this->notification = $mail;
	}
	public function subject($subject){
		$this->subject = str_replace("\n","",str_replace(["\r\n","\r"],"\n",$subject));
	}
	public function message($message){
		$this->message = $message;
	}
	public function html($message){
		$this->html = $message;
		if($this->message === null) $this->message(strip_tags($message));
	}
	public function header(){
		$send = '';
		$send .= $this->line('MIME-Version: 1.0');
		$send .= $this->line('To: '.$this->implode_address($this->to));
		$send .= $this->line('From: '.$this->address($this->from,$this->name));
		if(!empty($this->cc)) $send .= $this->line('Cc: '.$this->implode_address($this->cc));
		if(!empty($this->bcc)) $send .= $this->line('Bcc: '.$this->implode_address($this->bcc));
		if(!empty($this->return_path)) $send .= $this->line('Return-Path: '.$this->return_path);
		if(!empty($this->errors_to)) $send .= $this->line('Errors-To: '.$this->errors_to);
		if(!empty($this->reply_to)) $send .= $this->line('Reply-To: '.$this->reply_to);
		if(!empty($this->notification)) $send .= $this->line('Disposition-Notification-To: '.$this->notification);
		$send .= $this->line('Date: '.date('D, d M Y H:i:s O',time()));
		$send .= $this->line('Subject: '.$this->jis($this->subject));

		if(!empty($this->attach)){
			$send .= $this->line(sprintf('Content-Type: multipart/mixed; boundary="%s"',$this->boundary['mixed']));
		}else if(!empty($this->html)){
			$send .= $this->line(sprintf('Content-Type: multipart/alternative; boundary="%s"',$this->boundary['alternative']));
		}else{
			$send .= $this->meta('plain');
		}
		return $send;
	}
	private function implode_address($list){
		return trim(implode(','.$this->eol.' ',is_array($list) ? $list : [$list]));
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
		$send .= (!$ishtml) ? (($isattach) ? $this->meta('plain').$this->line() : '').$this->line($this->encode($this->message)) : $this->alternative();
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
		$send .= $this->line($this->encode($this->message));
		$send .= $this->line('--'.$this->boundary['alternative']);
		if(empty($this->media)) $send .= $this->meta('html');
		$send .= $this->line($this->encode((empty($this->media)) ? $this->line().$this->html : $this->related()));
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
			$send .= $this->line($this->encode($html));

			foreach($this->media as $name => $media){
				$send .= $this->line('--'.$this->boundary['related']);
				$send .= $this->attach_string($media,md5($name));
			}
			$send .= $this->line('--'.$this->boundary['related'].'--');
		}
		return $send;
	}
	private function jis($str){
		return sprintf('=?ISO-2022-JP?B?%s?=',base64_encode(mb_convert_encoding($str,'JIS',mb_detect_encoding($str))));
	}
	private function meta($type){
		switch(strtolower($type)){
			case 'html': $type = 'text/html'; break;
			default: $type = 'text/plain';
		}
		switch($this->encode){
			case 'utf8':
				return $this->line(sprintf('Content-Type: %s; charset="utf-8"',$type)).
						$this->line('Content-Transfer-Encoding: 8bit');
			case 'sjis':
				return $this->line(sprintf('Content-Type: %s; charset="iso-2022-jp"',$type)).
						$this->line('Content-Transfer-Encoding: base64');
			default:
				return $this->line(sprintf('Content-Type: %s; charset="iso-2022-jp"',$type)).
						$this->line('Content-Transfer-Encoding: 7bit');
		}
	}
	private function encode($message){
		switch($this->encode){
			case 'utf8': return mb_convert_encoding($message,'UTF8',mb_detect_encoding($message));
			case 'sjis': return mb_convert_encoding(base64_encode(mb_convert_encoding($message,'SJIS',mb_detect_encoding($message)),'JIS'));
			default: return mb_convert_encoding($message,'JIS',mb_detect_encoding($message));
		}
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
	private function address($mail,$name){
		return '"'.(empty($name) ? $mail : $this->jis($name)).'" <'.$mail.'>';
	}
	/**
	 * セットされた内容の取得
	 * @param string $key
	 * @return mixed
	 */
	public function get($key=null){
		$result = get_object_vars($this);
	
		if(!empty($key)){
			if(!isset($result[$key])) return null;
			return $result[$key];
		}
		return $result;
	}
	
	/**
	 * 送信する内容
	 * @param boolean $eol
	 * @return string
	 */
	public function manuscript($eol=true){
		$pre = $this->eol;
		$this->eol = ($eol) ? "\r\n" : "\n";
		$bcc = $this->bcc;
		$this->bcc = [];
		$send = $this->header().$this->line().$this->body();
		$this->bcc = $bcc;
		$this->eol = $pre;
		return $send;
	}

	/**
	 * メールを送信する
	 * @param string $subject
	 * @param string $message
	 * @return boolean
	 */
	public function send($subject=null,$message=null){
		if($this->has_object_plugin('set_mail')){
			$this->call_object_plugin_funcs('set_mail',$this);
		}else if(self::has_class_plugin('set_mail')){
			self::call_class_plugin_funcs('set_mail',$this);
		}
		if($subject !== null) $this->subject($subject);
		if($message !== null) $this->message($message);

		if($this->has_object_plugin('send_mail')){
			$this->call_object_plugin_funcs('send_mail',$this);
		}else if(self::has_class_plugin('send_mail')){
			self::call_class_plugin_funcs('send_mail',$this);
		}else{
			if(empty($this->to)) throw new \ebi\exception\RequiredException('undefine to');
			if(empty($this->from)) throw new \ebi\exception\RequiredException('undefine from');
			$header = $this->header();
			$header = preg_replace('/'.$this->eol.'Subject: .+'.$this->eol.'/',"\n",$header);
			$header = preg_replace('/'.$this->eol.'To: .+'.$this->eol.'/',"\n",$header);
			mail($this->implode_address($this->to),$this->jis($this->subject),$this->body(),trim($header),'-f'.$this->from);
		}
	}
	/**
	 * テンプレートから内容を取得しメールを送信する
	 * @param string　$template_path テンプレートファイルパス
	 * @param mixed{} $vars テンプレートへ渡す変数
	 * @return $this
	 */
	public function send_template($template_path,$vars=[]){
		return $this->set_template($template_path,$vars)->send();
	}
	/**
	 * テンプレートから内容を取得しセットする
	 * 
	 * テンプレートサンプル
	 * <mail>
	 * <from address="support@email.address" name="tokushima" />
	 * <subject>メールのタイトル</subject>
	 * <body>
	 * メールの本文
	 * </body>
	 * </mail>
	 * 
	 * @param string　$template_path テンプレートファイルパス
	 * @param mixed{} $vars テンプレートへ渡す変数
	 * @return $this
	 */
	public function set_template($template_path,$vars=[]){
		/**
		 * テンプレートのあるディレクトリパス
		 */
		$resource_path = empty($this->resource_path) ? \ebi\Conf::get('resource_path',\ebi\Conf::resource_path('mail')) : $this->resource_path;
		$template_path = \ebi\Util::path_absolute($resource_path,$template_path);
		if(!is_file($template_path)){
			throw new \InvalidArgumentException($template_path.' not found');
		}
		try{
			$xml = \ebi\Xml::extract(file_get_contents($template_path),'mail');
			try{
				$from = $xml->find_get('from');
				$this->from($from->in_attr('address'),$from->in_attr('name'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			foreach($xml->find('to') as $to){
				$this->to($to->in_attr('address'),$to->in_attr('name'));
			}
			try{
				$return_path = $xml->find_get('return_path');
				$this->return_path($return_path->in_attr('address'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			try{
				$errors_to = $xml->find_get('errors_to');
				$this->errors_to($errors_to->in_attr('address'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			try{
				$reply_to = $xml->find_get('reply_to');
				$this->reply_to($reply_to->in_attr('address'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			try{
				$notification = $xml->find_get('notification');
				$this->notification($notification->in_attr('notification'));
			}catch(\ebi\exception\NotFoundException $e){
			}
			$subject = trim(str_replace(["\r\n","\r","\n"],'',$xml->find_get('subject')->value()));
			$template = new \ebi\Template();
			$template->cp($vars);
			$this->message(\ebi\Util::plain_text(PHP_EOL.$template->get($xml->find_get('body')->value()).PHP_EOL));
			$this->subject($template->get($subject));
			
			try{
				$html = $xml->find_get('html');
				$html_path = \ebi\Util::path_absolute($resource_path,$html->in_attr('src'));
				
				foreach($html->find('media') as $media){
					$file = \ebi\Util::path_absolute($resource_path,$media->in_attr('src'));
					if(!is_file($file)){
						throw new \InvalidArgumentException($media->in_attr('src').' invalid media');
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
					throw new \InvalidArgumentException($attach->in_attr('src').' invalid media');
				}
				$this->attach($attach->in_attr('name',$attach->in_attr('src')),file_get_contents($file));				
			}
			return $this;
		}catch(\ebi\exception\NotFoundException $e){
			throw new \InvalidArgumentException($template_path.' invalid data');
		}
	}
	public function attach($filename,$src,$type="application/octet-stream"){
		$this->attach[] = [basename($filename),$src,$type];
	}
	public function media($filename,$src,$type="application/octet-stream"){
		$this->media[$filename] = [basename($filename),$src,$type];
	}
}

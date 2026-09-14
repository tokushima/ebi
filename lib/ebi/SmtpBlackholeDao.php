<?php
namespace ebi;
use ebi\Attribute\Prop;
/**
 * 送信するメールをDBに保存して実際にメールを送信しない
 */
class SmtpBlackholeDao extends \ebi\Dao implements \ebi\MailHandler{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(type:'text')]
	protected ?string $from = null;
	#[Prop(type:'text')]
	protected ?string $to = null;
	#[Prop(type:'text')]
	protected ?string $cc = null;
	#[Prop]
	protected ?string $bcc = null;
	#[Prop]
	protected ?string $subject = null;
	#[Prop(type:'text')]
	protected ?string $message = null;
	#[Prop]
	protected ?string $tcode = null;
	#[Prop(type:'text')]
	protected ?string $manuscript = null;
	#[Prop(type:'datetime', auto_now_add:true)]
	protected ?int $create_date = null;
	
	/**
	 * メールの内容をDBに保存する
	 */
	public function send_mail(\ebi\Mail $mail): void{
		$data = $mail->get();
		
		$header = $data['header'];
		$to = array_keys($data['to']);
		sort($to);

		$self = new static();
		$self->from($data['from']);
		$self->to(PHP_EOL.implode(PHP_EOL,$to).PHP_EOL);
		$self->cc(implode(PHP_EOL,array_keys($data['cc'])));
		$self->bcc(implode(PHP_EOL,array_keys($data['bcc'])));
		$self->subject($data['subject']);
		$self->message($data['message']);
		$self->tcode(array_key_exists('X-T-Code',$header) ? $header['X-T-Code'] : null);
		$self->manuscript($mail->manuscript());
		$self->save();
		
		self::commit();
	}

	/**
	 * 送信されたメールの一番新しいものを返す
	 */
	public static function find_mail(string $to, string $tcode='', string $keyword=''): \ebi\SmtpBlackholeDao{
		$q = new Q();
		$q->add(Q::contains('to', PHP_EOL.$to.PHP_EOL));
		$q->add(Q::gte('create_date',time()-300));
		
		if(!empty($tcode)){
			$q->add(Q::eq('tcode',$tcode));
		}	
		foreach(\ebi\SmtpBlackholeDao::find_all($q,Q::order('-id')) as $mail){
			$value = $mail->subject().$mail->message();
				
			if(empty($keyword) || mb_strpos($value,$keyword) !== false){
				return $mail;
			}
		}
		throw new \ebi\exception\NotFoundException('mail not found');
	}
}
<?php
namespace ebi;
/**
 * 送信するメールをDBに保存して実際にメールを送信しない
 * @author tokushima
 * @var serial $id
 * @var text $from
 * @var text $to
 * @var text $cc
 * @var string $subject
 * @var text $message
 * @var text $manuscript
 * @var string $tcode
 * @var timestamp $create_date @['auto_now_add'=>true]
 */
class SmtpBlackholeDao extends \ebi\Dao{
	protected $id;
	protected $from;
	protected $to;
	protected $cc;
	protected $bcc;
	protected $subject;
	protected $message;
	protected $tcode;
	protected $manuscript;
	protected $create_date;
	
	/**
	 * メールの内容をDBに保存する
	 * @param ebi.Mail $mail
	 */
	public function send_mail(\ebi\Mail $mail){
		$data = $mail->get();
		
		$header = $data['header'];
		
		$self = new static();
		$self->from($data['from']);
		$self->to(implode(PHP_EOL,array_keys($data['to'])));
		$self->cc(implode(PHP_EOL,array_keys($data['cc'])));
		$self->bcc(implode(PHP_EOL,array_keys($data['bcc'])));
		$self->subject($data['subject']);
		$self->message($data['message']);
		$self->tcode(array_key_exists('X-T-Code',$header) ? $header['X-T-Code'] : null);
		$self->manuscript($mail->manuscript());
		$self->save();
		
		self::commit();
	}
}
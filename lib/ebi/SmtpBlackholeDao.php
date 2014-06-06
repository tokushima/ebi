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
	protected $manuscript;
	protected $create_date;
	
	/**
	 * メールの内容をDBに保存する
	 * @param ebi.Mail $mail
	 */
	public function send_mail(\ebi\Mail $mail){
		$data = $mail->get();
		$self = new static();
		$self->from($data['from']);
		$self->to(implode("\n",array_keys($data['to'])));
		$self->cc(implode("\n",array_keys($data['cc'])));
		$self->bcc(implode("\n",array_keys($data['bcc'])));
		$self->subject($data['subject']);
		$self->message($data['message']);
		$self->manuscript($mail->manuscript());
		$self->save();
		
		self::commit();
	}
}
<?php
namespace ebi;
/**
 * 送信するメールをDBに保存して実際にメールを送信しない
 * 
 * @var serial $id
 * @var text $from
 * @var text $to
 * @var text $cc
 * @var string $subject
 * @var text $message
 * @var text $manuscript
 * @var string $tcode
 * @var datetime $create_date @['auto_now_add'=>true]
 */
class SmtpBlackholeDao extends \ebi\Dao implements \ebi\MailHandler{
	protected ?int $id = null;
	protected ?string $from = null;
	protected ?string $to = null;
	protected ?string $cc = null;
	protected ?string $bcc = null;
	protected ?string $subject = null;
	protected ?string $message = null;
	protected ?string $tcode = null;
	protected ?string $manuscript = null;
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
		$self->to(implode(PHP_EOL,$to));
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
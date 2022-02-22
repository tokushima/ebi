<?php
namespace ebi;

class Command{
	private $resource; #リソース
	private $stdout; # 実行結果
	private $stderr; # 実行時のエラー
	private $end_code; # 実行していたプロセスの終了状態
	private $proc;
	private $close = true;
	
	public function __construct(?string $command=null){
		if(!empty($command)){
			$this->open($command);
			$this->close();
		}
	}
	/**
	 * 実行結果の取得
	 */
	public function stdout(): string{
		return $this->stdout;
	}
	/**
	 * エラーの取得
	 */
	public function stderr(): string{
		return $this->stderr;
	}
	/**
	 * 実行していたプロセスの終了状態を取得
	 */
	public function end_code(): string{
		return $this->end_code;
	}
	/**
	 * コマンドを実行しプロセスをオープする
	 * @param string $command 実行するコマンド
	 * @param string $out_file 結果を保存するファイルパス
	 * @param string $error_file エラー結果を保存するファイルパス
	 */
	public function open(string $command, ?string $out_file=null, ?string $error_file=null): void{
		$this->close();

		if(!empty($out_file)){
			file_put_contents($out_file, '');
		}
		if(!empty($error_file)){
			file_put_contents($error_file, '');
		}
		$out = (empty($out_file)) ? ['pipe','w'] : ['file',$out_file,'w'];
		$err = (empty($error_file)) ? ['pipe','w'] : ['file',$error_file,'w'];
		$this->proc = proc_open($command,[['pipe','r'],$out,$err],$this->resource);
		$this->close = false;
	}
	/**
	 * コマンドを実行し出力する
	 * @param string $command 実行するコマンド
	 */
	public function write(string $command): self{
		if(is_resource($this->resource[0])){
			fwrite($this->resource[0],$command."\n");
		}
		return $this;
	}
	/**
	 * 結果を取得する
	 */
	public function gets(): string{
		if(isset($this->resource[1]) && is_resource($this->resource[1])){
			$value = fgets($this->resource[1]);
			$this->stdout .= $value;
			return $value;
		}
	}
	/**
	 * 結果から１文字取得する
	 */
	public function getc(): string{
		if(isset($this->resource[1]) && is_resource($this->resource[1])){
			$value = fgetc($this->resource[1]);
			$this->stdout .= $value;
			return $value;
		}
	}
	/**
	 * 閉じる
	 */
	public function close(): void{
		if(!$this->close){
			if(isset($this->resource[0]) && is_resource($this->resource[0])) fclose($this->resource[0]);
			if(isset($this->resource[1]) && is_resource($this->resource[1])){
				while(!feof($this->resource[1])) $this->stdout .= fgets($this->resource[1]);
				fclose($this->resource[1]);
			}
			if(isset($this->resource[2]) && is_resource($this->resource[2])){
				while(!feof($this->resource[2])) $this->stderr .= fgets($this->resource[2]);
				fclose($this->resource[2]);
			}
			$this->end_code = proc_close($this->proc);
			$this->close = true;
		}
	}

	public function __destruct(){
		$this->close();
	}
	public function __toString(){
		return (string)$this->stdout;
	}
	/**
	 * コマンドを実行し結果を取得
	 */
	public static function out(string $command): string{
		$self = new self($command);
		return $self->stdout();
	}
	/**
	 * コマンドを実行してエラー結果を取得
	 */
	public static function error(string $command): string{
		$self = new self($command);
		return $self->stderr();
	}
	/**
	 * 標準入力からの入力を取得する
	 * @param string $msg 入力待ちのメッセージ
	 * @param string $default 入力が空だった場合のデフォルト値
	 * @param string[] $choice 入力を選択式で求める
	 * @param bool $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 * @param bool $invisible 入力を非表示にする(Windowsでは非表示になりません)
	 */
	public static function stdin(string $msg, ?string $default=null, array $choice=[], bool $multiline=false, bool $invisible=false): string{
		$result = $b = null;
		print($msg.(empty($choice) ? '' : ' ('.implode(' / ',$choice).')').(empty($default) ? '' : ' ['.$default.']').': ');
		
		if($invisible && substr(PHP_OS,0,3) != 'WIN'){
			`tty -s && stty -echo`;
		}
		while(true){
			fscanf(STDIN,'%s',$b);
			if($multiline && $b == '.') break;
			$result .= $b."\n";
			if(!$multiline) break;
		}
		if($invisible && substr(PHP_OS,0,3) != 'WIN') `tty -s && stty echo`;
		$result = substr(str_replace(["\r\n","\r","\n"],"\n",$result),0,-1);
		if(empty($result)) $result = $default;
		if(empty($choice) || in_array($result,$choice)) return $result;
	}
	/**
	 * stdinのエイリアス、入力を非表示にする
	 * Windowsでは非表示になりません
	 * @param string $msg 入力待ちのメッセージ
	 * @param string $default 入力が空だった場合のデフォルト値
	 * @param string[] $choice 入力を選択式で求める
	 * @param bool $multiline 複数行の入力をまつ、終了は行頭.(ドット)
	 */
	public static function stdin_invisible(string $msg, ?string $default=null, array $choice=[], bool $multiline=false): string{
		return self::stdin($msg,$default,$choice,$multiline,true);
	}
}
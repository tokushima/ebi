<?php
namespace ebi;

class HttpFile{
	/**
	 * inlineで出力する
	 * @param mixed $filename (string|array) 出力するファイル、または[ファイル名,文字列]
	 * @param $modified_status Last-Modifiedを見るか
	 */
	public static function inline($filename, bool $modified_status=true): void{
		self::output_file_content($filename,'inline',$modified_status);
	}

	/**
	 * attachmentで出力する
	 * @param mixed $filename (string|array ) 出力するファイル、または[ファイル名,文字列]
	 * @param $modified_status Last-Modifiedを見るか
	 */
	public static function attach($filename, bool $modified_status=true): void{
		self::output_file_content($filename,'attachment',$modified_status);
	}
	private static function output_file_content($filename,$disposition,$modified_status){
		if(is_array($filename)){
			[$filename, $src] = $filename;

			\ebi\HttpHeader::send('Content-Type',self::mime($filename).'; name='.basename($filename));
			\ebi\HttpHeader::send('Content-Disposition',$disposition.'; filename='.basename($filename));
			\ebi\HttpHeader::send('Content-length',strlen($src));
			print($src);
			exit;
		}
		if(is_file($filename)){
			if($modified_status){
				$update = @filemtime($filename);
				if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $update <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
					\ebi\HttpHeader::send_status(304);
					exit;
				}
				\ebi\HttpHeader::send('Last-Modified',gmdate('D, d M Y H:i:s T',$update));
			}
			\ebi\HttpHeader::send('Content-Type',self::mime($filename).'; name='.basename($filename));
			\ebi\HttpHeader::send('Content-Disposition',$disposition.'; filename='.basename($filename));

			$range = [];
			if(isset($_SERVER['HTTP_RANGE']) && preg_match("/^bytes=(\d+)\-(\d+)$/",$_SERVER['HTTP_RANGE'],$range)){
				[,$offset,$end] = $range;
				$length = $end - $offset + 1;
				
				\ebi\HttpHeader::send_status(206);
				\ebi\HttpHeader::send('Accept-Ranges','bytes');
				\ebi\HttpHeader::send('Content-length',sprintf('%u',$length));
				\ebi\HttpHeader::send('Content-Range',sprintf('bytes %u-%u/%u',$offset,$end,filesize($filename)));

				print(file_get_contents($filename, false, null, $offset, $length));
				exit;
			}else{
				\ebi\HttpHeader::send('Content-length',sprintf('%u',filesize($filename)));
				$fp = fopen($filename,'rb');
				
				while(!feof($fp)){
					echo(fread($fp,8192));
					flush();
				}
				fclose($fp);
				exit;
			}
		}	
		\ebi\HttpHeader::send_status(404);
		exit;
	}

	private static function mime(string $filename): string{
		$ext = (false !== ($p = strrpos($filename,'.'))) ? strtolower(substr($filename,$p+1)) : null;
		switch($ext){
			case 'jpg':
			case 'jpeg': return 'jpeg';
			case 'png':
			case 'gif':
			case 'bmp':
			case 'tiff': return 'image/'.$ext;
			case 'svg': return 'image/svg+xml';
			case 'pdf': return 'application/pdf';
			case 'css': return 'text/css';
			case 'txt': return 'text/plain';
			case 'html': return 'text/html';
			case 'xml': return 'application/xml';
			case 'js': return 'text/javascript';
			case 'flv':
			case 'swf': return 'application/x-shockwave-flash';
			case '3gp': return 'video/3gpp';
			case 'gz':
			case 'tgz':
			case 'tar':
			case 'gz': return 'application/x-compress';
			case 'csv': return 'text/csv';
			case null:
			default:
				return 'application/octet-stream';
		}
	}
}
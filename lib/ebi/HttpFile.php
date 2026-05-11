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
			// image
			case 'jpg':
			case 'jpeg': return 'image/jpeg';
			case 'png':
			case 'gif':
			case 'bmp':
			case 'tiff':
			case 'webp':
			case 'avif': return 'image/'.$ext;
			case 'svg': return 'image/svg+xml';
			case 'ico': return 'image/x-icon';
			// document
			case 'pdf': return 'application/pdf';
			case 'rtf': return 'application/rtf';
			// text/web
			case 'css': return 'text/css';
			case 'txt': return 'text/plain';
			case 'html':
			case 'htm': return 'text/html';
			case 'xml': return 'application/xml';
			case 'xhtml': return 'application/xhtml+xml';
			case 'js':
			case 'mjs': return 'text/javascript';
			case 'json':
			case 'map': return 'application/json';
			case 'yaml':
			case 'yml': return 'application/yaml';
			case 'md': return 'text/markdown';
			case 'csv': return 'text/csv';
			// font
			case 'woff': return 'font/woff';
			case 'woff2': return 'font/woff2';
			case 'ttf': return 'font/ttf';
			case 'otf': return 'font/otf';
			case 'eot': return 'application/vnd.ms-fontobject';
			// audio
			case 'wav': return 'audio/wav';
			case 'mp3': return 'audio/mpeg';
			case 'm4a': return 'audio/mp4';
			case 'aac': return 'audio/aac';
			case 'flac': return 'audio/flac';
			case 'ogg':
			case 'oga': return 'audio/ogg';
			// video
			case 'mp4': return 'video/mp4';
			case 'webm': return 'video/webm';
			case 'mov': return 'video/quicktime';
			case 'avi': return 'video/x-msvideo';
			case '3gp': return 'video/3gpp';
			// flash (legacy)
			case 'flv':
			case 'swf': return 'application/x-shockwave-flash';
			// archive
			case 'zip': return 'application/zip';
			case '7z': return 'application/x-7z-compressed';
			case 'rar': return 'application/vnd.rar';
			case 'bz2': return 'application/x-bzip2';
			case 'gz':
			case 'tgz':
			case 'tar': return 'application/x-compress';
			case null:
			default:
				return 'application/octet-stream';
		}
	}
}
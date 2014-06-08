<?php
namespace ebi;
/**
 * 添付ファイルの操作
 * @author tokushima
 *
 */
class HttpFile{
	/**
	 * inlineで出力する
	 * @param mixed $file 出力するファイル、または[ファイル名,文字列]
	 */
	public static function inline($filename){
		self::output_file_content($filename,'inline');
	}
	/**
	 * attachmentで出力する
	 * @param mixed $file 出力するファイル、または[ファイル名,文字列]
	 */
	public static function attach($filename){
		self::output_file_content($filename,'attachment');
	}
	private static function output_file_content($filename,$disposition){
		$isstr = false;
		
		if(is_array($filename)){
			list($filename,$src) = $filename;
			$isstr = true;
		}
		if(!$isstr && is_file($filename)){
			$update = ($isstr) ? time() : @filemtime($filename);
			
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $update <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
				\ebi\HttpHeader::send_status(304);
				exit;
			}
			\ebi\HttpHeader::send('Last-Modified',gmdate('D, d M Y H:i:s',$update).' GMT');
			\ebi\HttpHeader::send('Content-Type',self::mime($filename).'; name='.basename($filename));
			\ebi\HttpHeader::send('Content-Disposition',$disposition.'; filename='.basename($filename));

			if(!$isstr && isset($_SERVER['HTTP_RANGE']) && preg_match("/^bytes=(\d+)\-(\d+)$/",$_SERVER['HTTP_RANGE'],$range)){
				list(,$offset,$end) = $range;
				$length = $end - $offset + 1;
				
				\ebi\HttpHeader::send_status(206);
				\ebi\HttpHeader::send('Accept-Ranges','bytes');
				\ebi\HttpHeader::send('Content-length',sprint('%u',$length));
				\ebi\HttpHeader::send('Content-Range',sprintf('bytes %u-%u/%u',$offset,$end,filesize($filename)));

				print(file_get_contents($filename,null,null,$offset,$length));
				exit;
			}else{
				\ebi\HttpHeader::send('Content-length',strlen($src));
				print($src);
				exit;
			}
		}else if($isstr){
			\ebi\HttpHeader::send('Content-length',sprintf('%u',filesize($filename)));
			$fp = fopen($filename,'rb');
			while(!feof($fp)){
				echo(fread($fp,8192));
				flush();
			}
			fclose($fp);
			exit;
		}		
		\ebi\HttpHeader::send_status(404);
		exit;
	}
	private static function mime($filename){
		$ext = (false !== ($p = strrpos($filename,'.'))) ? strtolower(substr($filename,$p+1)) : null;
		switch($ext){
			case 'jpg':
			case 'jpeg': return 'jpeg';
			case 'png':
			case 'gif':
			case 'bmp':
			case 'tiff': return 'image/'.$ext;
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
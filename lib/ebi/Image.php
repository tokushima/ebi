<?php
namespace ebi;

class Image{
	/**
	 * JPG画像ファイルを指定のサイズで縮小・切り取り
	 * 縦横比は変えずクロップする
	 * @param string $filename 入力ファイル名
	 * @param integer $width 幅(px)
	 * @param integer $height 高さ(px)
	 * @param string $output 出力先ファイル名
	 */
	public static function cropping_jpeg($filename,$width,$height,$output=null){
		if(extension_loaded('imagick')){
			$canvas = new \Imagick($filename);
			
			list($cw,$ch) = self::get_resize_info($canvas->getImageWidth(),$canvas->getImageHeight(), $width, $height);
			$canvas->cropThumbnailImage($cw,$ch);
			
			if(empty($output)){
				header('Content-Type: image/jpeg');
				print($canvas);
			}else{
				$canvas->writeImage($output);
			}
			$canvas->clear();
		}else{
			list($original_w,$original_h) = getimagesize($filename);
			$original_image = imagecreatefromjpeg($filename);
			
			$canvas = self::get_gd_cropping_resource($original_image, $original_w, $original_h, $width, $height);		
			
			if(empty($output)){
				header('Content-Type: image/jpeg');
				imagejpeg($canvas);
			}else{
				imagejpeg($canvas,$output);
			}
			imagedestroy($original_image);
			imagedestroy($canvas);
		}
	}
	
	/**
	 * JPG画像バイナリ文字列を指定のサイズで縮小・切り取り
	 * 縦横比は変えずクロップする
	 * @param string $filename 入力ファイル名
	 * @param integer $width 幅(px)
	 * @param integer $height 高さ(px)
	 * @param string $output 出力先ファイル名
	 */
	public static function cropping_jpeg_from_string($string,$width,$height,$output=null){
		if(extension_loaded('imagick')){
			$canvas = new \Imagick();
			$canvas->readImageBlob($string);
			
			list($cw,$ch) = self::get_resize_info($canvas->getImageWidth(),$canvas->getImageHeight(), $width, $height);
			$canvas->cropThumbnailImage($cw,$ch);
			
			if(empty($output)){
				header('Content-Type: image/jpeg');
				print($canvas);
			}else{
				$canvas->writeImage($output);
			}
			$canvas->clear();
		}else{
			list($original_w,$original_h) = getimagesizefromstring($string);
			$original_image = imagecreatefromstring($string);
			
			$canvas = self::get_gd_cropping_resource($original_image, $original_w, $original_h, $width, $height);
			
			if(empty($output)){
				header('Content-Type: image/jpeg');
				imagejpeg($canvas);
			}else{
				imagejpeg($canvas,$output);
			}
			imagedestroy($original_image);
			imagedestroy($canvas);
		}
	}
	
	private static function get_resize_info($original_w,$original_h,$width,$height){
		$aw = $width / $original_w;
		$ah = $height / $original_h;
		$a = max($aw,$ah);
		
		$cw = $original_w * $a;
		$ch = $original_h * $a;
		
		return [$cw,$ch];
	}
	private static function get_gd_cropping_resource($original_image,$original_w,$original_h,$width,$height){
		list($cw,$ch) = self::get_resize_info($original_w, $original_h, $width, $height);
		
		$x = 0;
		$y = 0;
		
		if($cw > $width){
			$x = ($cw - $width) / 2;
		}
		if($ch > $height){
			$y = ($ch - $height) / 2;
		}
		
		$canvas = imagecreatetruecolor($cw,$ch);
		imagecopyresampled($canvas,$original_image,0,0,0,0,$cw,$ch,$original_w,$original_h);
		
		if($cw > $width || $ch > $height){
			$canvas = imagecrop($canvas, ['x'=>$x,'y'=>$y,'width'=>$width,'height'=>$height]);
		}
		return $canvas;
	}
}
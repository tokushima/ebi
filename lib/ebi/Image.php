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
			$canvas->cropThumbnailImage($width,$height);
			
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
			$canvas->cropThumbnailImage($width,$height);
			
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
	
	private static function get_gd_cropping_resource($original_image,$original_w,$original_h,$width,$height){
		$aw = $width / $original_w;
		$ah = $height / $original_h;
		$a = max($aw,$ah);
		
		$cw = $original_w * $a;
		$ch = $original_h * $a;
		
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
	
	/**
	 * 画像ファイルから配置情報を算出
	 * @param string $filename
	 * @param number $mm_width
	 * @param number $mm_height
	 * @param number $dpi
	 * @return mixed{}
	 */
	public static function calc_jpeg_layout_info($filename,$mm_width,$mm_height,$dpi=null){
		list($original_w,$original_h) = getimagesize($filename);

		if(empty($dpi)){
			$width_dpi = \ebi\Calc::px2dpi($original_w,$mm_width);
			$height_dpi = \ebi\Calc::px2dpi($original_h,$mm_height);
			
			$dpi = round(($width_dpi < $height_dpi) ? $height_dpi : $width_dpi);
		}
		
		$image_w = \ebi\Calc::px2mm($original_w,$dpi);
		$image_h = \ebi\Calc::px2mm($original_h,$dpi);
		
		$x = ($mm_width / 2) - ($image_w / 2);
		$y = ($mm_height / 2) - ($image_h / 2);
		
		return [
			'x'=>$x,
			'y'=>$y,
			'width'=>$image_w,
			'height'=>$image_h,
			'dpi'=>$dpi,
		];
	}
}
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
	 * 用紙サイズ(mm)
	 * @param string $type
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_mm($type){
		switch(strtoupper($type)){
			case 'A0': return [841,1189];
			case 'A1': return [594,841];
			case 'A2': return [420,594];
			case 'A3': return [297,420];
			case 'A4': return [210,297];
			case 'A5': return [148,210];
			case 'A6': return [105,148];
			case 'A7': return [74,105];
			case 'A8': return [52,74];
			case 'A9': return [37,52];
			case 'A10': return [26,38];
			case 'A11': return [18,26];
			case 'A12': return [13,18];
				
			case 'A3+': return [329,483];
	
			case 'B0': return [1030,1456];
			case 'B1': return [728,1030];
			case 'B2': return [515,728];
			case 'B3': return [364,515];
			case 'B4': return [257,364];
			case 'B5': return [182,257];
			case 'B6': return [128,182];
			case 'B7': return [91,128];
			case 'B8': return [64,91];
			case 'B9': return [45,64];
			case 'B10': return [32,45];
			case 'B11': return [22,32];
			case 'B12': return [16,32];
				
			case 'DSC': return [89,119];
			case 'L': return [89,127];
			case 'HAGAKI': return [100,148];
			case 'KG': return [102,152];
			case '2L': return [127,178];
			case 'CABINET': return [130,180];
			case '6P': return [203,254]; // 六切
			case '4P': return [254,305]; // 四切
			case 'HP': return [356,432]; // 半切
			case 'ZENSHI': return [457,560]; // 全紙
			case 'DAIZENSHI': return [508,610]; // 大全紙
			case 'ZENBAI': return [600,900]; // 全倍
		}
		throw new \ebi\exception\InvalidArgumentException();
	}
}
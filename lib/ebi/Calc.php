<?php
namespace ebi;
/**
 *
 * @author tokushima
 *
 */
class Calc{
	/**
	 * mm -> inch
	 * @param number $mm
	 * @return number
	 */
	public static function mm2in($mm){
		return ($mm * 0.0393701);
	}
	
	/**
	 * inch -> mm
	 * @param number $in
	 * @return number
	 */
	public static function in2mm($in){
		return ($in * 25.4);
	}
	
	/**
	 * mm -> point
	 * @param number $mm
	 * @return number
	 */
	public static function mm2pt($mm){
		return ($mm * 2.83465);
	}
	
	/**
	 * point -> mm
	 * @param number $pt
	 * @return number
	 */
	public static function pt2mm($pt){
		return ($pt * 0.352778);
	}
	
	/**
	 * pixel -> mm
	 * @param number $px
	 * @param number $dpi
	 * @return number
	 */
	public static function px2mm($px,$dpi){
		return ($px / $dpi * 25.4);
	}
	
	/**
	 * mm -> pixel
	 * @param number $mm
	 * @param number $dpi
	 * @return number
	 */
	public static function mm2px($mm,$dpi){
		$x = ($mm / 25.4 * $dpi);
		return ceil($x);
	}
	
	/**
	 * pixel -> point
	 * @param number $px
	 * @param number $dpi
	 * @return number
	 */
	public static function px2pt($px,$dpi){
		return ($px / $dpi * 72);
	}
	
	/**
	 * point -> pixel
	 * @param number $pt
	 * @param number $dpi
	 * @return number
	 */
	public static function pt2px($pt,$dpi){
		$x = ($pt / 72 * $dpi);
		return ceil($x);
	}
	
	/**
	 * pixel -> dpi
	 * @param integer $px
	 * @param number $mm
	 * @param integer $precision;
	 * @return number
	 */
	public static function px2dpi($px,$mm,$precision=0){
		$dpi = ($px / $mm * 25.4);
		return (!empty($precision)) ? round($dpi,$precision) : $dpi;
	}
	
	/**
	 * 用紙サイズ width, height (mm)
	 * @param string $type
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_mm($type){
		switch(strtoupper(str_replace([' ','-'],'',$type))){
			case 'A0': return [841,1189];
			case 'A1': return [594,841];
			case 'A2': return [420,594];
			case 'A3': return [297,420];
			case 'A4': return [210,297];
			case 'A5': return [148,210]; // 学術書、豪華版コミック
			case 'A6': return [105,148]; // 文庫本
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
			case 'B5': return [182,257]; // 週刊誌
			case 'B6': return [128,182]; // 単行本、青年誌コミック
			case 'B7': return [91,128];
			case 'B8': return [64,91];
			case 'B9': return [45,64];
			case 'B10': return [32,45];
			case 'B11': return [22,32];
			case 'B12': return [16,32];
			
			case 'DSC': return [89,119];
			case 'L': return [89,127];
			case 'HAGAKI': return [100,148]; // 官製はがき
			case 'KG': return [102,152];
			case '2L': return [127,178];
			case 'CABINET': return [130,180];
			case '6P': return [203,254]; // 六切
			case '4P': return [254,305]; // 四切
			case 'HP': return [356,432]; // 半切
			case 'ZENSHI': return [457,560]; // 全紙
			case 'DAIZENSHI': return [508,610]; // 大全紙
			case 'ZENBAI': return [600,900]; // 全倍
			case '46':
			case 'SHIROKU': return [127,188]; // 四六判
			case 'B40':
			case 'SHINSHO': return [103,182]; // 新書
			case 'PAPAERBACK': return [114,178]; // ペーパーバック
			case 'B6S':
			case 'B6SMALL': return [112,174]; // 新書判、少年・少女コミック
			case 'CD':
			case 'A5SQUARE': return [148,148]; // A5スクエア、CDケースレーベル
			case 'MEISHI': return [55,91]; // 名刺（９号）
			case 'INSTAXMINI': return [54,86]; // instax mini
			case 'INSTAXSQUARE': return [72,86]; // instax SQUARE
			case 'INSTAXWIDE': return [86,108]; // instax WIDE
		}
		throw new \ebi\exception\InvalidArgumentException();
	}
	
	/**
	 * 拡大率
	 * @param number $a_width
	 * @param number $a_height
	 * @param number $b_width
	 * @param number $b_height
	 * @param boolean $minimum widthまたはheightの値を最小値とする
	 * @return number
	 */
	public static function magnification($a_width,$a_height,$b_width,$b_height=null,$minimum=true){
		$rw = empty($b_width) ? 1 : $b_width;
		$rh = empty($b_height) ? 1 : $b_height;
		
		if(!empty($b_width) && !empty($b_height)){
			$aw = $rw / $a_width;
			$ah = $rh / $a_height;
			return $minimum ? max($aw,$ah) : min($aw,$ah);
		}else if(!isset($b_height)){
			return $rw / $a_width;
		}
		return $rh / $a_height;
	}
}
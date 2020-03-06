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
	 * サイズ width, height (px)
	 * @param string $type
	 * @oaram integer $dpi
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_px($type,$dpi=72){
		list($w,$h) = self::get_size_mm($type);
		
		return [
			self::mm2px($w,$dpi),
			self::mm2px($h,$dpi)
		];
	}
	
	/**
	 * サイズ width, height (pt)
	 * @param string $type
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_pt($type){
		list($w,$h) = self::get_size_mm($type);
		
		return [
			self::mm2pt($w),
			self::mm2pt($h)
		];
	}
	
	/**
	 * サイズ width, height (mm)
	 * @param string $type
	 * @throws \ebi\exception\InvalidArgumentException
	 * @return number[]
	 */
	public static function get_size_mm($type){
		switch(strtoupper(str_replace([' ','-'],'',$type))){
			case 'A0': return [841,1189];
			case 'A1': return [594,841];
			case 'A2': return [420,594];
			case 'A3+': return [329,483]; // A3ノビ
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
			
			case 'DSC': return [89,119]; // デジタルカメラ
			case 'HV': return [89,158]; // ハイビジョン
			case 'L': return [89,127]; // L版
			case 'P': return [89,254]; // パノラマ
			case 'MASHIKAKU': return [89,89]; // ましかくプリント
			
			case 'CD':
			case 'A5SQUARE': return [148,148]; // A5スクエア、CDケースレーベル
			case 'HAGAKI': return [100,148]; // 官製はがき
			
			case 'KG': return [102,152];
			
			case 'DSCW': return [127,169];
			case '2L': return [127,178];
			case 'MASHIKAKU127': return [127,127]; // ましかく127プリント
			case '46':
			case 'SHIROKU': return [127,188]; // 四六判
			
			case 'CABINET': return [130,180];
			case '6P': return [203,254]; // 六切
			case 'W6P': return [203,305]; // W6切
			case '4P': return [254,305]; // 四切
			case 'W4P': return [254,365]; // W4切
			case 'HP': return [356,432]; // 半切
			case 'ZENSHI': return [457,560]; // 全紙
			case 'DAIZENSHI': return [508,610]; // 大全紙
			case 'ZENBAI': return [600,900]; // 全倍
			case 'B40':
			case 'SHINSHO': return [103,182]; // 新書
			case 'PAPAERBACK': return [114,178]; // ペーパーバック
			case 'B6S':
			case 'B6SMALL': return [112,174]; // 新書判、少年・少女コミック
			
			case 'MEISHI': return [55,91]; // 名刺（９号）
			case 'SMARTSIZE': return [51,89]; // 名刺 スマートサイズ
			
			case 'ID1': return [53.98,85.6]; // ISO/IEC7810,JIS X 6301 ID-1 (ISO規格 クレカ・Suica等)
			case 'ID2': return [74,105]; // ID-2 A7判 
			case 'ID3': return [88,125]; // ID-3 B7判 (パスポート)
			
			case 'INSTAXMINI': return [54,86]; // instax mini
			case 'INSTAXSQUARE': return [72,86]; // instax SQUARE
			case 'INSTAXWIDE': return [86,108]; // instax WIDE
		}
		throw new \ebi\exception\InvalidArgumentException();
	}
}
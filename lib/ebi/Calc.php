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
		return ($mm * 0.352778);
	}
	
	/**
	 * pixel -> mm
	 * @param number $px
	 * @param integer $dpi
	 * @return number
	 */
	public static function px2mm($px,$dpi){
		return ($px / $dpi * 25.4);
	}
	
	/**
	 * mm -> pixel
	 * @param number $mm
	 * @param integer $dpi
	 * @param boolean $floor 
	 * @return number
	 */
	public static function mm2px($mm,$dpi,$floor=true){
		$x = ($mm / 25.4 * $dpi);
		return ($floor) ? floor($x) : $x;
	}
	
	/**
	 * pixel -> point
	 * @param number $px
	 * @param integer $dpi
	 * @return number
	 */
	public static function px2pt($px,$dpi){
		return ($px / $dpi * 72);
	}
	
	/**
	 * point -> pixel
	 * @param number $pt
	 * @param integer $dpi
	 * @param boolean $floor
	 * @return number
	 */
	public static function pt2px($pt,$dpi,$floor=true){
		$x = ($pt / 72 * $dpi);
		return ($floor) ? floor($x) : $x;
	}
}
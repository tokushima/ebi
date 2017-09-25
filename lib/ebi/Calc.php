<?php
namespace ebi;
/**
 * 
 * @author tokushima
 *
 */
class Calc{
	public static function mm2in($mm){
		return ($mm * 0.0393701);
	}
	public static function in2mm($in){
		return ($in * 25.4);
	}
	public static function mm2pt($mm){
		return ($mm * 2.83465);
	}
	public static function pt2mm($pt){
		return ($mm * 0.352778);
	}
	public static function px2mm($px,$dpi){
		return ($px / $dpi * 25.4);
	}
	
	public static function mm2px($mm,$dpi){
		return ($mm / 25.4 * $dpi);
	}
	
	public static function px2pt($px,$dpi){
		return ($px / $dpi * 72);
	}
	
	public static function pt2px($pt,$dpi){
		return ($pt / 72 * $dpi);
	}
}
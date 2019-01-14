<?php
namespace ebi;
/**
 * 郵便番号
 * @author tokushima
 *
 */
class ZipCode{
	protected static function conf(){
		return [
			[
				'https://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip',
				'KEN_ALL.CSV',
				function($line){
					list(,,$zip,,,,$pref,$city,$area) = explode(',',$line);
					$facility = '';
					
					if(strpos($pref,'　') !== false){
						list($pref,$facility) = explode('　',$pref,2);
					}
					return [$zip,$pref,$city,$area,$facility];
				}
			],
			[
				'http://www.post.japanpost.jp/zipcode/dl/jigyosyo/zip/jigyosyo.zip',
				'JIGYOSYO.CSV',
				function($line){
					list(,,$facility,$pref,$city,$area1,$area2,$zip) = explode(',',$line);
					return [$zip,$pref,$city,$area1.$area2,$facility];
				}
			]
		];
	}
	
	/**
	 * KEN_ALL.CSV, JIGYOSYO.CSVをダウンロードして整形する
	 * @return array [郵便番号,都道府県,市区町村,以降の住所,建物]
	 */
	public static function read(){
		$download_func = function($url,$csv_filename){
			$work_dir = \ebi\WorkingStorage::tmpdir();
			
			$basename = basename($url);
			file_put_contents($work_dir.'/'.$basename,file_get_contents($url));
			
			$zip = new \ZipArchive();
			$zip->open($work_dir.'/'.$basename);
			$zip->extractTo($work_dir);
			$zip->close();
			
			$src = mb_convert_encoding(file_get_contents($work_dir.'/'.$csv_filename),'UTF-8','SJIS');
			$src = str_replace([' ','"'],[' ',''],$src);
			
			return $src;
		};
		
		$parse_area_func = function($str){
			if(!empty($str)){
				$m = [];
				
				if(preg_match('/\（.+$/',$str,$m)){
					if(mb_substr($m[0],-2) !== '階）'){
						$str = str_replace($m[0],'',$str);
					}
				}
				if(
					mb_strpos($str,'以下に掲載がない場合') !== false ||
					mb_strpos($str,'次に番地がくる場合') !== false ||
					mb_strpos($str,'一円') !== false ||
					mb_strpos($str,'、') !== false ||
					mb_strpos($str,'〜') !== false
				){
					$str = '';
				}
			}
			return str_replace('　','',$str);
		};
		
		foreach(self::conf() as $config){
			foreach(explode(PHP_EOL,$download_func($config[0],$config[1])) as $line){
				if(!empty($line)){
					list($zip,$pref,$city,$area,$facility) = $config[2]($line);
					
					$city = str_replace('　','',$city);
					$area = $parse_area_func($area);
					$facility = $parse_area_func($facility);
					
					var_dump([$zip,$pref,$city,$area,$facility]);					
					yield [$zip,$pref,$city,$area,$facility];
				}
			}
		}
	}
	
	/**
	 * ５桁によるJSONファイルの書き出し
	 * p = 都道府県, c = 市区町村, a = 以降の住所, f = 建物
	 * 郵便番号が重複する場合は d = 住所配列
	 * @param string $out_dir
	 * @return array [郵便番号合計, JSONファイル合計]
	 */
	public static function publish_json($out_dir){		
		$addr = [];
		$zipcnt = $jsoncnt = 0;
		
		foreach(self::read() as $zip){
			$zipcnt++;
			
			$code1 = substr($zip[0],0,3);
			$code2 = substr($zip[0],3,2);
			$code3 = substr($zip[0],5);
			
			if(isset($addr[$code1][$code2][$code3])){
				if(!isset($addr[$code1][$code2][$code3]['d'])){
					$addr[$code1][$code2][$code3]['d'] = [
						$addr[$code1][$code2][$code3]
					];
				}
				
				$addr[$code1][$code2][$code3]['d'][] = [
					'p'=>$zip[1],
					'c'=>$zip[2],
					'a'=>$zip[3],
					'f'=>$zip[4],
				];
			}else{
				$addr[$code1][$code2][$code3] = [
					'p'=>$zip[1],
					'c'=>$zip[2],
					'a'=>$zip[3],
					'f'=>$zip[4],
				];
			}
		}
		foreach($addr as $k1 => $v1){
			foreach($v1 as $k2 => $v2){
				$jsoncnt++;
				\ebi\Util::file_write($out_dir.'/'.$k1.'/'.$k2.'.json',json_encode($v2));
			}
		}
		return [
			'total'=>$zipcnt,
			'json_total'=>$jsoncnt,
		];
	}
}


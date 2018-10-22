<?php
namespace ebi;

class Prefecture{
	private static $pref = [
		1=>'北海道',
		2=>'青森県',
		3=>'岩手県',
		4=>'宮城県',
		5=>'秋田県',
		6=>'山形県',
		7=>'福島県',
		8=>'茨城県',
		9=>'栃木県',
		10=>'群馬県',
		11=>'埼玉県',
		12=>'千葉県',
		13=>'東京都',
		14=>'神奈川県',
		15=>'新潟県',
		16=>'富山県',
		17=>'石川県',
		18=>'福井県',
		19=>'山梨県',
		20=>'長野県',
		21=>'岐阜県',
		22=>'静岡県',
		23=>'愛知県',
		24=>'三重県',
		25=>'滋賀県',
		26=>'京都府',
		27=>'大阪府',
		28=>'兵庫県',
		29=>'奈良県',
		30=>'和歌山県',
		31=>'鳥取県',
		32=>'島根県',
		33=>'岡山県',
		34=>'広島県',
		35=>'山口県',
		36=>'徳島県',
		37=>'香川県',
		38=>'愛媛県',
		39=>'高知県',
		40=>'福岡県',
		41=>'佐賀県',
		42=>'長崎県',
		43=>'熊本県',
		44=>'大分県',
		45=>'宮崎県',
		46=>'鹿児島県',
		47=>'沖縄県',
	];
	
	/**
	 * 
	 * @return string[]
	 */
	public static function gets(){
		return self::$pref;
	}
	
	private static function download($url,$csv_filename){
		$work_dir = \ebi\WorkingStorage::tmpdir();
		$basename = basename($url);
		file_put_contents($work_dir.'/'.$basename,file_get_contents($url));
		
		$zip = new \ZipArchive();
		$zip->open($work_dir.'/'.$basename);
		$zip->extractTo($work_dir);
		$zip->close();
		
		unlink($work_dir.'/'.$basename);
		
		$src = mb_convert_encoding(file_get_contents($work_dir.'/'.$csv_filename),'UTF-8','SJIS');
		$src = str_replace(' ',' ',$src);
		$src = str_replace('"','',$src);
		unlink($work_dir.'/'.$csv_filename);
		return $src;
	}
	
	private static function parse_ken_all($line){
		list(,,$zip,,,,$pref,$city,$area) = explode(',',$line);
		$facility = '';
		
		if(strpos($pref,'　') !== false){
			list($pref,$facility) = explode('　',$pref,2);
		}
		return [$zip,$pref,$city,$area,$facility];
	}
	private static function parse_jigyosyo($line){
		list(,,$facility,$pref,$city,$area1,$area2,$zip) = explode(',',$line);
		return [$zip,$pref,$city,$area1.$area2,$facility];
	}
	private static function parse_area($str){
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
	}
	
	public static function write(){
		$addr = [];
		$cnt = 0;
		foreach([
				$ken_all_url=>[$parse_ken_all_func,'KEN_ALL_ROME.CSV',1],
				$jigyosyo_url=>[$parse_jigyosyo_func,'JIGYOSYO.CSV',2]
		] as $url => $datainfo){
			
			foreach(explode(PHP_EOL,$download_func($url,$datainfo[1])) as $line){
				if(!empty($line)){
					list($zip,$pref,$city,$area,$facility) = $datainfo[0]($line);
					$cnt++;
					
					$zip1 = substr($zip,0,3);
					$zip2 = substr($zip,3,2);
					$zip3 = substr($zip,5);
					
					$city = str_replace('　','',$city);
					$area = $parse_area_func($area);
					$facility = $parse_area_func($facility);
					
					if(isset($addr[$zip1][$zip2][$zip3])){
						if(!isset($addr[$zip1][$zip2][$zip3]['d'])){
							$addr[$zip1][$zip2][$zip3]['d'] = [
									$addr[$zip1][$zip2][$zip3]
							];
						}
						
						$addr[$zip1][$zip2][$zip3]['d'][] = [
								'p'=>$pref,
								'c'=>$city,
								'a'=>$area,
								'f'=>$facility,
						];
					}else{
						$addr[$zip1][$zip2][$zip3] = [
								'p'=>$pref,
								'c'=>$city,
								'a'=>$area,
								'f'=>$facility,
						];
					}
				}
			}
		}
		$output_cnt = 0;
		if(!is_dir($out_dir)){
			mkdir($out_dir);
		}
		foreach($addr as $k1 => $v1){
			foreach($v1 as $k2 => $v2){
				if(!is_dir($out_dir.'/'.$k1)){
					mkdir($out_dir.'/'.$k1,0777,true);
				}
				file_put_contents($out_dir.'/'.$k1.'/'.$k2.'.json',json_encode($v2));
				$output_cnt++;
			}
		}
	}
	
// $ken_all_url = 'https://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip';
// $jigyosyo_url = 'http://www.post.japanpost.jp/zipcode/dl/jigyosyo/zip/jigyosyo.zip';
// https://github.com/tokushima/ken_all_json/blob/master/lib/ZipInfo/cmd/publish.php
	
}
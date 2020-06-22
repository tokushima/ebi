<?php
/**
 * 郵便カスタマーバーコード 文字情報の抜き出し
 */
$get_type = function($str){
	$bits = [
		'0'=>[1,4,4],'1'=>[1,1,4],'2'=>[1,3,2],'3'=>[3,1,2],'4'=>[1,2,3],
		'5'=>[1,4,1],'6'=>[3,2,1],'7'=>[2,1,3],'8'=>[2,3,1],'9'=>[4,1,1],
		'!'=>[3,2,4],'#'=>[3,4,2],'%'=>[2,3,4],'@'=>[4,3,2],'('=>[2,4,3],
		')'=>[4,2,3],'['=>[4,4,1],']'=>[1,1,1],'-'=>[4,1,4],
		
		'S'=>[0,1,3],'E'=>[3,1,0],
	];
	
	$type = [];
	for($i=0;$i<strlen($str);$i++){
		foreach($bits[$str[$i]] as $t){
			array_push($type,0,$t);
		}
	}
	return [$type];
};

// https://www.post.japanpost.jp/zipcode/zipmanual/p25.html
$bar = \ebi\Barcode::CustomerBarcode('263-0023','千葉市稲毛区緑町3丁目30－8 郵便ビル403号');
eq($get_type('S26300233-30-8-403@@@5E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('014-0113','秋田県仙北郡仙北町堀見内 南田茂木 添60－1');
eq($get_type('S014011360-1@@@@@@@@@]E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('1100016','東京都台東区台東5－6－3 ABCビル10F');
eq($get_type('S11000165-6-3-10@@@@@9E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('0600906','北海道札幌市東区北六条東4丁目 郵便センター6号館');
eq($get_type('S06009064-6@@@@@@@@@@9E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('0650006','北海道札幌市東区北六条東8丁目 郵便センター10号館');
eq($get_type('S06500068-10@@@@@@@@@9E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('4070033','山梨県韮崎市龍岡町下條南割 韮崎400');
eq($get_type('S4070033400@@@@@@@@@@-E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('2730102','千葉県鎌ケ谷市右京塚 東3丁目20－5 郵便・A&bコーポB604号');
eq($get_type('S27301023-20-5!1604@@0E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('1980036','東京都青梅市河辺町十一丁目六番地一号 郵便タワー601');
eq($get_type('S198003611-6-1-601@@@]E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('0270203','岩手県宮古市大字津軽石第二十一地割大淵川480');
eq($get_type('S027020321-480@@@@@@@(E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('5900016','大阪府堺市中田出井町四丁目六番十九号');
eq($get_type('S59000164-6-19@@@@@@@#E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('0800831','北海道帯広市稲田町南七線 西28');
eq($get_type('S08008317-28@@@@@@@@@[E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('3170055','茨城県日立市宮田町6丁目7－14 ABCビル2F');
eq($get_type('S31700556-7-14-2@@@@@!E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('6500046','神戸市中央区港島中町9丁目7－6 郵便シティA棟1F1号');
eq($get_type('S65000469-7-6!01-1@@@5E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('6230011','京都府綾部市青野町綾部6－7 LプラザB106');
eq($get_type('S62300116-7#1!1106@@@4E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('2280024','神奈川県座間市入谷6丁目3454－5 郵便ハイツ6－1108');
eq($get_type('S22800246-3454-5-6-112E'),$bar->raw()[1]);

$bar = \ebi\Barcode::CustomerBarcode('9100067','福井県福井市新田塚3丁目80－25 J1ビル2－B');
eq($get_type('S91000673-80-25!91-2!9E'),$bar->raw()[1]);


$bar = \ebi\Barcode::CustomerBarcode('0640804','札幌市中央区南四条西29丁目1524－23 第2郵便ハウス501');
eq($get_type('S064080429-1524-23-2-3E'),$bar->raw()[1]);



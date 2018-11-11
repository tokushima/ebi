<?php

$layers = [
	[
		'x'=>100,
		'y'=>250,
		'src'=>\testman\Resource::path('wani.jpg'),
	],
	[
		'x'=>300,
		'y'=>10,
		'src'=>(new \ebi\Image(\testman\Resource::path('test.jpg')))->resize(100),
	],
	[
		'x'=>200,
		'y'=>300,
		'src'=>(new \ebi\Image(\testman\Resource::path('test.png'))),
	],
	[
		'x'=>200,
		'y'=>30,
		'text'=>'Hello',
	],
	[
		'x'=>400,
		'y'=>0,
		'text'=>'Good',
		'color'=>'#0000ff',
	],
	[
		'x'=>200,
		'y'=>30,
		'text'=>'Good',
		'color'=>'#00ffff',
		'angle'=>90,
	],
	[
		'text'=>'PS Plus加入者特典 電撃PlayStationほか人気雑誌無料プレゼント！（第3弾） (PS4(R)用)',
		'width'=>200,
	],
	[
		'x'=>10,
		'y'=>200,
		'size'=>12,
		'text'=>'じゅげむじゅげむ '.
				'ごこうのすりきれ '.
				'かいじゃりすいぎょの '.
				'すいぎょうまつ '.
				'うんらいまつ '.
				'ふうらいまつ '.
				'くうねるところに'.
				'すむところ '.
				'やぶらこうじの '.
				'ぶらこうじ '.
				'パイポパイポパイポのシューリンガン '.
				'シューリンガンのグーリンダイ '.
				'グーリンダイのポンポコピーのポンポコナーの '.
				'ちょうきゅうめいのちょうすけ',
	],
	[
		'y'=>350,
		'text'=>'It\'s our 8th update for the Affinity Publisher Public Beta! Thanks to all of your participation, this is our most refined build yet. Give it a try and let us know what you think',		
	],
];

$opt = [
	'transparent-color'=>'#FFFFFF',
	'font'=>'/System/Library/Fonts/ヒラギノ明朝 ProN.ttc',
// 	'font'=>'/Users/tokushima/Downloads/M_PLUS_Rounded_1c/MPLUSRounded1c-Bold.ttf',
];

$img = \ebi\Image::flatten(500, 500,$layers,$opt);
// $img->write(\ebi\Conf::work_path('flatten.jpg'));
$img->write(\ebi\WorkingStorage::path('flatten.jpg'));


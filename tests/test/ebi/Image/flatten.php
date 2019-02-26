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
		'size'=>11,
		'leading'=>9,
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
	[
		'x'=>200,
		'y'=>200,
		'src'=>(new \ebi\Image(\testman\Resource::path('mm.png'))),
	],
	[
		'x'=>100,
		'y'=>100,
		'src'=>(new \ebi\Image(\testman\Resource::path('test.png'))),
	],
	[
		'x'=>100,
		'y'=>-50,
		'src'=>(new \ebi\Image(\testman\Resource::path('mm.png'))),
	],		
	[
		'x'=>50,
		'y'=>450,
		'angle'=>-45,
		'size'=>120,
		'text'=>'SAMPLE',
		'color'=>'#0000ff',
		'pct'=>30,
		'z'=>100,
	],
	[
		'x'=>10,
		'y'=>600,
		'size'=>16,
		'text'=>'一',
	],
	[
		'x'=>30,
		'y'=>600,
		'size'=>16,
		'text'=>'ニ',
	],
	[
		'x'=>50,
		'y'=>600,
		'size'=>16,
		'text'=>'六',
	],
	[
		'x'=>70,
		'y'=>600,
		'size'=>16,
		'text'=>'A!',
	],
];

$opt = [
	'transparent-color'=>'#FFFFFF',
	'font'=>'HIRAMIN',
];

list($w,$h) = \ebi\Calc::get_size_px('a4');

\ebi\Image::load_font('/System/Library/Fonts/ヒラギノ明朝 ProN.ttc','HIRAMIN');

$img = \ebi\Image::flatten($w, $h,$layers,$opt);
$img->write(\ebi\Conf::work_path('flatten.jpg'));
//$img->write(\ebi\WorkingStorage::path('flatten.jpg'));


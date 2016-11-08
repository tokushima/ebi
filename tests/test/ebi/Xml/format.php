<?php
$src = <<< XML
<aaa><bbb></bbb><ccc>CCC</ccc><ddd></ddd><eee><FFF>aa</FFF><GGG></GGG></eee><fff /><ggg /></aaa>
XML;

$xml = <<< XML
<aaa>
	<bbb></bbb>
	<ccc>CCC</ccc>
	<ddd></ddd>
	<eee>
		<FFF>aa</FFF>
		<GGG></GGG>
	</eee>
	<fff />
	<ggg />
</aaa>

XML;





$f = \ebi\Xml::format($src);

eq($xml,$f);


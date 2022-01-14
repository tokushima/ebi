<?php
/**
 * @param int $n @['init'=>100]
 */

\cmdman\Std::println();

for($i=1;$i<=$n;$i++){
	if($i % 1000 === 0){
		\cmdman\Std::backspace(30);
		print('Cnt. '.number_format($i));
		
		\test\db\Data::commit();
	}
	\test\db\Data::sample();
}
\cmdman\Std::println();
\cmdman\Std::println('Total. '.number_format(\test\db\Data::find_count()));

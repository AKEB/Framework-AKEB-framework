<?php

require_once(__DIR__."/vendor/autoload.php");
set_time_limit(0);

function delTree($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

@mkdir('./src/dist/', 0755, true);
delTree('./src/dist/');
@mkdir('./src/dist/css/fonts/', 0755, true);
@mkdir('./src/dist/js/', 0755, true);

echo "Minify CSS...";
$minify = \AKEB\Minify\MinifierFactory::create('css');

$minify->addFile('./src/css/bootstrap-icons.min.css');
$minify->addFile('./src/css/bootstrap.min.css');
$minify->addFile('./src/css/datatables.min.css');
$minify->addFile('./src/css/bootstrap-select.min.css');
$minify->addFile('./src/css/main.css');

$minify->toFile('./src/dist/css/framework.min.css');
echo "Done.\n";

// Fonts
echo "Copy fonts...";
copy('./src/css/fonts/bootstrap-icons.woff2', './src/dist/css/fonts/bootstrap-icons.woff2');
echo "Done.\n";

// JS-RU
echo "Minify JS ru...";
$minifier = new \Bissolli\PhpMinifier\Minifier();

$minifier->addJsFile('./src/js/bootstrap.bundle.min.js');
$minifier->addJsFile('./src/js/datatables.min.js');
$minifier->addJsFile('./src/js/bootstrap-select.min.js');
$minifier->addJsFile('./src/js/bootstrap-select-ru.min.js');
$minifier->addJsFile('./src/js/locale_ru.js');
$minifier->addJsFile('./src/js/main.js');

$output = $minifier->minifyJs()->outputJs('./src/dist/js/framework_ru.min.js');
echo "Done.\n";


// JS-EN
echo "Minify JS en...";
$minifier = new \Bissolli\PhpMinifier\Minifier();

$minifier->addJsFile('./src/js/bootstrap.bundle.min.js');
$minifier->addJsFile('./src/js/datatables.min.js');
$minifier->addJsFile('./src/js/bootstrap-select.min.js');
$minifier->addJsFile('./src/js/bootstrap-select-en.min.js');
$minifier->addJsFile('./src/js/locale_en.js');
$minifier->addJsFile('./src/js/main.js');

$output = $minifier->minifyJs()->outputJs('./src/dist/js/framework_en.min.js');
echo "Done.\n";

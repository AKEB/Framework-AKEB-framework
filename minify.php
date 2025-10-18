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
$files = [
	'./src/css/bootstrap-icons.min.css',
	'./src/css/bootstrap.min.css',
	'./src/css/datatables.min.css',
	'./src/css/bootstrap-select.min.css',
	'./src/css/main.css',
];
$data = '';
foreach ($files as $file) {
	$data .= "\n".file_get_contents($file)."\n";
}
file_put_contents('./src/dist/css/framework.min.css', $data);
echo "Done.\n";

// Fonts
echo "Copy fonts...";
copy('./src/css/fonts/bootstrap-icons.woff2', './src/dist/css/fonts/bootstrap-icons.woff2');
echo "Done.\n";

// JS-RU
echo "Minify JS ru...";
$files = [
	'./src/js/bootstrap.bundle.min.js',
	'./src/js/datatables.min.js',
	'./src/js/bootstrap-select.min.js',
	'./src/js/bootstrap-select-ru.min.js',
	'./src/js/locale_ru.js',
	'./src/js/main.js',
];
$data = '';
foreach ($files as $file) {
	$data .= "\n".file_get_contents($file)."\n";
}
file_put_contents('./src/dist/js/framework_ru.min.js', $data);
// $minifier = new \Bissolli\PhpMinifier\Minifier();
// $minifier->addJsFile('./src/js/bootstrap.bundle.min.js');
// $minifier->addJsFile('./src/js/datatables.min.js');
// $minifier->addJsFile('./src/js/bootstrap-select.min.js');
// $minifier->addJsFile('./src/js/bootstrap-select-ru.min.js');
// $minifier->addJsFile('./src/js/locale_ru.js');
// $minifier->addJsFile('./src/js/main.js');
// $output = $minifier->minifyJs()->outputJs('./src/dist/js/framework_ru.min.js');
echo "Done.\n";


// JS-EN
echo "Minify JS en...";
$files = [
	'./src/js/bootstrap.bundle.min.js',
	'./src/js/datatables.min.js',
	'./src/js/bootstrap-select.min.js',
	'./src/js/bootstrap-select-en.min.js',
	'./src/js/locale_en.js',
	'./src/js/main.js',
];
$data = '';
foreach ($files as $file) {
	$data .= "\n".file_get_contents($file)."\n";
}
file_put_contents('./src/dist/js/framework_en.min.js', $data);

// $minifier = new \Bissolli\PhpMinifier\Minifier();

// $minifier->addJsFile('./src/js/bootstrap.bundle.min.js');
// $minifier->addJsFile('./src/js/datatables.min.js');
// $minifier->addJsFile('./src/js/bootstrap-select.min.js');
// $minifier->addJsFile('./src/js/bootstrap-select-en.min.js');
// $minifier->addJsFile('./src/js/locale_en.js');
// $minifier->addJsFile('./src/js/main.js');

// $output = $minifier->minifyJs()->outputJs('./src/dist/js/framework_en.min.js');
echo "Done.\n";

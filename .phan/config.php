<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['scalar_implicit_cast'] = true;

$cfg['target_php_version'] = '8.1';
$cfg['directory_list'] = [
	'src',
	'tests',
	'vendor',
];
$cfg['exclude_analysis_directory_list'] = [ 'vendor/', 'coverage/', 'doc/' ];

// Makes phan crash, see T324207.
$cfg['exclude_file_list'][] = 'tests/Objects/CSSObjectListTest.php';

// By default mediawiki-phan-config ignores the 'use of deprecated <foo>' errors.
// $cfg['suppress_issue_types'][] = '<some phan issue>';

return $cfg;

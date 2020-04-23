<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['scalar_implicit_cast'] = true;

$cfg['target_php_version'] = '7.4';
$cfg['directory_list'] = [
	'src',
	'tests',
	'vendor',
];
$cfg['exclude_analysis_directory_list'] = [ 'vendor/', 'coverage/', 'doc/' ];

// T311928 - ReturnTypeWillChange only exists in PHP >= 8.1; seen as a comment on PHP < 8.0
$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	class_exists( ReturnTypeWillChange::class ) ? [] : [ '.phan/stubs/ReturnTypeWillChange.php' ]
);

// By default mediawiki-phan-config ignores the 'use of deprecated <foo>' errors.
// $cfg['suppress_issue_types'][] = '<some phan issue>';

return $cfg;

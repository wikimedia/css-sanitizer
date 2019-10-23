<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['target_php_version'] = '7.2';
$cfg['directory_list'] = [
	'src',
	'tests',
	'vendor',
];
$cfg['exclude_file_regex'] = '@^vendor/(' . implode( '|', [
	'jakub-onderka/php-parallel-lint',
	'jakub-onderka/php-console-highlighter',
	'mediawiki/mediawiki-codesniffer',
	'phan/phan',
	'phpunit/php-code-coverage',
	'squizlabs/php_codesniffer',
] ) . ')/@';
$cfg['exclude_analysis_directory_list'] = [ 'vendor/', 'coverage/', 'doc/' ];

// By default mediawiki-phan-config ignores the 'use of deprecated <foo>' errors.
// $cfg['suppress_issue_types'][] = '<some phan issue>';

return $cfg;

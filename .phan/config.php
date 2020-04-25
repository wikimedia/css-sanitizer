<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['scalar_implicit_cast'] = true;

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

// PHP 7.4 adds int parameter type to SeekableIterator::seek( $offset ). When this library requires
// PHP >=7.4, add int param type to $offset and remove this conditional. See T250934/T251043
if ( PHP_VERSION_ID >= 70400 ) {
	$cfg['allow_method_param_type_widening'] = true;
}

return $cfg;

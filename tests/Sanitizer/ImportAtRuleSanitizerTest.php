<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\ImportAtRuleSanitizer
 */
class ImportAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new ImportAtRuleSanitizer( TestMatcherFactory::singleton() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'import' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'import' ] ],
			],
			'ok' => [
				'@import url("foo.css");',
				true,
				'@import url("foo.css");',
				'@import url("foo.css");',
			],
			'ok 2' => [
				'@import "foo.css";',
				true,
				'@import "foo.css";',
				'@import"foo.css";',
			],
			'bad url' => [
				'@import url("bad.xss");',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'bad url 2' => [
				'@import "bad.xss";',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'bad value' => [
				'@import foo.css;',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'missing value' => [
				'@import ;',
				true,
				null,
				null,
				[ [ 'missing-import-source', 1, 1 ] ],
			],
			'block not allowed' => [
				'@import "foo.css" {}',
				true,
				null,
				null,
				[ [ 'at-rule-block-not-allowed', 1, 19, 'import' ] ],
			],
		];
	}
}

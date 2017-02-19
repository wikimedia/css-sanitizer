<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\NamespaceAtRuleSanitizer
 */
class NamespaceAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new NamespaceAtRuleSanitizer( TestMatcherFactory::singleton() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'namespace' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'namespace' ] ],
			],
			'ok' => [
				'@namespace url("namespace");',
				true,
				'@namespace url("namespace");',
				'@namespace url("namespace");',
			],
			'ok 2' => [
				'@namespace "namespace";',
				true,
				'@namespace "namespace";',
				'@namespace"namespace";',
			],
			'bad url' => [
				'@namespace url("bad.xss");',
				true,
				null,
				null,
				[ [ 'invalid-namespace-value', 1, 12 ] ],
			],
			'bad url 2' => [
				'@namespace "bad.xss";',
				true,
				null,
				null,
				[ [ 'invalid-namespace-value', 1, 12 ] ],
			],
			'with ident' => [
				'@namespace foo url("namespace");',
				true,
				'@namespace foo url("namespace");',
				'@namespace foo url("namespace");',
			],
			'only ident' => [
				'@namespace foo;',
				true,
				null,
				null,
				[ [ 'invalid-namespace-value', 1, 12 ] ],
			],
			'bad value' => [
				'@namespace foo bar url("namespace");',
				true,
				null,
				null,
				[ [ 'invalid-namespace-value', 1, 12 ] ],
			],
			'missing value' => [
				'@namespace ;',
				true,
				null,
				null,
				[ [ 'missing-namespace-value', 1, 1 ] ],
			],
			'block not allowed' => [
				'@namespace "namespace" {}',
				true,
				null,
				null,
				[ [ 'at-rule-block-not-allowed', 1, 24, 'namespace' ] ],
			],
		];
	}
}

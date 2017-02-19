<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\FontFeatureValueAtRuleSanitizer
 */
class FontFeatureValueAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new FontFeatureValueAtRuleSanitizer( 'x', TestMatcherFactory::singleton()->number() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'x' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'x' ] ],
			],
			'block required' => [
				'@x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'x' ] ],
			],
			'prelude not allowed' => [
				'@x x {}',
				true,
				null,
				null,
				[ [ 'invalid-font-feature-value', 1, 1, 'x' ] ],
			],
			'declarations' => [
				'@x { a: 1; b: 2; c: 3 4; }',
				true,
				'@x { a: 1; b: 2; }',
				'@x{a:1;b:2}',
				[
					[ 'invalid-font-feature-value-declaration', 1, 18, 'x' ],
				],
			],
		];
	}
}

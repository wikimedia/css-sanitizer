<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\FontFeatureValuesAtRuleSanitizer
 */
class FontFeatureValuesAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new FontFeatureValuesAtRuleSanitizer( TestMatcherFactory::singleton() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'font-feature-values' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'font-feature-values' ] ],
			],
			'block required' => [
				'@font-feature-values;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'font-feature-values' ] ],
			],
			'ok' => [
				'@font-feature-values "foo bar" {}',
				true,
				'@font-feature-values "foo bar" {}',
				'@font-feature-values"foo bar"{}',
			],
			'ok 2' => [
				'@font-feature-values foo bar {}',
				true,
				'@font-feature-values foo bar {}',
				'@font-feature-values foo bar{}',
			],
			'no prelude' => [
				'@font-feature-values {}',
				true,
				null,
				null,
				[ [ 'missing-font-feature-values-font-list', 1, 1 ] ],
			],
			'bad prelude' => [
				'@font-feature-values "foo" "bar" {}',
				true,
				null,
				null,
				[ [ 'invalid-font-feature-values-font-list', 1, 22 ] ],
			],
			'declarations' => [
				'@font-feature-values foo {
					bogus: foo;
					bogus {}
					@stylistic { a: 1; b: 2; c: 3 4; }
					@styleset { a: 1; b: 2 3; c: 3 4 5 6 7; d:; e: 5, 6; f:1 }
					@character-variant { a: 1; b: 2 3; c: 3 4 5 6 7; d:; e: 5, 6; f:1 }
					@swash { a: 1; b: 2; c: 3 4; }
					@swash x { a: 1; b: 2; c: 3 4; }
					@ornaments { a: 1; b: 2; c: 3 4; }
					@annotation { a: 1; ; b: 2; c: 3 4; }
					@font-feature-values foo {}
					@font-feature-values {}
				}',
				true,
				// @codingStandardsIgnoreStart Ignore Generic.Files.LineLength.TooLong
				'@font-feature-values foo { @stylistic { a: 1; b: 2; } @styleset { a: 1; b: 2 3; c: 3 4 5 6 7; f:1 ; } @character-variant { a: 1; b: 2 3; f:1 ; } @swash { a: 1; b: 2; } @ornaments { a: 1; b: 2; } @annotation { a: 1; b: 2; } }',
				'@font-feature-values foo{@stylistic{a:1;b:2}@styleset{a:1;b:2 3;c:3 4 5 6 7;f:1}@character-variant{a:1;b:2 3;f:1}@swash{a:1;b:2}@ornaments{a:1;b:2}@annotation{a:1;b:2}}',
				// @codingStandardsIgnoreEnd
				[
					[ 'unrecognized-rule', 2, 6 ],
					[ 'invalid-font-feature-value-declaration', 4, 31, 'stylistic' ],
					[ 'invalid-font-feature-value-declaration', 5, 46, 'styleset' ],
					[ 'invalid-font-feature-value-declaration', 5, 50, 'styleset' ],
					[ 'invalid-font-feature-value-declaration', 6, 41, 'character-variant' ],
					[ 'invalid-font-feature-value-declaration', 6, 55, 'character-variant' ],
					[ 'invalid-font-feature-value-declaration', 6, 59, 'character-variant' ],
					[ 'invalid-font-feature-value-declaration', 7, 27, 'swash' ],
					[ 'invalid-font-feature-value', 8, 6, 'swash' ],
					[ 'invalid-font-feature-value-declaration', 9, 31, 'ornaments' ],
					[ 'invalid-font-feature-value-declaration', 10, 34, 'annotation' ],
					[ 'unrecognized-rule', 11, 6 ],
					[ 'unrecognized-rule', 12, 6 ]
				],
			],
		];
	}
}

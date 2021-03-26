<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\KeyframesAtRuleSanitizer
 */
class KeyframesAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		$matcherFactory = MatcherFactory::singleton();
		return new KeyframesAtRuleSanitizer(
			$matcherFactory, new StylePropertySanitizer( $matcherFactory )
		);
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'keyframes' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'keyframes' ] ],
			],
			'block required' => [
				'@keyframes x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'keyframes' ] ],
			],
			'prelude required' => [
				'@keyframes {}',
				true,
				null,
				null,
				[ [ 'missing-keyframe-name', 1, 1 ] ],
			],
			'bad prelude' => [
				'@keyframes x x {}',
				true,
				null,
				null,
				[ [ 'invalid-keyframe-name', 1, 12 ] ],
			],
			'bad prelude (reserved ident)' => [
				'@keyframes nOnE {}',
				true,
				null,
				null,
				[ [ 'invalid-keyframe-name', 1, 12 ] ],
			],
			'ok' => [
				'@keyframes x {}',
				true,
				'@keyframes x {}',
				'@keyframes x{}',
			],
			'ok (string)' => [
				'@keyframes "none" {}',
				true,
				'@keyframes "none" {}',
				'@keyframes"none"{}',
			],
			'declarations' => [
				'@keyframes x {
					from {
						color: black;
						color: bogus;
						bogus: bogus;
					}

					10%, 20% {
						color: red;
						color: bogus;
						bogus: bogus;
					}

					30%, bogus {
						color: maroon;
					}

					90%, to {
						color: white;
						color: bogus;
						bogus: bogus;
					}
				}',
				true,
				'@keyframes x { from { color: black; } 10%, 20% { color: red; } 90%, to { color: white; } }',
				'@keyframes x{from{color:black}10%,20%{color:red}90%,to{color:white}}',
				[
					[ 'bad-value-for-property', 4, 14, 'color' ],
					[ 'unrecognized-property', 5, 7 ],
					[ 'bad-value-for-property', 10, 14, 'color' ],
					[ 'unrecognized-property', 11, 7 ],
					[ 'invalid-selector-list', 14, 6 ],
					[ 'bad-value-for-property', 20, 14, 'color' ],
					[ 'unrecognized-property', 21, 7 ],
				]
			],
		];
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\PageAtRuleSanitizer
 */
class PageAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		$matcherFactory = MatcherFactory::singleton();
		return new PageAtRuleSanitizer(
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
				[ [ 'expected-at-rule', 1, 1, 'page' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'page' ] ],
			],
			'block required' => [
				'@page x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'page' ] ],
			],
			'bad prelude' => [
				'@page x x {}',
				true,
				null,
				null,
				[ [ 'invalid-page-selector', 1, 7 ] ],
			],
			'ok' => [
				'@page {}',
				true,
				'@page {}',
				'@page{}',
			],
			'ok 2' => [
				'@page x {}',
				true,
				'@page x {}',
				'@page x{}',
			],
			'ok 3' => [
				'@page x:left, :first, :right:blank {}',
				true,
				'@page x:left, :first, :right:blank {}',
				'@page x:left,:first,:right:blank{}',
			],
			'declarations' => [
				'@page x {
					color: red;
					bogus: foo;
					size: 10in;
					size: 8.5in 11in;
					size: a4 landscape;

					@top-left {
						color: red;
						bogus: foo;
						size: 10in;
					}

					@foo {}

					color: blue;

					@top-right {}
				}',
				true,
				// @codingStandardsIgnoreStart Ignore Generic.Files.LineLength.TooLong
				'@page x { color: red; size: 10in; size: 8.5in 11in; size: a4 landscape; @top-left { color: red; } color: blue; @top-right {} }',
				'@page x{color:red;size:10in;size:8.5in 11in;size:a4 landscape;@top-left{color:red}color:blue;@top-right{}}',
				// @codingStandardsIgnoreEnd
				[
					[ 'unrecognized-property', 3, 6 ],
					[ 'unrecognized-property', 10, 7 ],
					[ 'unrecognized-property', 11, 7 ],
					[ 'invalid-page-rule-content', 14, 6 ],
				]
			],
		];
	}
}

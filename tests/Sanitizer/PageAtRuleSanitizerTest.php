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
					marks: cross crop;
					bleed: 10px;

					@top-left {
						color: red;
						bogus: foo;
						size: 10in;
					}

					@foo {}

					color: blue;
					bleed: auto;

					@top-right {}
				}',
				true,
				// phpcs:disable Generic.Files.LineLength
				'@page x { color:red; size:10in; size:8.5in 11in; size:a4 landscape; marks:cross crop; bleed:10px; @top-left { color:red; } color:blue; bleed:auto; @top-right {} }',
				'@page x{color:red;size:10in;size:8.5in 11in;size:a4 landscape;marks:cross crop;bleed:10px;@top-left{color:red}color:blue;bleed:auto;@top-right{}}',
				// phpcs:enable
				[
					[ 'unrecognized-property', 3, 6 ],
					[ 'unrecognized-property', 12, 7 ],
					[ 'unrecognized-property', 13, 7 ],
					[ 'invalid-page-rule-content', 16, 6 ],
				]
			],
		];
	}
}

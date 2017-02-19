<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\MarginAtRuleSanitizer
 */
class MarginAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new MarginAtRuleSanitizer(
			new StylePropertySanitizer( MatcherFactory::singleton() )
		);
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-page-margin-at-rule', 1, 1 ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-page-margin-at-rule', 1, 1 ] ],
			],
			'block required' => [
				'@top-left;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'top-left' ] ],
			],
			'no prelude allowed' => [
				'@top-left x {}',
				true,
				null,
				null,
				[ [ 'invalid-page-margin-at-rule', 1, 1, 'top-left' ] ],
			],
			'ok, top-left-corner' => [
				'@top-left-corner {}', true, '@top-left-corner {}', '@top-left-corner{}'
			],
			'ok, top-left' => [ '@top-left {}', true, '@top-left {}', '@top-left{}' ],
			'ok, top-center' => [ '@top-center {}', true, '@top-center {}', '@top-center{}' ],
			'ok, top-right' => [ '@top-right {}', true, '@top-right {}', '@top-right{}' ],
			'ok, top-right-corner' => [
				'@top-right-corner {}', true, '@top-right-corner {}', '@top-right-corner{}'
			],
			'ok, bottom-left-corner' => [
				'@bottom-left-corner {}', true, '@bottom-left-corner {}', '@bottom-left-corner{}'
			],
			'ok, bottom-left' => [ '@bottom-left {}', true, '@bottom-left {}', '@bottom-left{}' ],
			'ok, bottom-center' => [ '@bottom-center {}', true, '@bottom-center {}', '@bottom-center{}' ],
			'ok, bottom-right' => [ '@bottom-right {}', true, '@bottom-right {}', '@bottom-right{}' ],
			'ok, bottom-right-corner' => [
				'@bottom-right-corner {}', true, '@bottom-right-corner {}', '@bottom-right-corner{}'
			],
			'ok, left-top' => [ '@left-top {}', true, '@left-top {}', '@left-top{}' ],
			'ok, left-middle' => [ '@left-middle {}', true, '@left-middle {}', '@left-middle{}' ],
			'ok, left-bottom' => [ '@left-bottom {}', true, '@left-bottom {}', '@left-bottom{}' ],
			'ok, right-top' => [ '@right-top {}', true, '@right-top {}', '@right-top{}' ],
			'ok, right-middle' => [ '@right-middle {}', true, '@right-middle {}', '@right-middle{}' ],
			'ok, right-bottom' => [ '@right-bottom {}', true, '@right-bottom {}', '@right-bottom{}' ],
			'declarations' => [
				'@top-left {
					color: red;
					color: bogus;
					bogus: bogus;
				}',
				true,
				'@top-left { color: red; }',
				'@top-left{color:red}',
				[
					[ 'bad-value-for-property', 3, 13, 'color' ],
					[ 'unrecognized-property', 4, 6 ],
				]
			],
		];
	}
}

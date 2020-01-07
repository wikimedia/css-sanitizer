<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\FontFaceAtRuleSanitizer
 */
class FontFaceAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new FontFaceAtRuleSanitizer( TestMatcherFactory::singleton() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'font-face' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'font-face' ] ],
			],
			'block required' => [
				'@font-face;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'font-face' ] ],
			],
			'ok' => [
				'@font-face {}',
				true,
				'@font-face {}',
				'@font-face{}',
			],
			'no prelude' => [
				'@font-face x {}',
				true,
				null,
				null,
				[ [ 'invalid-font-face-at-rule', 1, 1 ] ],
			],
			'declarations' => [
				'@font-face {
					font-family: "foo bar";
					font-family: foo bar;
					font-family: 23;
					src: local("foo bar");
					src: local(foo bar), url("font.fnt") format("a","b"), url(font2.fnt);
					src: url("bad.fnt");
					font-style: italic;
					font-style: bogus;
					font-weight: bold;
					font-weight: 600;
					font-weight: 601;
					font-stretch: normal;
					unicode-range: U+0-7F, U+1000;
					unicode-range: U+110000;
					unicode-range: U+200-100;
					/*font-variant: super slashed-zero common-ligatures unicase tabular-nums proportional-width;*/
					font-feature-settings: "abcd", "defg" off, "qq ~" 99;
					display: none;
					@font-face {}
					font-weight: bold
				}',
				true,
				// phpcs:disable Generic.Files.LineLength
				'@font-face { font-family:"foo bar"; font-family:foo bar; src:local("foo bar"); src:local(foo bar), url("font.fnt") format("a","b"), url("font2.fnt"); font-style:italic; font-weight:bold; font-weight:600; font-stretch:normal; unicode-range:U+0-7F, U+1000; font-feature-settings:"abcd", "defg" off, "qq ~" 99; font-weight:bold; }',
				'@font-face{font-family:"foo bar";font-family:foo bar;src:local("foo bar");src:local(foo bar),url("font.fnt")format("a","b"),url("font2.fnt");font-style:italic;font-weight:bold;font-weight:600;font-stretch:normal;unicode-range:U+0-7F,U+1000;font-feature-settings:"abcd","defg"off,"qq ~"99;font-weight:bold}',
				// phpcs:enable
				[
					[ 'unexpected-token-in-declaration-list', 20, 6 ],
					[ 'bad-value-for-property', 4, 19, 'font-family' ],
					[ 'bad-value-for-property', 7, 11, 'src' ],
					[ 'bad-value-for-property', 9, 18, 'font-style' ],
					[ 'bad-value-for-property', 12, 19, 'font-weight' ],
					[ 'bad-value-for-property', 15, 21, 'unicode-range' ],
					[ 'bad-value-for-property', 16, 21, 'unicode-range' ],
					[ 'unrecognized-property', 19, 6 ],
				]
			],
		];
	}
}

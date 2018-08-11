<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Sanitizer\MediaAtRuleSanitizer
 */
class MediaAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		$matcherFactory = MatcherFactory::singleton();
		$san = new MediaAtRuleSanitizer( $matcherFactory->cssMediaQueryList() );
		$ruleSanitizers = [
			new StyleRuleSanitizer(
				$matcherFactory->cssSelectorList(),
				new StylePropertySanitizer( $matcherFactory ),
				[
					'prependSelectors' => [ new Token( Token::T_IDENT, 'div' ) ]
				]
			),
			$san,
		];
		$san->setRuleSanitizers( $ruleSanitizers );
		$this->assertSame( $ruleSanitizers, $san->getRuleSanitizers() );
		return $san;
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'media' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'media' ] ],
			],
			'block required' => [
				'@media x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'media' ] ],
			],
			'ok' => [
				'@media {}',
				true,
				'@media {}',
				'@media{}',
			],
			'ok 2' => [
				'@media screen and (min-width:100px), print {}',
				true,
				'@media screen and (min-width:100px), print {}',
				'@media screen and (min-width:100px),print{}',
			],
			'bad media query' => [
				'@media x {}',
				true,
				null,
				null,
				[ [ 'invalid-media-query', 1, 8 ] ],
			],
			'declarations' => [
				'@media {
					.foo bar, #baz {
						color: red;
						margin: calc(10px * 3 + 10%)
					}

					x "huh?" {}

					@bogus;

					@media (width > 100px) {
						@media (height > 100px) {
							#yeah { display:none; foo:bar; }
						}

						@media (height <= 100px) {
							#nope { display:none; }
						}
					}
				}',
				true,
				// @codingStandardsIgnoreStart Ignore Generic.Files.LineLength.TooLong
				'@media { div .foo bar, div #baz { color: red; margin: calc(10px * 3 + 10%) ; } @media (width > 100px) { @media (height > 100px) { div #yeah { display:none; } } @media (height <= 100px) { div #nope { display:none; } } } }',
				'@media{div .foo bar,div #baz{color:red;margin:calc(10px*3 + 10%)}@media(width>100px){@media(height>100px){div #yeah{display:none}}@media(height<=100px){div #nope{display:none}}}}',
				// @codingStandardsIgnoreEnd
				[
					[ 'invalid-selector-list', 7, 6 ],
					[ 'unrecognized-rule', 9, 6 ],
					[ 'unrecognized-property', 13, 30 ],
				]
			],
		];
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Sanitizer\SupportsAtRuleSanitizer
 */
class SupportsAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		$matcherFactory = MatcherFactory::singleton();
		$propSan = new StylePropertySanitizer( $matcherFactory );
		if ( !empty( $options['declarationSanitizer'] ) ) {
			$options['declarationSanitizer'] = $propSan;
		}
		$san = new SupportsAtRuleSanitizer( $matcherFactory, $options );
		$ruleSanitizers = [
			$san,
			new StyleRuleSanitizer(
				$matcherFactory->cssSelectorList(),
				$propSan,
				[
					'prependSelectors' => [ new Token( Token::T_IDENT, 'div' ) ]
				]
			),
		];
		$san->setRuleSanitizers( $ruleSanitizers );
		$this->assertSame( $ruleSanitizers, $san->getRuleSanitizers() );
		return $san;
	}

	public function testException() {
		$matcherFactory = MatcherFactory::singleton();
		$this->expectException( \TypeError::class );
		// The exact TypeError message differs between php7 and php8 (nullables)
		$this->expectExceptionMessage( 'Wikimedia\CSS\Sanitizer\PropertySanitizer' );
		// @phan-suppress-next-line PhanNoopNew
		new SupportsAtRuleSanitizer( $matcherFactory, [
			'declarationSanitizer' => new NamespaceAtRuleSanitizer( $matcherFactory ),
		] );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'supports' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'supports' ] ],
			],
			'block required' => [
				'@supports x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'supports' ] ],
			],
			'prelude required' => [
				'@supports {}',
				true,
				null,
				null,
				[ [ 'missing-supports-condition', 1, 1 ] ],
			],
			'ok' => [
				'@supports (color:red) {}',
				true,
				'@supports (color:red) {}',
				'@supports(color:red){}',
			],
			'not ok, invalid declaration' => [
				'@supports (color) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
			],
			'not ok, missing spaces (not)' => [
				'@supports not(color) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
			],
			'not ok, missing spaces (and)' => [
				'@supports (color) and(color) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
			],
			'not ok, missing spaces (or)' => [
				'@supports (color)or (color) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
			],
			'ok, with spaces' => [
				'@supports ( color : red ) and ( color : green ) {}',
				true,
				'@supports ( color : red ) and ( color : green ) {}',
				'@supports(color:red) and (color:green){}',
			],
			'ok, validating property' => [
				'@supports ( color: red ) {}',
				true,
				'@supports ( color: red ) {}',
				'@supports(color:red){}',
				[],
				[ 'declarationSanitizer' => true ],
			],
			'ok because not validating property' => [
				'@supports ( color: bogus ) {}',
				true,
				'@supports ( color: bogus ) {}',
				'@supports(color:bogus){}',
			],
			'not ok because validating property' => [
				'@supports ( color: bogus ) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
				[ 'declarationSanitizer' => true ],
			],
			'ok, complex' => [
				'@supports ((a:b)) and (not (a:b)) and ((a:b) or (b:c) or (c:d)) {}',
				true,
				'@supports ((a:b)) and (not (a:b)) and ((a:b) or (b:c) or (c:d)) {}',
				'@supports((a:b)) and (not (a:b)) and ((a:b) or (b:c) or (c:d)){}',
			],
			'ok, general-enclosed in non-strict' => [
				'@supports func(a:b?!) {}',
				true,
				'@supports func(a:b?!) {}',
				'@supports func(a:b?!){}',
				[],
				[ 'strict' => false ],
			],
			'not ok, general-enclosed in strict' => [
				'@supports func(a:b?!) {}',
				true,
				null,
				null,
				[ [ 'invalid-supports-condition', 1, 11 ] ],
			],
			'declarations' => [
				'@supports (a:b) {
					.foo bar, #baz {
						color: red;
					}

					x "huh?" {}

					@bogus;

					@supports (c:d) {
						@supports (e:f) {
							#yeah { display:none; foo:bar; }
						}

						@supports not (e:f) {
							#nope { display:none; }
						}
					}
				}',
				true,
				// phpcs:disable Generic.Files.LineLength
				'@supports (a:b) { div .foo bar, div #baz { color:red; } @supports (c:d) { @supports (e:f) { div #yeah { display:none; } } @supports not (e:f) { div #nope { display:none; } } } }',
				'@supports(a:b){div .foo bar,div #baz{color:red}@supports(c:d){@supports(e:f){div #yeah{display:none}}@supports not (e:f){div #nope{display:none}}}}',
				// phpcs:enable
				[
					[ 'invalid-selector-list', 6, 6 ],
					[ 'unrecognized-rule', 8, 6 ],
					[ 'unrecognized-property', 12, 30 ],
				]
			],
		];
	}
}

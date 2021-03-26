<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;

/**
 * @covers \Wikimedia\CSS\Sanitizer\StylesheetSanitizer
 */
class StylesheetSanitizerTest extends \PHPUnit\Framework\TestCase {

	protected function getSanitizer() {
		$matcherFactory = MatcherFactory::singleton();
		$propertySanitizer = new StylePropertySanitizer( $matcherFactory );
		$ruleSanitizers = [
			new StyleRuleSanitizer( $matcherFactory->cssSelectorList(), $propertySanitizer ),
			new FontFaceAtRuleSanitizer( $matcherFactory ),
			new FontFeatureValuesAtRuleSanitizer( $matcherFactory ),
			new KeyframesAtRuleSanitizer( $matcherFactory, $propertySanitizer ),
			new PageAtRuleSanitizer( $matcherFactory, $propertySanitizer ),
			'media' => new MediaAtRuleSanitizer( $matcherFactory->cssMediaQueryList() ),
			'supports' => new SupportsAtRuleSanitizer( $matcherFactory, [
				'declarationSanitizer' => $propertySanitizer,
			] ),
		];
		$ruleSanitizers['media']->setRuleSanitizers( $ruleSanitizers );
		$ruleSanitizers['supports']->setRuleSanitizers( $ruleSanitizers );
		$allRuleSanitizers = array_merge( $ruleSanitizers, [
			new ImportAtRuleSanitizer( $matcherFactory ),
			new NamespaceAtRuleSanitizer( $matcherFactory ),
		] );
		$san = new StylesheetSanitizer( $allRuleSanitizers );
		$this->assertSame( $allRuleSanitizers, $san->getRuleSanitizers() );
		return $san;
	}

	public function testNewDefault() {
		$this->assertInstanceOf( StylesheetSanitizer::class, StylesheetSanitizer::newDefault() );
	}

	public function testWrongType() {
		$san = $this->getSanitizer();
		$obj = new Token( Token::T_WHITESPACE, [ 'position' => [ 42, 23 ] ] );
		$this->assertNull( $san->sanitize( $obj ) );
		$this->assertSame(
			[ [ 'expected-stylesheet', 42, 23 ] ],
			$san->getSanitizationErrors()
		);
	}

	/**
	 * @dataProvider provideStylesheets
	 * @param string $input
	 * @param string|null $output
	 * @param array $errors
	 */
	public function testStylesheets( $input, $output, $errors = [] ) {
		$san = $this->getSanitizer();
		$sheet = Parser::newFromString( $input )->parseStylesheet();
		$ret = $san->sanitize( $sheet );
		$this->assertSame( $errors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );

			$sheet2 = Parser::newFromString( $output )->parseStylesheet();
			$ret2 = $san->sanitize( $sheet2 );
			$this->assertSame( $output, (string)$ret2 );
		}

		$san->clearSanitizationErrors();
		$list = Parser::newFromString( $input )->parseRuleList();
		$ret = $san->sanitize( $list );
		$this->assertSame( $errors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );
		}
	}

	public static function provideStylesheets() {
		return [
			'general test' => [
				'@import "foo";
				@import url( "bar" );
				@namespace "foo";
				@namespace "bar";
				@import "baz";

				.foo {
					display: none;
					color: bogus;
				}

				@namespace "bar";

				.baz % .baz {
					display: none;
				}

				@foo;

				@media print {
					@page {
						size: portrait;
					}

					.noprint { display: none; }
				}',
				// phpcs:ignore Generic.Files.LineLength
				'@import "foo"; @import url( "bar" ); @namespace "foo"; @namespace "bar"; .foo { display: none; } @media print { @page { size: portrait; } .noprint { display: none; } }',
				[
					[ 'misordered-rule', 5, 5 ],
					[ 'bad-value-for-property', 9, 13, 'color' ],
					[ 'misordered-rule', 12, 5 ],
					[ 'invalid-selector-list', 14, 5 ],
					[ 'unrecognized-rule', 18, 5 ],
				],
			],
			'media/supports interaction' => [
				'@media screen {
					@foo;
					@supports ( color : red ) {
						@foo;
						@media screen {
							@foo;
							@supports (color:red) {
								@foo;
								@bar;
							}
							@bar;
						}
						@bar;
					}
					@bar;
				}',
				'@media screen { @supports ( color : red ) { @media screen { @supports (color:red) {} } } }',
				[
					[ 'unrecognized-rule', 2, 6 ],
					[ 'unrecognized-rule', 6, 8 ],
					[ 'unrecognized-rule', 4, 7 ],
					[ 'unrecognized-rule', 8, 9 ],
					[ 'unrecognized-rule', 9, 9 ],
					[ 'unrecognized-rule', 11, 8 ],
					[ 'unrecognized-rule', 13, 7 ],
					[ 'unrecognized-rule', 15, 6 ],
				],
			],
		];
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;

/**
 * @covers \Wikimedia\CSS\Sanitizer\StyleAttributeSanitizer
 */
class StyleAttributeSanitizerTest extends TestCase {

	protected function getSanitizer() {
		return new StyleAttributeSanitizer(
			new StylePropertySanitizer( MatcherFactory::singleton() )
		);
	}

	public function testNewDefault() {
		$this->assertInstanceOf( StyleAttributeSanitizer::class, StyleAttributeSanitizer::newDefault() );
	}

	public function testWrongType() {
		$san = $this->getSanitizer();
		$obj = new Token( Token::T_WHITESPACE, [ 'position' => [ 42, 23 ] ] );
		$this->assertNull( $san->sanitize( $obj ) );
		$this->assertSame(
			[ [ 'expected-declaration-list', 42, 23 ] ],
			$san->getSanitizationErrors()
		);
	}

	/**
	 * @dataProvider provideAttributes
	 * @param string $input
	 * @param string|null $output
	 * @param array $errors
	 * @param array $parseErrors
	 */
	public function testAttributes( $input, $output, $errors = [], $parseErrors = [] ) {
		$san = $this->getSanitizer();
		$list = Parser::newFromString( $input )->parseDeclarationList();
		$ret = $san->sanitize( $list );
		$this->assertSame( $errors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );
		}

		$san->clearSanitizationErrors();
		$ret = $san->sanitizeString( $input );
		$this->assertSame( $parseErrors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );
		}
	}

	public static function provideAttributes() {
		return [
			'ok' => [
				'display: block; border:1px solid red',
				'display:block; border:1px solid red;',
			],
			'invalid declarations in list' => [
				'display:none;ident; foo: bar; all: inherit !important',
				'display:none; all:inherit !important;',
				[ [ 'unrecognized-property', 1, 21 ] ],
				[ [ 'expected-colon', 1, 19 ], [ 'unrecognized-property', 1, 21 ] ],
			],
		];
	}
}

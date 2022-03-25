<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\Declaration
 * @covers \Wikimedia\CSS\Objects\ComponentValue
 */
class DeclarationTest extends TestCase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Declaration must begin with an ident token, got at-keyword' );
		// @phan-suppress-next-line PhanNoopNew
		new Declaration( new Token( Token::T_AT_KEYWORD, 'value' ) );
	}

	public function testClone() {
		$identToken = new Token( Token::T_IDENT, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$ws = new Token( Token::T_WHITESPACE );
		$declaration = new Declaration( $identToken );
		$declaration->getValue()->add( $ws );

		$declaration2 = clone $declaration;
		$this->assertNotSame( $declaration, $declaration2 );
		$this->assertNotSame( $declaration->getValue(), $declaration2->getValue() );
		$this->assertEquals( $declaration, $declaration2 );
	}

	public function testBasics() {
		$identToken = new Token( Token::T_IDENT, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = $ws->copyWithSignificance( false );
		$colonToken = new Token( Token::T_COLON );
		$bangToken = new Token( Token::T_DELIM, '!' );
		$importantToken = new Token( Token::T_IDENT, 'important' );
		$funcToken = new Token( Token::T_FUNCTION, 'foo' );
		$rp = new Token( Token::T_RIGHT_PAREN );
		$func = new CSSFunction( $funcToken );

		$declaration = new Declaration( $identToken );
		$this->assertSame( [ 123, 42 ], $declaration->getPosition() );
		$this->assertSame( 'foobar', $declaration->getName() );
		$this->assertFalse( $declaration->getImportant() );
		$this->assertInstanceOf( ComponentValueList::class, $declaration->getValue() );
		$this->assertCount( 0, $declaration->getValue() );

		$declaration->getValue()->add( [ $func, $ws ] );

		$this->assertEquals(
			[ $identToken, $colonToken, $funcToken, $rp, $ws ],
			$declaration->toTokenArray()
		);
		$this->assertEquals(
			[ $identToken, $colonToken, $func, $ws ],
			$declaration->toComponentValueArray()
		);
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );

		$declaration->setImportant( true );
		$this->assertTrue( $declaration->getImportant() );

		$this->assertEquals(
			[ $identToken, $colonToken, $funcToken, $rp, $ws, $bangToken, $importantToken ],
			$declaration->toTokenArray()
		);
		$this->assertEquals(
			[ $identToken, $colonToken, $func, $ws, $bangToken, $importantToken ],
			$declaration->toComponentValueArray()
		);
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );

		$declaration->getValue()->remove( $declaration->getValue()->count() - 1 );
		$this->assertEquals(
			[ $identToken, $colonToken, $funcToken, $rp, $Iws, $bangToken, $importantToken ],
			$declaration->toTokenArray()
		);
		$this->assertEquals(
			[ $identToken, $colonToken, $func, $Iws, $bangToken, $importantToken ],
			$declaration->toComponentValueArray()
		);
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );
	}
}

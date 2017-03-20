<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\Declaration
 * @covers \Wikimedia\CSS\Objects\ComponentValue
 */
class DeclarationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Declaration must begin with an ident token, got at-keyword
	 */
	public function testException() {
		new Declaration( new Token( Token::T_AT_KEYWORD, 'value' ) );
	}

	public function testClone() {
		$identToken = new Token( Token::T_IDENT, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$ws = new Token( Token::T_WHITESPACE );
		$declaration = new Declaration( $identToken );
		$declaration->getValue()->add( $ws );

		$declaration2 = clone( $declaration );
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

		$declaration = new Declaration( $identToken );
		$this->assertSame( [ 123, 42 ], $declaration->getPosition() );
		$this->assertSame( 'foobar', $declaration->getName() );
		$this->assertSame( false, $declaration->getImportant() );
		$this->assertInstanceOf( ComponentValueList::class, $declaration->getValue() );
		$this->assertCount( 0, $declaration->getValue() );

		$declaration->getValue()->add( $ws );

		$this->assertEquals( [ $identToken, $colonToken, $ws ], $declaration->toTokenArray() );
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );

		$declaration->setImportant( true );
		$this->assertSame( true, $declaration->getImportant() );

		$this->assertEquals(
			[ $identToken, $colonToken, $ws, $bangToken, $importantToken ],
			$declaration->toTokenArray()
		);
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );

		$declaration->getValue()->remove( $declaration->getValue()->count() - 1 );
		$this->assertEquals(
			[ $identToken, $colonToken, $Iws, $bangToken, $importantToken ],
			$declaration->toTokenArray()
		);
		$this->assertSame( Util::stringify( $declaration ), (string)$declaration );
	}
}

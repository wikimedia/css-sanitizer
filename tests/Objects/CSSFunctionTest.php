<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\CSSFunction
 * @covers \Wikimedia\CSS\Objects\ComponentValue
 */
class CSSFunctionTest extends \PHPUnit\Framework\TestCase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'CSS function must begin with a function token, got ident' );
		// @phan-suppress-next-line PhanNoopNew
		new CSSFunction( new Token( Token::T_IDENT, 'value' ) );
	}

	public function testClone() {
		$funcToken = new Token( Token::T_FUNCTION, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$spaceToken = new Token( Token::T_WHITESPACE );
		$function = new CSSFunction( $funcToken );
		$function->getValue()->add( $spaceToken );

		$function2 = clone $function;
		$this->assertNotSame( $function, $function2 );
		$this->assertNotSame( $function->getValue(), $function2->getValue() );
		$this->assertEquals( $function, $function2 );
	}

	public function testBasics() {
		$funcToken = new Token( Token::T_FUNCTION, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$spaceToken = new Token( Token::T_WHITESPACE );
		$rightParenToken = new Token( Token::T_RIGHT_PAREN );

		$function = new CSSFunction( $funcToken );
		$this->assertSame( [ 123, 42 ], $function->getPosition() );
		$this->assertSame( 'foobar', $function->getName() );
		$this->assertInstanceOf( ComponentValueList::class, $function->getValue() );
		$this->assertCount( 0, $function->getValue() );

		$function->getValue()->add( $spaceToken );

		$this->assertEquals(
			[ $funcToken, $spaceToken, $rightParenToken ],
			$function->toTokenArray()
		);
		$this->assertSame( [ $function ], $function->toComponentValueArray() );
		$this->assertSame( Util::stringify( $function ), (string)$function );

		$function = CSSFunction::newFromName( 'qwerty' );
		$this->assertSame( 'qwerty', $function->getName() );
	}
}

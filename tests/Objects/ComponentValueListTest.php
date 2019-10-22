<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/**
 * @covers \Wikimedia\CSS\Objects\ComponentValueList
 */
class ComponentValueListTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideBadTokens
	 * @param Token $token
	 */
	public function testConstructorBadTokens( $token ) {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage(
			ComponentValueList::class . " may not contain tokens of type \"{$token->type()}\"."
		);
		new ComponentValueList( [ $token ] );
	}

	/**
	 * @dataProvider provideBadTokens
	 * @param Token $token
	 */
	public function testAddBadToken( $token ) {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage(
			ComponentValueList::class . " may not contain tokens of type \"{$token->type()}\"."
		);
		$list = new ComponentValueList();
		$list->add( $token );
	}

	/**
	 * @dataProvider provideBadTokens
	 * @param Token $token
	 */
	public function testSetBadToken( $token ) {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage(
			ComponentValueList::class . " may not contain tokens of type \"{$token->type()}\"."
		);
		$list = new ComponentValueList();
		$list[0] = $token;
	}

	public static function provideBadTokens() {
		return [
			[ new Token( Token::T_LEFT_PAREN ), '' ],
			[ new Token( Token::T_LEFT_BRACE ), 'X' ],
			[ new Token( Token::T_LEFT_BRACKET ), 'X' ],
			[ new Token( Token::T_FUNCTION, 'foo' ), 'X' ],
		];
	}

	public function testToComponentValueArray() {
		$token = new Token( Token::T_IDENT, 'a' );
		$func = CSSFunction::newFromName( 'b' );
		$block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACKET );

		$list = new ComponentValueList( [ $token, $func, $block ] );
		$this->assertSame( [ $token, $func, $block ], $list->toComponentValueArray() );
	}

}

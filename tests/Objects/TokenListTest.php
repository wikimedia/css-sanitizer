<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Exception\ParseException;

/**
 * @covers \Wikimedia\CSS\Objects\TokenList
 */
class TokenListTest extends TestCase {

	public function testToTokenArray() {
		$token1 = new Token( Token::T_IDENT, 'a' );
		$token2 = new Token( Token::T_IDENT, 'b' );
		$token3 = new Token( Token::T_IDENT, 'c' );

		$list = new TokenList( [ $token1, $token2, $token3 ] );
		$this->assertSame( [ $token1, $token2, $token3 ], $list->toTokenArray() );
	}

	public function testToComponentValueArray() {
		$token1 = new Token( Token::T_IDENT, 'a' );
		$lparen = new Token( Token::T_LEFT_PAREN );
		$rparen = new Token( Token::T_RIGHT_PAREN );
		$lbrace = new Token( Token::T_LEFT_BRACE );
		$rbrace = new Token( Token::T_RIGHT_BRACE );
		$lbracket = new Token( Token::T_LEFT_BRACKET );
		$rbracket = new Token( Token::T_RIGHT_BRACKET );
		$func = new Token( Token::T_FUNCTION, 'foo' );

		$list = new TokenList( [
			$token1, $lparen, $rparen, $lbrace, $rbrace, $lbracket, $rbracket, $func, $rparen
		] );
		$this->assertEquals(
			[
				$token1, new SimpleBlock( $lparen ), new SimpleBlock( $lbrace ),
				new SimpleBlock( $lbracket ), new CSSFunction( $func )
			],
			$list->toComponentValueArray()
		);

		foreach (
			[
				[ $lparen, [ [ 'unexpected-eof-in-block', -1, -1 ] ] ],
				[ $lbrace, [ [ 'unexpected-eof-in-block', -1, -1 ] ] ],
				[ $lbracket, [ [ 'unexpected-eof-in-block', -1, -1 ] ] ],
				[ $func, [ [ 'unexpected-eof-in-function', -1, -1 ] ] ],
			] as [ $token, $errors ]
		) {
			$list = new TokenList( [ $token ] );
			try {
				$list->toComponentValueArray();
				$this->fail( "Expected exception not thrown for token type {$token->type()}" );
			} catch ( ParseException $ex ) {
				$this->assertSame( 'TokenList cannot be converted to a ComponentValueList', $ex->getMessage() );
				$this->assertEquals( $errors, $ex->parseErrors );
			}
		}
	}

}

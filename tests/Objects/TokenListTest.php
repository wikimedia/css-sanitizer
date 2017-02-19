<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/**
 * @covers \Wikimedia\CSS\Objects\TokenList
 */
class TokenListTest extends \PHPUnit_Framework_TestCase {

	public function testToTokenArray() {
		$token1 = new Token( Token::T_IDENT, 'a' );
		$token2 = new Token( Token::T_IDENT, 'b' );
		$token3 = new Token( Token::T_IDENT, 'c' );

		$list = new TokenList( [ $token1, $token2, $token3 ] );
		$this->assertSame( [ $token1, $token2, $token3 ], $list->toTokenArray() );
	}

}

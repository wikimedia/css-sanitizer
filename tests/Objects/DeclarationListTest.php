<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/**
 * @covers \Wikimedia\CSS\Objects\DeclarationList
 */
class DeclarationListTest extends \PHPUnit_Framework_TestCase {

	public function testToTokenArray() {
		$token1 = new Token( Token::T_IDENT, 'a' );
		$token2 = new Token( Token::T_IDENT, 'b' );
		$token3 = new Token( Token::T_IDENT, 'c' );
		$colon = new Token( Token::T_COLON );
		$semicolon = new Token( Token::T_SEMICOLON );
		$Isemicolon = $semicolon->copyWithSignificance( false );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );

		$dec1 = new Declaration( $token1 );
		$dec1->getValue()->add( $ws );

		$list = new DeclarationList( [
			$dec1,
			new Declaration( $token2 ),
			new Declaration( $token3 )
		] );
		$this->assertEquals( [
			$token1, $colon, $ws, $semicolon, $Iws,
			$token2, $colon, $semicolon, $Iws,
			$token3, $colon, $Isemicolon,
		], $list->toTokenArray() );
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/**
 * @covers \Wikimedia\CSS\Objects\DeclarationOrAtRuleList
 */
class DeclarationOrAtRuleListTest extends \PHPUnit\Framework\TestCase {

	public function testToTokenArray() {
		$token1 = new Token( Token::T_IDENT, 'a' );
		$token2 = new Token( Token::T_IDENT, 'c' );
		$token3 = new Token( Token::T_AT_KEYWORD, 'b' );
		$colon = new Token( Token::T_COLON );
		$semicolon = new Token( Token::T_SEMICOLON );
		$Isemicolon = $semicolon->copyWithSignificance( false );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );

		$dec1 = new Declaration( $token1 );
		$dec1->getValue()->add( $ws );

		$list = new DeclarationOrAtRuleList( [
			$dec1,
			new Declaration( $token2 ),
			new AtRule( $token3 ),
		] );
		$this->assertEquals( [
			$token1, $colon, $ws, $semicolon, $Iws,
			$token2, $colon, $semicolon, $Iws,
			$token3, $semicolon,
		], $list->toTokenArray() );

		$list = new DeclarationOrAtRuleList( [
			$dec1,
			new AtRule( $token3 ),
			new Declaration( $token2 ),
		] );
		$this->assertEquals( [
			$token1, $colon, $ws, $semicolon, $Iws,
			$token3, $semicolon, $Iws,
			$token2, $colon, $Isemicolon,
		], $list->toTokenArray() );
	}
}

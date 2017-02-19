<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/**
 * @covers \Wikimedia\CSS\Objects\RuleList
 */
class RuleListTest extends \PHPUnit_Framework_TestCase {

	public function testToTokenArray() {
		$tok1 = new Token( Token::T_AT_KEYWORD, 'a' );
		$tok2 = new Token( Token::T_AT_KEYWORD, 'b' );
		$tok3 = new Token( Token::T_AT_KEYWORD, 'c' );
		$semicolon = new Token( Token::T_SEMICOLON );
		$ws = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );

		$list = new RuleList( [
			AtRule::newFromName( 'a' ),
			AtRule::newFromName( 'b' ),
			AtRule::newFromName( 'c' ),
		] );
		$this->assertEquals( [
			$tok1, $semicolon, $ws,
			$tok2, $semicolon, $ws,
			$tok3, $semicolon,
		], $list->toTokenArray() );
	}
}

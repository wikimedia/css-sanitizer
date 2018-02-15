<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\AtRule
 * @covers \Wikimedia\CSS\Objects\Rule
 */
class AtRuleTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage At rule must begin with an at-keyword token, got ident
	 */
	public function testException() {
		new AtRule( new Token( Token::T_IDENT, 'value' ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage At-rule block must be delimited by {}
	 */
	public function testBadBlock() {
		$rule = new AtRule( new Token( Token::T_AT_KEYWORD, 'value' ) );
		$rule->setBlock( SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACKET ) );
	}

	public function testClone() {
		$atToken = new Token( Token::T_AT_KEYWORD, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$ws = new Token( Token::T_WHITESPACE );
		$leftBraceToken = new Token( Token::T_LEFT_BRACE );
		$rule = new AtRule( $atToken );
		$rule->getPrelude()->add( $ws );
		$rule->setBlock( new SimpleBlock( $leftBraceToken ) );

		$rule2 = clone $rule;
		$this->assertNotSame( $rule, $rule2 );
		$this->assertNotSame( $rule->getPrelude(), $rule2->getPrelude() );
		$this->assertNotSame( $rule->getBlock(), $rule2->getBlock() );
		$this->assertEquals( $rule, $rule2 );

		$rule = new AtRule( $atToken );
		$rule2 = clone $rule;
		$this->assertNull( $rule2->getBlock() );
	}

	public function testBasics() {
		$atToken = new Token( Token::T_AT_KEYWORD, [ 'value' => 'foobar', 'position' => [ 123, 42 ] ] );
		$colon = new Token( Token::T_COLON );
		$ident = new Token( Token::T_IDENT, 'bar' );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );
		$leftBraceToken = new Token( Token::T_LEFT_BRACE );
		$rightBraceToken = new Token( Token::T_RIGHT_BRACE );
		$leftBracketToken = new Token( Token::T_LEFT_BRACKET );
		$rightBracketToken = new Token( Token::T_RIGHT_BRACKET );

		$rule = new AtRule( $atToken );
		$this->assertSame( [ 123, 42 ], $rule->getPosition() );
		$this->assertSame( 'foobar', $rule->getName() );
		$this->assertInstanceOf( ComponentValueList::class, $rule->getPrelude() );
		$this->assertCount( 0, $rule->getPrelude() );
		$this->assertNull( $rule->getBlock() );

		$rule->getPrelude()->add( $ws );
		$rule->getPrelude()->add( $ws );

		$this->assertEquals(
			[ $atToken, $ws, $ws, new Token( Token::T_SEMICOLON ) ],
			$rule->toTokenArray()
		);
		$this->assertEquals(
			[ $atToken, $ws, $ws, new Token( Token::T_SEMICOLON ) ],
			$rule->toComponentValueArray()
		);
		$this->assertSame( Util::stringify( $rule ), (string)$rule );

		$block = new SimpleBlock( $leftBraceToken );
		$block->getValue()->add( $ws );
		$rule->setBlock( $block );
		$this->assertSame( $block, $rule->getBlock() );

		$this->assertEquals(
			[ $atToken, $ws, $ws, $leftBraceToken, $ws, $rightBraceToken ],
			$rule->toTokenArray()
		);
		$this->assertEquals(
			[ $atToken, $ws, $ws, $block ],
			$rule->toComponentValueArray()
		);
		$this->assertSame( Util::stringify( $rule ), (string)$rule );

		$rule->getPrelude()->clear();
		$rule->getPrelude()->add( $colon );
		$this->assertEquals(
			[ $atToken, $colon, $leftBraceToken, $ws, $rightBraceToken ],
			$rule->toTokenArray()
		);
		$this->assertEquals(
			[ $atToken, $colon, $block ],
			$rule->toComponentValueArray()
		);

		$rule->getPrelude()->clear();
		$rule->getPrelude()->add( $ident );
		$this->assertEquals(
			[ $atToken, $ident, $leftBraceToken, $ws, $rightBraceToken ],
			$rule->toTokenArray()
		);
		$this->assertEquals(
			[ $atToken, $ident, $block ],
			$rule->toComponentValueArray()
		);

		$block2 = new SimpleBlock( $leftBracketToken );
		$rule->getPrelude()->clear();
		$rule->getPrelude()->add( $block2 );
		$this->assertEquals(
			[ $atToken, $leftBracketToken, $rightBracketToken, $leftBraceToken, $ws, $rightBraceToken ],
			$rule->toTokenArray()
		);
		$this->assertEquals(
			[ $atToken, $block2, $block ],
			$rule->toComponentValueArray()
		);

		$rule = AtRule::newFromName( 'qwerty' );
		$this->assertSame( 'qwerty', $rule->getName() );
	}
}

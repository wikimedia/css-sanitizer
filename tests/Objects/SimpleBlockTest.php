<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\SimpleBlock
 */
class SimpleBlockTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage A SimpleBlock is delimited by either {}, [], or ().
	 */
	public function testException() {
		new SimpleBlock( new Token( Token::T_IDENT, 'value' ) );
	}

	public function testClone() {
		$ws = new Token( Token::T_WHITESPACE );
		$block = new SimpleBlock( new Token( Token::T_LEFT_BRACE ) );
		$block->getValue()->add( $ws );

		$block2 = clone $block;
		$this->assertNotSame( $block, $block2 );
		$this->assertNotSame( $block->getValue(), $block2->getValue() );
		$this->assertEquals( $block, $block2 );
	}

	public function testBasics() {
		foreach ( [
				Token::T_LEFT_BRACE => Token::T_RIGHT_BRACE,
				Token::T_LEFT_BRACKET => Token::T_RIGHT_BRACKET,
				Token::T_LEFT_PAREN => Token::T_RIGHT_PAREN,
			] as $start => $end
		) {
			$block = new SimpleBlock( new Token( $start ) );
			$this->assertSame( $start, $block->getStartTokenType() );
			$this->assertSame( $end, $block->getEndTokenType() );
			$this->assertInstanceOf( ComponentValueList::class, $block->getValue() );
			$this->assertCount( 0, $block->getValue() );

			$this->assertEquals(
				[ new Token( $start ), new Token( $end ) ],
				$block->toTokenArray()
			);
			$this->assertSame( [ $block ], $block->toComponentValueArray() );
			$this->assertSame( Util::stringify( $block ), (string)$block );

			$block = SimpleBlock::newFromDelimiter( $start );
			$this->assertSame( $start, $block->getStartTokenType() );
			$this->assertSame( $end, $block->getEndTokenType() );
		}

		$colon = new Token( Token::T_COLON );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );

		// Braces insert insignificant whitespace
		$block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
		$block->getValue()->add( $colon );
		$this->assertEquals(
			[ new Token( Token::T_LEFT_BRACE ), $Iws, $colon, $Iws, new Token( Token::T_RIGHT_BRACE ) ],
			$block->toTokenArray()
		);
		$this->assertSame( [ $block ], $block->toComponentValueArray() );
		$this->assertSame( Util::stringify( $block ), (string)$block );

		$block->getValue()->add( $ws, 0 );
		$block->getValue()->add( $ws );
		$this->assertEquals(
			[ new Token( Token::T_LEFT_BRACE ), $ws, $colon, $ws, new Token( Token::T_RIGHT_BRACE ) ],
			$block->toTokenArray()
		);
		$this->assertSame( [ $block ], $block->toComponentValueArray() );
		$this->assertSame( Util::stringify( $block ), (string)$block );

		// Brackets (and parens) don't
		$block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACKET );
		$block->getValue()->add( $colon );
		$this->assertEquals(
			[ new Token( Token::T_LEFT_BRACKET ), $colon, new Token( Token::T_RIGHT_BRACKET ) ],
			$block->toTokenArray()
		);
		$this->assertSame( [ $block ], $block->toComponentValueArray() );
		$this->assertSame( Util::stringify( $block ), (string)$block );
	}
}

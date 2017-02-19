<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Grammar\BlockMatcher
 */
class BlockMatcherTest extends MatcherTestBase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage A block is delimited by either {}, [], or ().
	 */
	public function testException() {
		new BlockMatcher( Token::T_RIGHT_BRACE, new TokenMatcher( Token::T_COMMA ) );
	}

	public function testStandard() {
		$matcher = new BlockMatcher( Token::T_LEFT_BRACE, new TokenMatcher( Token::T_COMMA ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$b1 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
		$b1->getValue()->add( $c );
		$b2 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACKET );
		$b2->getValue()->add( $c );
		$b3 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );

		$list = new ComponentValueList( [ $ws, $b1, $b1, $ws, $ws, $b2, $b3, $c, $b1, $ws ] );
		$expect = [ false, 2, 5, false, false, false, false, false, 10, false, false ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $v ] : [], $generateMatches( $list, $i, $options ),
				"Skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $i + 1 ] : [], $generateMatches( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}
	}

	public function testCaptures() {
		$matcher = new BlockMatcher( Token::T_LEFT_BRACE,
			TokenMatcher::create( Token::T_COMMA )->capture( 'foo' ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$b1 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
		$b1->getValue()->add( [ $ws, $c, $ws ] );
		$lb = new Token( Token::T_LEFT_BRACE );
		$rb = new Token( Token::T_RIGHT_BRACE );

		$list = new ComponentValueList( [ $b1 ] );
		$ret = iterator_to_array( $generateMatches( $list, 0, [ 'skip-whitespace' => true ] ) );
		$this->assertEquals( [
			new Match( $list, 0, 1, null, [ new Match( $b1->getValue(), 1, 2, 'foo' ) ] ),
		], $ret );
	}
}

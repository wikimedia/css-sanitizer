<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use InvalidArgumentException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\BlockMatcher
 */
class BlockMatcherTest extends MatcherTestBase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'A block is delimited by either {}, [], or ().' );
		// @phan-suppress-next-line PhanNoopNew
		new BlockMatcher( Token::T_RIGHT_BRACE, new TokenMatcher( Token::T_COMMA ) );
	}

	public function testStandard() {
		$m = TestingAccessWrapper::newFromObject(
			new BlockMatcher( Token::T_LEFT_BRACE, new TokenMatcher( Token::T_COMMA ) )
		);

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
			$this->assertPositions( $i, $v ? [ $v ] : [], $m->generateMatches( $list, $i, $options ),
				"Skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $i + 1 ] : [], $m->generateMatches( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}
	}

	public function testCaptures() {
		$matcher = TestingAccessWrapper::newFromObject( new BlockMatcher( Token::T_LEFT_BRACE,
			TokenMatcher::create( Token::T_COMMA )->capture( 'foo' ) ) );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$b1 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
		$b1->getValue()->add( [ $ws, $c, $ws ] );
		$lb = new Token( Token::T_LEFT_BRACE );
		$rb = new Token( Token::T_RIGHT_BRACE );

		$list = new ComponentValueList( [ $b1 ] );
		$ret = iterator_to_array( $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] ) );
		$this->assertEquals( [
			new GrammarMatch( $list, 0, 1, null, [ new GrammarMatch( $b1->getValue(), 1, 2, 'foo' ) ] ),
		], $ret );
	}
}

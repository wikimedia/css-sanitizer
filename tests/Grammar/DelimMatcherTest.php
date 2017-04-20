<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\DelimMatcher
 */
class DelimMatcherTest extends MatcherTestBase {

	public function testEverything() {
		$m = TestingAccessWrapper::newFromObject( new DelimMatcher( [ '+', 'x', 'x' ] ) );

		$ws = new Token( Token::T_WHITESPACE );
		$cv1 = new Token( Token::T_DELIM, '+' );
		$cv2 = new Token( Token::T_DELIM, 'x' );
		$cv3 = new Token( Token::T_DELIM, 'X' );
		$cv4 = new Token( Token::T_IDENT, '+' );
		$block = SimpleBlock::newFromDelimiter( '{' );

		$list = new ComponentValueList( [ $ws, $cv1, $cv2, $ws, $ws, $cv3, $block, $cv4, $cv2, $ws ] );
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
}

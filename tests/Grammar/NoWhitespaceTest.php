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
 * @covers \Wikimedia\CSS\Grammar\NoWhitespace
 */
class NoWhitespaceTest extends MatcherTestBase {

	public function testStandard() {
		$matcher = TestingAccessWrapper::newFromObject( new NoWhitespace );

		$ws = new Token( Token::T_WHITESPACE );
		$v1 = new Token( Token::T_IDENT, 'foo' );
		$v2 = SimpleBlock::newFromDelimiter( '{' );
		$list = new ComponentValueList( [ $ws, $v1, $ws, $ws, $v2, $v1, $v2, $ws, $v1, $ws ] );

		$expect = [ 0, false, 2, false, false, 5, 6, 7, false, 9, false, 11 ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertEquals(
				$v === false ? [] : [ new Match( $list, $i, 0 ) ],
				iterator_to_array( $matcher->generateMatches( $list, $i, $options ) ),
				"Skipping whitespace, index $i"
			);
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertEquals(
				$v === false ? [] : [ new Match( $list, $i, 0 ) ],
				iterator_to_array( $matcher->generateMatches( $list, $i, $options ) ),
				"Not skipping whitespace, index $i"
			);
		}
	}
}

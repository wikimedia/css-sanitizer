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
 * @covers \Wikimedia\CSS\Grammar\WhitespaceMatcher
 */
class WhitespaceMatcherTest extends MatcherTestBase {

	public function testStandard() {
		$m1 = TestingAccessWrapper::newFromObject( new WhitespaceMatcher( [ 'significant' => false ] ) );
		$m2 = TestingAccessWrapper::newFromObject( new WhitespaceMatcher( [ 'significant' => true ] ) );

		$ws = new Token( Token::T_WHITESPACE );
		$v1 = new Token( Token::T_IDENT, 'foo' );
		$v2 = SimpleBlock::newFromDelimiter( '{' );
		$list = new ComponentValueList( [ $v1, $ws, $ws, $ws, $v2, $v1, $v2, $ws, $v1, $ws ] );

		$expect = [ 0, 4, 4, 4, 4, 5, 6, 8, 8, 10, 10 ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertEquals(
				[ new Match( $list, $i, $v - $i ) ],
				iterator_to_array( $m1->generateMatches( $list, $i, $options ) ),
				"Insignificant, skipping whitespace, index $i"
			);
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertEquals(
				[ new Match( $list, $i, $v - $i ) ],
				iterator_to_array( $m1->generateMatches( $list, $i, $options ) ),
				"Insignificant, not skipping whitespace, index $i"
			);
		}

		$expect = [ false, 4, 4, 4, 4, false, false, 8, 8, 10, 10, false ];
		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			if ( $v === false ) {
				$ex = [];
			} elseif ( $i === $v ) {
				$ex = [
					new Match( $list, $i - 1, 1, null, [ new Match( $list, $i - 1, 1, 'significantWhitespace' ) ] )
				];
			} else {
				$ex = [
					new Match( $list, $i, $v - $i, null, [ new Match( $list, $i, 1, 'significantWhitespace' ) ] )
				];
			}
			$this->assertEquals(
				$ex,
				iterator_to_array( $m2->generateMatches( $list, $i, $options ) ),
				"Significant, skipping whitespace, index $i"
			);
		}

		$expect = [ false, 4, 4, 4, false, false, false, 8, false, 10, false, false ];
		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			if ( $v === false ) {
				$ex = [];
			} else {
				$ex = [
					new Match( $list, $i, $v - $i, null, [ new Match( $list, $i, 1, 'significantWhitespace' ) ] )
				];
			}
			$this->assertEquals(
				$ex,
				iterator_to_array( $m2->generateMatches( $list, $i, $options ) ),
				"Significant, not skipping whitespace, index $i"
			);
		}
	}
}

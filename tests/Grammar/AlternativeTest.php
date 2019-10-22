<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use InvalidArgumentException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\Alternative
 */
class AlternativeTest extends MatcherTestBase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'$matchers may only contain instances of Wikimedia\CSS\Grammar\Matcher '
			. '(found Wikimedia\CSS\Objects\ComponentValueList at index 0)'
		);
		new Alternative( [ new ComponentValueList ] );
	}

	/**
	 * @dataProvider provideGenerateMatches
	 * @param array $rets
	 * @param array $expect
	 */
	public function testGenerateMatches( $rets, $expect ) {
		$matchers = [];
		foreach ( $rets as $ret ) {
			$matcher = $this->getMockBuilder( Matcher::class )
				->setMethods( [ 'generateMatches' ] )
				->getMockForAbstractClass();
			$matcher->expects( $this->once() )->method( 'generateMatches' )
				->willReturnCallback( function ( $values, $i, $options ) use ( $ret ) {
					foreach ( $ret as $v ) {
						yield new Match( $values, $i, $v - $i );
					}
				} );
			$matchers[] = $matcher;
		}

		$list = new ComponentValueList();
		$alternative = TestingAccessWrapper::newFromObject( new Alternative( $matchers ) );
		$this->assertPositions( 0, $expect, $alternative->generateMatches( $list, 0, [] ) );
	}

	public static function provideGenerateMatches() {
		return [
			'No matches' => [
				[ [], [], [] ], []
			],
			'One match' => [
				[ [ 1 ], [], [] ], [ 1 ]
			],
			'Multiple matches from one alternative' => [
				[ [], [ 1, 2, 3 ], [] ], [ 1, 2, 3 ]
			],
			'Matches from multiple alternatives, with deduplication' => [
				[ [ 1 ], [ 2 ], [ 1, 2, 3, 4 ] ], [ 1, 2, 3, 4 ]
			],
			'No alternatives' => [
				[], []
			],
		];
	}

	public function testCaptures() {
		$matcher = new TokenMatcher( Token::T_COLON );
		$m1 = $matcher->capture( 'foo' );
		$m2 = $matcher->capture( 'bar' );
		$m3 = $matcher->capture( 'baz' );
		$m4 = $matcher->capture( 'foo' );

		$list = new ComponentValueList( [ new Token( Token::T_COLON ) ] );
		$alternative = TestingAccessWrapper::newFromObject( new Alternative( [ $m1, $m2, $m3, $m4 ] ) );
		$ret = iterator_to_array(
			$alternative->generateMatches( $list, 0, [ 'skip-whitespace' => true ] )
		);
		$this->assertEquals( [
			new Match( $list, 0, 1, null, [ new Match( $list, 0, 1, 'foo' ) ] ),
			new Match( $list, 0, 1, null, [ new Match( $list, 0, 1, 'bar' ) ] ),
			new Match( $list, 0, 1, null, [ new Match( $list, 0, 1, 'baz' ) ] ),
		], $ret );
	}
}

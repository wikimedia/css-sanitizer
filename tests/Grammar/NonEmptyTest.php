<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Grammar\NonEmpty
 */
class NonEmptyTest extends MatcherTestBase {

	/**
	 * @dataProvider provideGenerateMatches
	 * @param int $start
	 * @param array $ret
	 * @param array $expect
	 */
	public function testGenerateMatches( $start, $ret, $expect ) {
		$matcher = $this->getMockBuilder( Matcher::class )
			->setMethods( [ 'generateMatches' ] )
			->getMockForAbstractClass();
		$matcher->expects( $this->once() )->method( 'generateMatches' )
			->willReturnCallback( function ( $values, $i, $options ) use ( $start, $ret ) {
				$this->assertSame( $i, $start );
				foreach ( $ret as $v ) {
					yield new Match( $values, $i, $v - $i );
				}
			} );

		$list = new ComponentValueList( [] );
		$nonempty = new NonEmpty( $matcher );
		$generateMatches = $this->getGenerateMatches( $nonempty );
		$this->assertPositions( $start, $expect, $generateMatches( $list, $start, [] ) );
	}

	public static function provideGenerateMatches() {
		return [
			[ 0, [], [] ],
			[ 0, [ 0 ], [] ],
			[ 4, [ 6, 4, 2 ], [ 6, 2 ] ],
		];
	}

	public function testCaptures() {
		$m = Quantifier::optional( new TokenMatcher( Token::T_COLON ) );
		$matcher = new NonEmpty( $m->capture( 'foo' ) );

		$list = new ComponentValueList( [ new Token( Token::T_COLON ) ] );

		$generateMatches = $this->getGenerateMatches( $matcher );
		$ret = $generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertEquals( [
			new Match( $list, 0, 1, null, [ new Match( $list, 0, 1, 'foo' ) ] ),
		], iterator_to_array( $ret ) );
	}
}

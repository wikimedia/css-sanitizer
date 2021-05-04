<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

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
			->onlyMethods( [ 'generateMatches' ] )
			->getMockForAbstractClass();
		$matcher->expects( $this->once() )->method( 'generateMatches' )
			->willReturnCallback( function ( $values, $i, $options ) use ( $start, $ret ) {
				$this->assertSame( $i, $start );
				foreach ( $ret as $v ) {
					yield new GrammarMatch( $values, $i, $v - $i );
				}
			} );
		'@phan-var Matcher $matcher';

		$list = new ComponentValueList( [] );
		$nonempty = TestingAccessWrapper::newFromObject( new NonEmpty( $matcher ) );
		$this->assertPositions( $start, $expect, $nonempty->generateMatches( $list, $start, [] ) );
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
		$matcher = TestingAccessWrapper::newFromObject( new NonEmpty( $m->capture( 'foo' ) ) );

		$list = new ComponentValueList( [ new Token( Token::T_COLON ) ] );

		$ret = $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertEquals( [
			new GrammarMatch( $list, 0, 1, null, [ new GrammarMatch( $list, 0, 1, 'foo' ) ] ),
		], iterator_to_array( $ret ) );
	}
}

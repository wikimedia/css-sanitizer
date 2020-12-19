<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\CheckedMatcher
 */
class CheckedMatcherTest extends MatcherTestBase {

	public function testGenerateMatches() {
		$matcher = $this->getMockBuilder( Matcher::class )
			->setMethods( [ 'generateMatches' ] )
			->getMockForAbstractClass();
		$matcher->expects( $this->once() )->method( 'generateMatches' )
			->willReturnCallback( function ( $values, $i, $options ) {
				for ( $i = 0; $i < 10; $i++ ) {
					yield new GrammarMatch( $values, 0, $i );
				}
			} );
		'@phan-var Matcher $matcher';

		$list = new ComponentValueList( [] );
		$checked = TestingAccessWrapper::newFromObject(
			new CheckedMatcher( $matcher, function ( $l, $m, $opts ) use ( $list ) {
				$this->assertSame( $list, $l );
				return ( $m->getLength() % 2 ) === 0;
			} )
		);
		$this->assertPositions( 0, [ 0, 2, 4, 6, 8 ], $checked->generateMatches( $list, 0, [] ) );
	}
}

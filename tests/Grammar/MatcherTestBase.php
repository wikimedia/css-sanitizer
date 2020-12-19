<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Iterator;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\TestingAccessWrapper;

class MatcherTestBase extends \PHPUnit\Framework\TestCase {

	/**
	 * Strip the tokens from a GrammarMatch
	 * @param GrammarMatch|null $match
	 * @return GrammarMatch|null
	 */
	public static function stripMatch( $match ) {
		if ( $match === null ) {
			return null;
		}

		TestingAccessWrapper::newFromObject( $match )->values = '...';
		if ( $match->getCapturedMatches() ) {
			self::stripMatches( $match->getCapturedMatches() );
		}
		return $match;
	}

	/**
	 * Strip the tokens from the Matches
	 * @param GrammarMatch[]|Iterator $matches
	 * @return GrammarMatch[]
	 */
	public static function stripMatches( $matches ) {
		if ( $matches instanceof Iterator ) {
			$matches = iterator_to_array( $matches );
		}
		foreach ( $matches as $m ) {
			self::stripMatch( $m );
		}
		return $matches;
	}

	/**
	 * Assert that an Iterator returns the specified ending positions
	 * @param int $start Starting position
	 * @param int[] $expectPos Expected ending positions
	 * @param Iterator $iter
	 * @param string $text
	 */
	public function assertPositions( $start, $expectPos, $iter, string $text = '' ) {
		$list = new ComponentValueList();
		$expect = [];
		foreach ( $expectPos as $end ) {
			$expect[] = new GrammarMatch( $list, $start, $end - $start );
		}
		$expect = self::stripMatches( $expect );
		$actual = self::stripMatches( $iter );
		$this->assertEquals( $expect, $actual, $text );
	}

	/**
	 * Assert that two Matches match
	 * @param GrammarMatch $expected
	 * @param GrammarMatch $actual
	 * @param string $text
	 */
	public function assertMatch( $expected, $actual, string $text = '' ) {
		$this->assertEquals(
			self::stripMatch( $expected ),
			self::stripMatch( $actual ),
			$text
		);
	}
}

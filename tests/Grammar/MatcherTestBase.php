<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use \Iterator;
use Wikimedia\CSS\Objects\ComponentValueList;

class MatcherTestBase extends \PHPUnit_Framework_TestCase {

	/**
	 * Get a handle to call $matcher->generateMatches()
	 * @param Matcher $matcher
	 * @return callable
	 */
	protected function getGenerateMatches( Matcher $matcher ) {
		$rm = new \ReflectionMethod( $matcher, 'generateMatches' );
		$rm->setAccessible( true );
		return function ( $values, $start, $options ) use ( $rm, $matcher ) {
			return $rm->invoke( $matcher, $values, $start, $options );
		};
	}

	/**
	 * Strip the tokens from a Match
	 * @param Match|null $match
	 * @return Match|null
	 */
	public static function stripMatch( $match ) {
		static $rp = null;

		if ( $match === null ) {
			return null;
		}

		if ( !$rp ) {
			$rp = new \ReflectionProperty( Match::class, 'values' );
			$rp->setAccessible( true );
		}

		$rp->setValue( $match, '...' );
		if ( $match->getCapturedMatches() ) {
			self::stripMatches( $match->getCapturedMatches() );
		}
		return $match;
	}

	/**
	 * Strip the tokens from the Matches
	 * @param Match[]|Iterator $matches
	 * @return Match[]
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
	 * @param string|null $text
	 */
	public function assertPositions( $start, $expectPos, $iter, $text = null ) {
		$list = new ComponentValueList();
		$expect = [];
		foreach ( $expectPos as $end ) {
			$expect[] = new Match( $list, $start, $end - $start );
		}
		$expect = self::stripMatches( $expect );
		$actual = self::stripMatches( $iter );
		$this->assertEquals( $expect, $actual, $text );
	}

	/**
	 * Assert that two Matches match
	 * @param Match $expected
	 * @param Match $actual
	 * @param string|null $text
	 */
	public function assertMatch( $expected, $actual, $text = null ) {
		$this->assertEquals(
			self::stripMatch( $expected ),
			self::stripMatch( $actual ),
			$text
		);
	}
}

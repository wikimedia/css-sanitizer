<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;

class MatcherTestMock extends Matcher {
	public $args;

	/**
	 * @param mixed $a Arbitrary value stored in $this->args
	 * @param mixed $b Arbitrary value stored in $this->args
	 * @param mixed $c Arbitrary value stored in $this->args
	 * @param mixed $d Arbitrary value stored in $this->args
	 * @param mixed $e Arbitrary value stored in $this->args
	 */
	public function __construct( $a = null, $b = null, $c = null, $d = null, $e = null ) {
		$this->args = [ $a, $b, $c, $d, $e ];
	}

	/** @inheritDoc */
	protected function generateMatches( ComponentValueList $values, $start, array $options ) {
	}
}

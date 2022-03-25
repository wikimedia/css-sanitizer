<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use EmptyIterator;
use Wikimedia\CSS\Objects\ComponentValueList;

class MatcherTestMock extends Matcher {
	/** @var array */
	public $args;

	/**
	 * @param mixed|null $a Arbitrary value stored in $this->args
	 * @param mixed|null $b Arbitrary value stored in $this->args
	 * @param mixed|null $c Arbitrary value stored in $this->args
	 * @param mixed|null $d Arbitrary value stored in $this->args
	 * @param mixed|null $e Arbitrary value stored in $this->args
	 */
	public function __construct( $a = null, $b = null, $c = null, $d = null, $e = null ) {
		$this->args = [ $a, $b, $c, $d, $e ];
	}

	/** @inheritDoc */
	protected function generateMatches( ComponentValueList $values, $start, array $options ) {
		return new EmptyIterator();
	}
}

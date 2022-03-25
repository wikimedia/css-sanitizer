<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

class TestCSSObjectListItem implements CSSObject {
	/** @var string */
	private $id;

	/** @var int[] */
	public $position = [ -1, -1 ];

	/**
	 * @param string $id ID used for the test
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function __toString() {
		return "[$this->id]";
	}

	/** @inheritDoc */
	public function getPosition() {
		return $this->position;
	}

	/** @inheritDoc */
	public function toTokenArray() {
		return [ new Token( Token::T_STRING, 'T' . $this->id ) ];
	}

	/** @inheritDoc */
	public function toComponentValueArray() {
		return [ new Token( Token::T_STRING, 'CV' . $this->id ) ];
	}
}

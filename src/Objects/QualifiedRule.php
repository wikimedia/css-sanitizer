<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use Wikimedia\CSS\Util;

/**
 * Represent a CSS qualified rule
 */
class QualifiedRule extends Rule {

	/** @var ComponentValueList */
	protected $prelude;

	/** @var SimpleBlock */
	protected $block;

	public function __construct( Token $token = null ) {
		parent::__construct( $token ?: new Token( Token::T_EOF ) );
		$this->prelude = new ComponentValueList();
		$this->block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
	}

	public function __clone() {
		$this->prelude = clone( $this->prelude );
		$this->block = clone( $this->block );
	}

	/**
	 * Return the rule's prelude
	 * @return ComponentValueList
	 */
	public function getPrelude() {
		return $this->prelude;
	}

	/**
	 * Return the rule's block
	 * @return SimpleBlock
	 */
	public function getBlock() {
		return $this->block;
	}

	/**
	 * Set the block
	 * @param SimpleBlock $block
	 */
	public function setBlock( SimpleBlock $block = null ) {
		if ( $block->getStartTokenType() !== Token::T_LEFT_BRACE ) {
			throw new \InvalidArgumentException( 'Qualified rule block must be delimited by {}' );
		}
		$this->block = $block;
	}

	/**
	 * Return an array of Tokens that correspond to this object.
	 * @return Token[]
	 */
	public function toTokenArray() {
		$ret = [];
		if ( $this->ppComments ) {
			$ret = $this->ppComments;
			$ret[] = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );
		}
		// Manually looping and appending turns out to be noticably faster than array_merge.
		foreach ( $this->prelude->toTokenArray() as $v ) {
			$ret[] = $v;
		}
		foreach ( $this->block->toTokenArray() as $v ) {
			$ret[] = $v;
		}
		return $ret;
	}

	public function __toString() {
		return Util::stringify( $this );
	}
}

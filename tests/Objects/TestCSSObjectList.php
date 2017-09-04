<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

class TestCSSObjectList extends CSSObjectList {
	protected static $objectType = TestCSSObjectListItem::class;

	public $separator = null;

	/** @inheritDoc */
	protected function getSeparator( CSSObject $left, CSSObject $right = null ) {
		return $this->separator ?: parent::getSeparator( $left, $right );
	}
}

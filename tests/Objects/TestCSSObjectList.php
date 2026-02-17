<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

/** @extends CSSObjectList<TestCSSObjectListItem> */
class TestCSSObjectList extends CSSObjectList {
	/** @var string */
	protected static $objectType = TestCSSObjectListItem::class;

	/** @var Token[]|int */
	public $separator = null;

	/** @inheritDoc */
	protected function getSeparator( CSSObject $left, ?CSSObject $right = null ) {
		return $this->separator ?: parent::getSeparator( $left, $right );
	}
}

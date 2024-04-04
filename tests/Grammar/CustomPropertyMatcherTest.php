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
 * @covers \Wikimedia\CSS\Grammar\CustomPropertyMatcher
 */
class CustomPropertyMatcherTest extends MatcherTestBase {

	public static function provideGenerateMatches() {
		return [
			'--custom-property' => [
				'--custom-property',
				true
			],
			'--custom-property-2' => [
				'--custom-property-2',
				true
			],
			'--custom-property-' => [
				'--custom-property-',
				true
			],
			'custom-property' => [
				'custom-property',
				false
			],
			'-custom-property' => [
				'custom-property',
				false
			],
		];
	}

	/**
	 * @dataProvider provideGenerateMatches
	 *
	 * @covers \Wikimedia\CSS\Grammar\CustomPropertyMatcher::generateMatches
	 *
	 * @param string $value
	 * @param bool $expect
	 */
	public function testGenerateMatches( $value, $expect ) {
		$componentValueList = new ComponentValueList( [ new Token( Token::T_IDENT, $value ) ] );
		$matcher = TestingAccessWrapper::newFromObject( new CustomPropertyMatcher() );
		/** @var GrammarMatch[] $matches */
		$matches = iterator_to_array( $matcher->generateMatches(
			$componentValueList, 0, [ 'skip-whitespace' => true ] ) );
		if ( $expect ) {
			$this->assertCount( 1, $matches );
			$this->assertSame( $value, $matches[0]->getValues()[0]->value() );
		} else {
			$this->assertCount( 0, $matches );
		}
	}
}

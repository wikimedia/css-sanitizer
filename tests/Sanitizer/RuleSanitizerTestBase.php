<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\CSS\Util;

abstract class RuleSanitizerTestBase extends TestCase {

	/**
	 * Fetch the sanitizer to be tested
	 * @param array $options
	 */
	abstract protected function getSanitizer( $options = [] );

	/**
	 * Provide data for the rules testing.
	 * @return array Array of parameter arrays for self::testRules()
	 */
	public static function provideRules() {
		throw new BadMethodCallException( static::class . ' must override ' . __METHOD__ );
	}

	/** Test */
	public function testBasicSanitize() {
		$san = $this->getSanitizer();
		$this->assertNull( $san->sanitize( new Token( Token::T_WHITESPACE ) ) );
	}

	/**
	 * @dataProvider provideRules
	 * @param string $input
	 * @param bool $handled
	 * @param string|null $output
	 * @param string|null $minified
	 * @param array $errors
	 * @param array $options
	 */
	public function testRules( $input, $handled, $output, $minified, $errors = [], $options = [] ) {
		$san = $this->getSanitizer( $options );
		$rule = Parser::newFromString( $input )->parseRule();
		$oldRule = clone $rule;

		$this->assertSame( $handled, $san->handlesRule( $rule ) );
		$ret = $san->sanitize( $rule );
		$this->assertSame( $errors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );
			$this->assertSame( $minified, Util::stringify( $ret, [ 'minify' => true ] ) );
		}

		$this->assertEquals( (string)$oldRule, (string)$rule, 'Rule wasn\'t overwritten' );
	}

	/** Test */
	public function testGetIndex() {
		$index = $this->getSanitizer()->getIndex();
		if ( is_array( $index ) ) {
			$this->assertCount( 2, $index );
			[ $i1, $i2 ] = $index;
			$this->assertIsInt( $i1, '$index[0]' );
			$this->assertIsInt( $i2, '$index[1]' );
		} else {
			$this->assertIsInt( $index );
		}
	}
}

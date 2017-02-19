<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\AnythingMatcher;
use Wikimedia\CSS\Grammar\KeywordMatcher;
use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Grammar\NothingMatcher;
use Wikimedia\CSS\Objects\Declaration;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Sanitizer\PropertySanitizer
 */
class PropertySanitizerTest extends \PHPUnit_Framework_TestCase {

	public function testGettersSetters() {
		$m1 = new AnythingMatcher;
		$m2 = new NothingMatcher;
		$san = new PropertySanitizer( [ 'all' => $m1 ], $m2 );
		$this->assertSame( [ 'all' => $m1 ], $san->getKnownProperties() );
		$this->assertSame( $m2, $san->getCssWideKeywordsMatcher() );
		$san->setKnownProperties( [ 'foo' => $m2 ] );
		$san->setCssWideKeywordsMatcher( $m1 );
		$this->assertSame( [ 'foo' => $m2 ], $san->getKnownProperties() );
		$this->assertSame( $m1, $san->getCssWideKeywordsMatcher() );

		$san->addKnownProperties( [ 'bar' => $m1 ] );
		$this->assertSame( [ 'foo' => $m2, 'bar' => $m1 ], $san->getKnownProperties() );

		$san->addKnownProperties( [ 'bar' => $m1 ] );

		try {
			$san->addKnownProperties( [ 'bar' => clone( $m1 ) ] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Duplicate definitions for properties: bar', $ex->getMessage() );
		}

		try {
			$san->setKnownProperties( [ 'bar' => null ] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Value for \'bar\' is not a Matcher', $ex->getMessage() );
		}

		try {
			$san->setKnownProperties( [ 'Bar' => $m1 ] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Property name \'Bar\' must be lowercased', $ex->getMessage() );
		}
	}

	public function testSanitize() {
		$ID = Token::T_IDENT;
		$san = new PropertySanitizer( [
			'foo' => new AnythingMatcher,
			'bar' => new NothingMatcher,
		] );
		$rm = new \ReflectionMethod( $san, 'doSanitize' );
		$rm->setAccessible( true );

		$global = new Token( $ID, [ 'value' => 'global', 'position' => [ 1, 2 ] ] );
		$foo = new Declaration( new Token( $ID, [ 'value' => 'foo', 'position' => [ 3, 4 ] ] ) );
		$foo->getValue()->add( $global );
		$foo2 = new Declaration( new Token( $ID, [ 'value' => 'fOO', 'position' => [ 3, 4 ] ] ) );
		$foo2->getValue()->add( $global );
		$foo3 = new Declaration( new Token( $ID, [ 'value' => 'fOO', 'position' => [ 3, 4 ] ] ) );
		$foo3->getValue()->add( new Token( Token::T_WHITESPACE ) );
		$bar = new Declaration( new Token( $ID, [ 'value' => 'bar', 'position' => [ 5, 6 ] ] ) );
		$bar->getValue()->add( $global );
		$baz = new Declaration( new Token( $ID, [ 'value' => 'baz', 'position' => [ 7, 8 ] ] ) );
		$baz->getValue()->add( $global );
		$bar2 = new Declaration( new Token( $ID, [ 'value' => 'bar', 'position' => [ 9, 10 ] ] ) );
		$bar2->getValue()->add(
			new Token( Token::T_IDENT, [ 'value' => 'not-global', 'position' => [ 11, 12 ] ] )
		);

		$this->assertNull( $san->sanitize( $global ) );
		$this->assertSame( [ [ 'expected-declaration', 1, 2 ] ], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertSame( $foo, $rm->invoke( $san, $foo ) );
		$this->assertSame( [], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertSame( $foo2, $rm->invoke( $san, $foo2 ) );
		$this->assertSame( [], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertNull( $san->sanitize( $foo3 ) );
		$this->assertSame(
			[ [ 'missing-value-for-property', 3, 4, 'foo' ] ], $san->getSanitizationErrors()
		);
		$san->clearSanitizationErrors();

		$this->assertNull( $san->sanitize( $bar ) );
		$this->assertSame( [ [ 'bad-value-for-property', 1, 2, 'bar' ] ], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertNull( $san->sanitize( $baz ) );
		$this->assertSame( [ [ 'unrecognized-property', 7, 8 ] ], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$san->setCssWideKeywordsMatcher( new KeywordMatcher( 'global' ) );

		$this->assertNull( $san->sanitize( $global ) );
		$this->assertSame( [ [ 'expected-declaration', 1, 2 ] ], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertSame( $bar, $rm->invoke( $san, $bar ) );
		$this->assertSame( [], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();

		$this->assertNull( $san->sanitize( $bar2 ) );
		$this->assertSame(
			[ [ 'bad-value-for-property', 11, 12, 'bar' ] ], $san->getSanitizationErrors()
		);
		$san->clearSanitizationErrors();

		$this->assertNull( $san->sanitize( $baz ) );
		$this->assertSame( [ [ 'unrecognized-property', 7, 8 ] ], $san->getSanitizationErrors() );
		$san->clearSanitizationErrors();
	}
}

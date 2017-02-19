<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use InvalidArgumentException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\CSSFunction;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Grammar\FunctionMatcher
 */
class FunctionMatcherTest extends MatcherTestBase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage $name must be a string, callable, or null
	 */
	public function testException() {
		new FunctionMatcher( [], new TokenMatcher( Token::T_COMMA ) );
	}

	public function testNoName() {
		$matcher = new FunctionMatcher( null, new TokenMatcher( Token::T_COMMA ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$f1 = CSSFunction::newFromName( 'Foo' );
		$f1->getValue()->add( $c );
		$f2 = CSSFunction::newFromName( 'Bar' );
		$f2->getValue()->add( $c );
		$f3 = CSSFunction::newFromName( 'Foo' );

		$list = new ComponentValueList( [ $ws, $f1, $f1, $ws, $ws, $f2, $f3, $c, $f1, $ws ] );
		$expect = [ false, 2, 5, false, false, 6, false, false, 10, false, false ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $v ] : [], $generateMatches( $list, $i, $options ),
				"Skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $i + 1 ] : [], $generateMatches( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}
	}

	public function testStringName() {
		$matcher = new FunctionMatcher( 'foo', new TokenMatcher( Token::T_COMMA ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$f1 = CSSFunction::newFromName( 'Foo' );
		$f1->getValue()->add( $c );
		$f2 = CSSFunction::newFromName( 'Bar' );
		$f2->getValue()->add( $c );
		$f3 = CSSFunction::newFromName( 'Foo' );

		$list = new ComponentValueList( [ $ws, $f1, $f1, $ws, $ws, $f2, $f3, $c, $f1, $ws ] );
		$expect = [ false, 2, 5, false, false, false, false, false, 10, false, false ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $v ] : [], $generateMatches( $list, $i, $options ),
				"Skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $i + 1 ] : [], $generateMatches( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}
	}

	public function testCallbackName() {
		$matcher = new FunctionMatcher( function ( $name ) {
			return $name === 'Foo';
		}, new TokenMatcher( Token::T_COMMA ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$f1 = CSSFunction::newFromName( 'Foo' );
		$f1->getValue()->add( $c );
		$f2 = CSSFunction::newFromName( 'foo' );
		$f2->getValue()->add( $c );
		$f3 = CSSFunction::newFromName( 'Foo' );

		$list = new ComponentValueList( [ $ws, $f1, $f1, $ws, $ws, $f2, $f3, $c, $f1, $ws ] );
		$expect = [ false, 2, 5, false, false, false, false, false, 10, false, false ];

		$options = [ 'skip-whitespace' => true ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $v ] : [], $generateMatches( $list, $i, $options ),
				"Skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => false ];
		foreach ( $expect as $i => $v ) {
			$this->assertPositions( $i, $v ? [ $i + 1 ] : [], $generateMatches( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}
	}

	public function testCaptures() {
		$matcher = new FunctionMatcher( null, TokenMatcher::create( Token::T_COMMA )->capture( 'foo' ) );
		$generateMatches = $this->getGenerateMatches( $matcher );

		$ws = new Token( Token::T_WHITESPACE );
		$c = new Token( Token::T_COMMA );
		$f1 = CSSFunction::newFromName( 'foo' );
		$f1->getValue()->add( [ $ws, $c, $ws ] );
		$func = new Token( Token::T_FUNCTION, 'Foo' );
		$rp = new Token( Token::T_RIGHT_PAREN );

		$list = new ComponentValueList( [ $f1 ] );
		$ret = iterator_to_array( $generateMatches( $list, 0, [ 'skip-whitespace' => true ] ) );
		$this->assertEquals( [
			new Match( $list, 0, 1, null, [ new Match( $f1->getValue(), 1, 2, 'foo' ) ] ),
		], $ret );
	}
}

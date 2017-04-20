<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use UnexpectedValueException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\Quantifier
 */
class QuantifierTest extends MatcherTestBase {

	/**
	 * @dataProvider provideConstructors
	 * @param string $func
	 * @param array $args
	 * @param int $min
	 * @param int $max
	 * @param bool $commas
	 */
	public function testConstructors( $func, $args, $min, $max, $commas ) {
		$matcher = new TokenMatcher( Token::T_COMMA );
		$expect = new Quantifier( $matcher, $min, $max, $commas );
		array_unshift( $args, $matcher );
		$ret = call_user_func_array( [ Quantifier::class, $func ], $args );
		$this->assertEquals( $expect, $ret );
	}

	public static function provideConstructors() {
		return [
			[ 'optional', [], 0, 1, false ],
			[ 'star', [], 0, INF, false ],
			[ 'plus', [], 1, INF, false ],
			[ 'count', [ 23, 42 ], 23, 42, false ],
			[ 'hash', [], 1, INF, true ],
			[ 'hash', [ 23, 42 ], 23, 42, true ],
		];
	}

	/**
	 * @dataProvider provideGenerateMatches
	 * @param ComponentValue[] $values
	 * @param bool $skipWS
	 * @param int $min
	 * @param int $max
	 * @param bool $commas
	 * @param array $expect [ $start => $out, ... ]
	 */
	public function testGenerateMatches( $values, $skipWS, $min, $max, $commas, $expect ) {
		$matcher = TestingAccessWrapper::newFromObject(
			new Quantifier( new TokenMatcher( Token::T_IDENT ), $min, $max, $commas )
		);

		$list = new ComponentValueList( $values );
		$options = [ 'skip-whitespace' => $skipWS ];
		foreach ( $expect as $start => $out ) {
			$this->assertPositions( $start, $out, $matcher->generateMatches( $list, $start, $options ),
				"Start position $start" );
		}
	}

	public static function provideGenerateMatches() {
		$ws = new Token( Token::T_WHITESPACE );
		$ok = new Token( Token::T_IDENT, 'foo' );
		$no = new Token( Token::T_SEMICOLON );
		$c = new Token( Token::T_COMMA );
		$block = SimpleBlock::newFromDelimiter( '{' );

		return [
			'"?" match' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], true, 0, 1, false, [
					0 => [ 1, 0 ],
					1 => [ 3, 1 ],
					5 => [ 5 ],
					6 => [ 7, 6 ],
					7 => [ 7 ],
				]
			],
			'"?" match, no WS' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], false, 0, 1, false, [
					0 => [ 1, 0 ],
					1 => [ 2, 1 ],
					5 => [ 5 ],
					6 => [ 7, 6 ],
					7 => [ 7 ],
				]
			],
			'"*" match' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], true, 0, INF, false, [
					0 => [ 5, 3, 1, 0 ],
					1 => [ 5, 3, 1 ],
					3 => [ 5, 3 ],
					5 => [ 5 ],
					6 => [ 7, 6 ],
					7 => [ 7 ],
				]
			],
			'"*" match, no WS' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], false, 0, INF, false, [
					0 => [ 2, 1, 0 ],
					1 => [ 2, 1 ],
					3 => [ 4, 3 ],
					5 => [ 5 ],
					6 => [ 7, 6 ],
					7 => [ 7 ],
				]
			],
			'"+" match' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], true, 1, INF, false, [
					0 => [ 5, 3, 1 ],
					1 => [ 5, 3 ],
					3 => [ 5 ],
					5 => [],
					6 => [ 7 ],
					7 => [],
				]
			],
			'"{A}" match' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], true, 2, 2, false, [
					0 => [ 3 ],
					1 => [ 5 ],
					3 => [],
					5 => [],
					6 => [],
					7 => [],
				]
			],
			'"{A,B}" match' => [
				[ $ok, $ok, $ws, $ok, $ws, $no, $ok ], true, 1, 2, false, [
					0 => [ 3, 1 ],
					1 => [ 5, 3 ],
					3 => [ 5 ],
					5 => [],
					6 => [ 7 ],
					7 => [],
				]
			],
			'"#" match' => [
				[ $ok, $ok, $ws, $c, $ok, $c, $ws, $ok, $c, $ws, $c, $no, $ok ], true, 1, INF, true, [
					0 => [ 1 ],
					1 => [ 8, 5, 3 ],
					3 => [],
					11 => [],
					12 => [ 13 ],
					13 => [],
				]
			],
			'"#" match, always skips whitespace around commas' => [
				[ $ok, $ok, $ws, $c, $ok, $c, $ws, $ok, $c, $ws, $c, $no, $ok ], false, 1, INF, true, [
					0 => [ 1 ],
					1 => [ 8, 5, 2 ],
					3 => [],
					11 => [],
					12 => [ 13 ],
					13 => [],
				]
			],
		];
	}

	/**
	 * @expectedException UnexpectedValueException
	 * @expectedExceptionMessage Empty match in quantifier!
	 */
	public function testEmptyMatch() {
		$list = new ComponentValueList();
		$matcher = $this->getMockBuilder( Matcher::class )
			->setMethods( [ 'generateMatches' ] )
			->getMockForAbstractClass();
		$matcher->expects( $this->once() )->method( 'generateMatches' )
			->willReturn( new \ArrayIterator( [ new Match( $list, 1, 0 ) ] ) );

		$quantifier = TestingAccessWrapper::newFromObject( Quantifier::optional( $matcher ) );

		// Need to actually process the returned generator to call the method.
		$quantifier->generateMatches( $list, 1, [] )->current();
	}

	public function testCaptures() {
		$m = new KeywordMatcher( [ 'A' ] );
		$matcher = TestingAccessWrapper::newFromObject( Quantifier::count( $m->capture( 'foo' ), 0, 3 ) );

		$A = new Token( Token::T_IDENT, 'A' );
		$list = new ComponentValueList( [ $A, $A, $A, $A ] );

		$foo0 = new Match( $list, 0, 1, 'foo' );
		$foo1 = new Match( $list, 1, 1, 'foo' );
		$foo2 = new Match( $list, 2, 1, 'foo' );

		$ret = $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertEquals( [
			new Match( $list, 0, 3, null, [ $foo0, $foo1, $foo2 ] ),
			new Match( $list, 0, 2, null, [ $foo0, $foo1 ] ),
			new Match( $list, 0, 1, null, [ $foo0 ] ),
			new Match( $list, 0, 0 ),
		], iterator_to_array( $ret ) );
	}
}

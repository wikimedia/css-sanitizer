<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use InvalidArgumentException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\UnorderedGroup
 */
class UnorderedGroupTest extends MatcherTestBase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'$matchers may only contain instances of Wikimedia\CSS\Grammar\Matcher '
			. '(found Wikimedia\CSS\Objects\ComponentValueList at index 0)'
		);
		new UnorderedGroup( [ new ComponentValueList ], true );
	}

	/**
	 * @dataProvider provideConstructors
	 * @param string $func
	 * @param bool $all
	 */
	public function testConstructors( $func, $all ) {
		$matcher = new TokenMatcher( Token::T_COMMA );
		$expect = new UnorderedGroup( [ $matcher ], $all );
		$ret = call_user_func_array( [ UnorderedGroup::class, $func ], [ [ $matcher ] ] );
		$this->assertEquals( $expect, $ret );
	}

	public static function provideConstructors() {
		return [
			[ 'allOf', true ],
			[ 'someOf', false ],
		];
	}

	/**
	 * @dataProvider provideGenerateMatches
	 * @param ComponentValue[] $values
	 * @param bool $optional
	 * @param bool $skipWS
	 * @param array $expectAll
	 * @param array $expectSome
	 */
	public function testGenerateMatches( $values, $optional, $skipWS, $expectAll, $expectSome ) {
		$matchers = [
			new KeywordMatcher( [ 'A' ] ),
			new KeywordMatcher( [ 'B' ] ),
			new KeywordMatcher( [ 'C' ] ),
		];
		if ( $optional ) {
			$matchers = array_map( [ Quantifier::class, 'optional' ], $matchers );
		}
		$list = new ComponentValueList( $values );
		$options = [ 'skip-whitespace' => $skipWS ];

		$m = TestingAccessWrapper::newFromObject( UnorderedGroup::allOf( $matchers ) );
		$this->assertPositions( 0, $expectAll, $m->generateMatches( $list, 0, $options ), 'allOf' );

		$m = TestingAccessWrapper::newFromObject( UnorderedGroup::someOf( $matchers ) );
		$this->assertPositions( 0, $expectSome, $m->generateMatches( $list, 0, $options ), 'someOf' );
	}

	public static function provideGenerateMatches() {
		$ws = new Token( Token::T_WHITESPACE );
		$A = new Token( Token::T_IDENT, 'A' );
		$B = new Token( Token::T_IDENT, 'B' );
		$C = new Token( Token::T_IDENT, 'C' );
		$D = new Token( Token::T_IDENT, 'D' );
		$block = SimpleBlock::newFromDelimiter( '{' );

		return [
			'Basic match' => [
				[ $A, $B, $C ], false, false, [ 3 ], [ 3, 2, 1 ]
			],
			'Basic match, different order' => [
				[ $C, $A, $B ], false, false, [ 3 ], [ 3, 2, 1 ]
			],
			'Basic match, nonterminal' => [
				[ $A, $B, $C, $block ], false, false, [ 3 ], [ 3, 2, 1 ]
			],
			'Non-match' => [
				[], false, false, [], []
			],
			'Non-match, nonterminal' => [
				[ $D, $A, $B ], false, false, [], []
			],
			'Partial match' => [
				[ $A, $C ], false, false, [], [ 2, 1 ]
			],
			'Partial match, nonterminal' => [
				[ $C, $B, $block ], false, false, [], [ 2, 1 ]
			],

			'Basic match with whitespace' => [
				[ $B, $ws, $C, $ws, $A, $ws ], false, true, [ 6 ], [ 6, 4, 2 ]
			],
			'Basic match with non-skipped whitespace' => [
				[ $B, $ws, $C, $ws, $A, $ws ], false, false, [], [ 1 ]
			],

			'Optionals match with whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], true, true, [ 6, 4, 2, 0 ], [ 6, 4, 2, 0 ]
			],
			'Optionals match with non-skipped whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], true, false, [ 1, 0 ], [ 1, 0 ]
			],
			'Optionals match with missing "A"' => [
				[ $B, $ws, $C, $ws ], true, true, [ 4, 2, 0 ], [ 4, 2, 0 ]
			],
			'Optionals match with missing "B"' => [
				[ $C, $ws, $A, $ws ], true, true, [ 2, 0, 4 ], [ 2, 0, 4 ]
			],
			'Optionals match with missing "C"' => [
				[ $B, $ws, $C, $ws ], true, true, [ 4, 2, 0 ], [ 4, 2, 0 ]
			],
			'Optionals match with only "A"' => [
				[ $A ], true, true, [ 1, 0 ], [ 1, 0 ]
			],
			'Optionals match with only "B"' => [
				[ $B ], true, true, [ 1, 0 ], [ 1, 0 ]
			],
			'Optionals match with only "C"' => [
				[ $C ], true, true, [ 1, 0 ], [ 1, 0 ]
			],
			'Optionals match with nothing' => [
				[], true, true, [ 0 ], [ 0 ]
			],
		];
	}

	public function testDeduplication() {
		$matchers = [
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
		];
		$A = new Token( Token::T_IDENT, 'A' );
		$list = new ComponentValueList( [ $A, $A, $A, $A ] );
		$options = [ 'skip-whitespace' => false ];

		$m = TestingAccessWrapper::newFromObject( UnorderedGroup::allOf( $matchers ) );
		$this->assertPositions( 0, [ 3, 2, 1, 0 ], $m->generateMatches( $list, 0, $options ), 'allOf' );

		$m = TestingAccessWrapper::newFromObject( UnorderedGroup::someOf( $matchers ) );
		$this->assertPositions( 0, [ 3, 2, 1, 0 ], $m->generateMatches( $list, 0, $options ), 'someOf' );
	}

	public function testCaptures() {
		$m = Quantifier::optional( new KeywordMatcher( [ 'A' ] ) );
		$matcher = TestingAccessWrapper::newFromObject( UnorderedGroup::allOf( [
			$m->capture( 'foo' ), $m->capture( 'bar' ), $m->capture( 'foo' )
		] ) );

		$A = new Token( Token::T_IDENT, 'A' );
		$list = new ComponentValueList( [ $A, $A, $A, $A ] );

		$foo00 = new Match( $list, 0, 0, 'foo' );
		$foo01 = new Match( $list, 0, 1, 'foo' );
		$foo10 = new Match( $list, 1, 0, 'foo' );
		$foo11 = new Match( $list, 1, 1, 'foo' );
		$foo20 = new Match( $list, 2, 0, 'foo' );
		$foo21 = new Match( $list, 2, 1, 'foo' );
		$bar00 = new Match( $list, 0, 0, 'bar' );
		$bar01 = new Match( $list, 0, 1, 'bar' );
		$bar10 = new Match( $list, 1, 0, 'bar' );
		$bar11 = new Match( $list, 1, 1, 'bar' );
		$bar20 = new Match( $list, 2, 0, 'bar' );
		$bar21 = new Match( $list, 2, 1, 'bar' );

		$ret = $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertEquals( [
			new Match( $list, 0, 3, null, [ $foo01, $bar11, $foo21 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $bar11, $foo20 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $bar10, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $foo01, $bar10, $foo10 ] ),
			new Match( $list, 0, 3, null, [ $foo01, $foo11, $bar21 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $foo11, $bar20 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $foo10, $bar11 ] ),
			new Match( $list, 0, 1, null, [ $foo01, $foo10, $bar10 ] ),
			new Match( $list, 0, 2, null, [ $foo00, $bar01, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $bar01, $foo10 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $bar00, $foo01 ] ),
			new Match( $list, 0, 0, null, [ $foo00, $bar00, $foo00 ] ),
			new Match( $list, 0, 2, null, [ $foo00, $foo01, $bar11 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $foo01, $bar10 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $foo00, $bar01 ] ),
			new Match( $list, 0, 0, null, [ $foo00, $foo00, $bar00 ] ),
			new Match( $list, 0, 3, null, [ $bar01, $foo11, $foo21 ] ),
			new Match( $list, 0, 2, null, [ $bar01, $foo11, $foo20 ] ),
			new Match( $list, 0, 2, null, [ $bar01, $foo10, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $bar01, $foo10, $foo10 ] ),
			new Match( $list, 0, 2, null, [ $bar00, $foo01, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $bar00, $foo01, $foo10 ] ),
			new Match( $list, 0, 1, null, [ $bar00, $foo00, $foo01 ] ),
			new Match( $list, 0, 0, null, [ $bar00, $foo00, $foo00 ] ),
		], iterator_to_array( $ret ) );
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\Juxtaposition
 */
class JuxtapositionTest extends MatcherTestBase {

	/**
	 * @dataProvider provideGenerateMatches
	 * @param ComponentValue[] $values
	 * @param bool $optional
	 * @param bool $skipWS
	 * @param bool $commas
	 * @param array $expect
	 */
	public function testGenerateMatches( $values, $optional, $skipWS, $commas, $expect ) {
		$matchers = [
			new KeywordMatcher( [ 'A' ] ),
			new KeywordMatcher( [ 'B' ] ),
			new KeywordMatcher( [ 'C' ] ),
		];
		if ( $optional ) {
			$matchers = array_map( [ Quantifier::class, 'optional' ], $matchers );
		}
		$matcher = TestingAccessWrapper::newFromObject( new Juxtaposition( $matchers, $commas ) );

		$list = new ComponentValueList( $values );
		$options = [ 'skip-whitespace' => $skipWS ];
		$this->assertPositions( 0, $expect, $matcher->generateMatches( $list, 0, $options ) );
	}

	public static function provideGenerateMatches() {
		$ws = new Token( Token::T_WHITESPACE );
		$comma = new Token( Token::T_COMMA );
		$A = new Token( Token::T_IDENT, 'A' );
		$B = new Token( Token::T_IDENT, 'B' );
		$C = new Token( Token::T_IDENT, 'C' );
		$D = new Token( Token::T_IDENT, 'D' );
		$block = SimpleBlock::newFromDelimiter( '{' );

		return [
			'Basic match' => [
				[ $A, $B, $C ], false, true, false, [ 3 ]
			],
			'Basic match, nonterminal' => [
				[ $A, $B, $C, $block ], false, true, false, [ 3 ]
			],
			'Non-match' => [
				[ $A, $B ], false, true, false, []
			],
			'Non-match, nonterminal' => [
				[ $A, $B, $block ], false, true, false, []
			],
			'Basic match with whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], false, true, false, [ 6 ]
			],
			'Basic match with non-skipped whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], false, false, false, []
			],
			'Optionals match with whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], true, true, false, [ 6, 4, 2, 0 ]
			],
			'Optionals match with non-skipped whitespace' => [
				[ $A, $ws, $B, $ws, $C, $ws ], true, false, false, [ 1, 0 ]
			],
			'Optionals match with missing "A"' => [
				[ $B, $ws, $C, $ws ], true, true, false, [ 4, 2, 0 ]
			],
			'Optionals match with missing "B"' => [
				[ $A, $ws, $C, $ws ], true, true, false, [ 4, 2, 0 ]
			],
			'Optionals match with missing "C"' => [
				[ $A, $ws, $B, $ws ], true, true, false, [ 4, 2, 0 ]
			],
			'Optionals match with only "A"' => [
				[ $A ], true, true, false, [ 1, 0 ]
			],
			'Optionals match with only "B"' => [
				[ $B ], true, true, false, [ 1, 0 ]
			],
			'Optionals match with only "C"' => [
				[ $C ], true, true, false, [ 1, 0 ]
			],
			'Optionals match with nothing' => [
				[], true, true, false, [ 0 ]
			],
			'Optionals match with nothing, nonterminal' => [
				[ $comma ], true, true, false, [ 0 ]
			],

			'Basic match with commas' => [
				[ $A, $comma, $B, $comma, $C ], false, true, true, [ 5 ]
			],
			'Basic match, nonterminal with commas' => [
				[ $A, $comma, $B, $comma, $C, $comma, $block ], false, true, true, [ 5 ]
			],
			'Non-match with commas' => [
				[ $A, $comma, $B ], false, true, true, []
			],
			'Non-match, nonterminal, with commas' => [
				[ $A, $comma, $B, $C ], false, true, true, []
			],
			'Basic match with whitespace and commas' => [
				[ $A, $comma, $ws, $B, $ws, $comma, $ws, $C, $ws ], false, true, true, [ 9 ]
			],
			'Basic match with non-skipped whitespace and commas' => [
				[ $A, $comma, $ws, $B, $comma, $ws, $C, $ws ], false, false, true, []
			],
			'Optionals match with whitespace and commas' => [
				[ $A, $comma, $ws, $B, $comma, $ws, $C, $comma, $ws ], true, true, true, [ 7, 4, 1, 0 ]
			],
			'Optionals match with non-skipped whitespace and commas' => [
				[ $A, $comma, $ws, $B, $comma, $ws, $C, $comma, $ws ], true, false, true, [ 1, 0 ]
			],
			'Optionals match with missing "A" and commas' => [
				[ $B, $comma, $ws, $C, $ws ], true, true, true, [ 5, 1, 0 ]
			],
			'Optionals match with missing "B" and commas' => [
				[ $A, $comma, $C ], true, true, true, [ 3, 1, 0 ]
			],
			'Optionals match with missing "B" and extra commas' => [
				[ $A, $comma, $comma, $C ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with missing "C" and commas' => [
				[ $A, $comma, $ws, $B, $ws ], true, true, true, [ 5, 1, 0 ]
			],
			'Optionals match with only "A" and commas' => [
				[ $A ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with only "A" and commas, nonterminal' => [
				[ $A, $comma, $D ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with only "B" and commas' => [
				[ $B ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with only "B" and commas, nonterminal' => [
				[ $B, $comma, $D ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with only "C" and commas' => [
				[ $C ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with only "C" and commas, nonterminal' => [
				[ $C, $comma, $D ], true, true, true, [ 1, 0 ]
			],
			'Optionals match with nothing and commas' => [
				[], true, true, true, [ 0 ]
			],
			'Optionals match with nothing and commas, nonterminal' => [
				[ $comma ], true, true, true, [ 0 ]
			],
		];
	}

	public function testDeduplication() {
		$matchers = [
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
			Quantifier::optional( new KeywordMatcher( [ 'A' ] ) ),
		];
		$matcher = TestingAccessWrapper::newFromObject( new Juxtaposition( $matchers ) );

		$A = new Token( Token::T_IDENT, 'A' );
		$list = new ComponentValueList( [ $A, $A, $A, $A ] );

		$ret = $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertPositions( 0, [ 3, 2, 1, 0 ], $ret );
	}

	public function testCaptures() {
		$m = Quantifier::optional( new KeywordMatcher( [ 'A' ] ) );
		$matcher = TestingAccessWrapper::newFromObject( new Juxtaposition( [
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

		$ret = $matcher->generateMatches( $list, 0, [ 'skip-whitespace' => true ] );
		$this->assertEquals( [
			new Match( $list, 0, 3, null, [ $foo01, $bar11, $foo21 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $bar11, $foo20 ] ),
			new Match( $list, 0, 2, null, [ $foo01, $bar10, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $foo01, $bar10, $foo10 ] ),
			new Match( $list, 0, 2, null, [ $foo00, $bar01, $foo11 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $bar01, $foo10 ] ),
			new Match( $list, 0, 1, null, [ $foo00, $bar00, $foo01 ] ),
			new Match( $list, 0, 0, null, [ $foo00, $bar00, $foo00 ] ),
		], iterator_to_array( $ret ) );
	}
}

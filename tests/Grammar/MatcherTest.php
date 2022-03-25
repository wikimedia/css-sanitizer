<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use ArrayIterator;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\Matcher
 */
class MatcherTest extends MatcherTestBase {

	public function testCreate() {
		$m = MatcherTestMock::create();
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ null, null, null, null, null ], $m->args );

		$m = MatcherTestMock::create( 'a' );
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ 'a', null, null, null, null ], $m->args );

		$m = MatcherTestMock::create( 'a', 'b' );
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ 'a', 'b', null, null, null ], $m->args );

		$m = MatcherTestMock::create( 'a', 'b', 'c' );
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ 'a', 'b', 'c', null, null ], $m->args );

		$m = MatcherTestMock::create( 'a', 'b', 'c', 'd' );
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ 'a', 'b', 'c', 'd', null ], $m->args );

		$m = MatcherTestMock::create( 'a', 'b', 'c', 'd', 'e' );
		$this->assertInstanceOf( MatcherTestMock::class, $m );
		$this->assertSame( [ 'a', 'b', 'c', 'd', 'e' ], $m->args );
	}

	public function testDefaultOptions() {
		$matcher = $this->getMockForAbstractClass( Matcher::class );
		'@phan-var Matcher $matcher';

		$this->assertSame(
			[
				'skip-whitespace' => true,
				'nonterminal' => false,
				'mark-significance' => false,
			],
			$matcher->getDefaultOptions()
		);

		$this->assertSame( $matcher, $matcher->setDefaultOptions( [
			'skip-whitespace' => false,
			'foobar' => 'baz'
		] ) );

		$this->assertSame(
			[
				'skip-whitespace' => false,
				'foobar' => 'baz',
				'nonterminal' => false,
				'mark-significance' => false,
			],
			$matcher->getDefaultOptions()
		);
	}

	public function testNext() {
		$matcher = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Matcher::class )
		);

		$list = new ComponentValueList( [
			new Token( Token::T_WHITESPACE ),
			new Token( Token::T_COMMA ),
			SimpleBlock::newFromDelimiter( '{' ),
			new Token( Token::T_WHITESPACE ),
			new Token( Token::T_WHITESPACE ),
			new Token( Token::T_WHITESPACE ),
			new Token( Token::T_COMMA ),
			new Token( Token::T_WHITESPACE ),
		] );

		$options = [ 'skip-whitespace' => false ];
		for ( $i = -1; $i < 8; $i++ ) {
			$this->assertSame( $i + 1, $matcher->next( $list, $i, $options ),
				"Not skipping whitespace, index $i" );
		}

		$options = [ 'skip-whitespace' => true ];
		$this->assertSame( 1, $matcher->next( $list, -1, $options ) );
		$this->assertSame( 1, $matcher->next( $list, 0, $options ) );
		$this->assertSame( 2, $matcher->next( $list, 1, $options ) );
		$this->assertSame( 6, $matcher->next( $list, 2, $options ) );
		$this->assertSame( 6, $matcher->next( $list, 3, $options ) );
		$this->assertSame( 6, $matcher->next( $list, 4, $options ) );
		$this->assertSame( 6, $matcher->next( $list, 5, $options ) );
		$this->assertSame( 8, $matcher->next( $list, 6, $options ) );
		$this->assertSame( 8, $matcher->next( $list, 7, $options ) );
	}

	/**
	 * @dataProvider provideMatch
	 * @param ComponentValueList $list
	 * @param array $options
	 * @param array $ret
	 * @param int $expectStart
	 * @param bool $expect
	 */
	public function testMatch( $list, $options, $ret, $expectStart, $expect ) {
		$matcher = $this->getMockBuilder( Matcher::class )
			->onlyMethods( [ 'generateMatches' ] )
			->getMockForAbstractClass();
		$matcher->expects( $this->exactly( 2 ) )->method( 'generateMatches' )
			->willReturnCallback(
				function ( $values, $start, $options ) use ( $expectStart, $ret ) {
					$this->assertArrayHasKey( 'skip-whitespace', $options );
					$this->assertArrayHasKey( 'nonterminal', $options );
					$this->assertSame( $expectStart, $start );

					foreach ( $ret as $v ) {
						yield new GrammarMatch( $values, $start, $v - $start );
					}
				}
			);
		'@phan-var Matcher $matcher';

		$this->assertSame( $expect, (bool)$matcher->matchAgainst( $list, $options ) );

		$matcher->setDefaultOptions( $options );
		$this->assertSame( $expect, (bool)$matcher->matchAgainst( $list ) );
	}

	public static function provideMatch() {
		$ws = new Token( Token::T_WHITESPACE );
		$cv = new Token( Token::T_COMMA );
		$list = new ComponentValueList( [ $ws, $ws, $cv, $cv ] );

		return [
			'Default options, no matches' => [
				$list, [], [], 2, false
			],
			'No skip whitespace, no matches' => [
				$list, [ 'skip-whitespace' => false ], [], 0, false
			],
			'Nonterminal, no matches' => [
				$list, [ 'nonterminal' => true ], [], 2, false
			],

			'Default options, partial matches' => [
				$list, [], [ 1, 2, 3 ], 2, false
			],
			'No skip whitespace, partial matches' => [
				$list, [ 'skip-whitespace' => false ], [ 1, 2, 3 ], 0, false
			],
			'Nonterminal, partial matches' => [
				$list, [ 'nonterminal' => true ], [ 1, 2, 3 ], 2, true
			],

			'Default options, full match' => [
				$list, [], [ 1, 2, 4 ], 2, true
			],
			'No skip whitespace, full match' => [
				$list, [ 'skip-whitespace' => false ], [ 1, 2, 4 ], 0, true
			],
			'Nonterminal, full match' => [
				$list, [ 'nonterminal' => true ], [ 1, 2, 4 ], 2, true
			],
		];
	}

	public function testMatchSignificantWhitespace() {
		$tok = new Token( Token::T_COMMA );
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = $ws->copyWithSignificance( false );

		// Test list. The whitespaces need to be cloned, or it'll get confused.
		$testBlock = SimpleBlock::newFromDelimiter( '[' );
		$testBlock->getValue()->add( [ clone $ws , $tok, clone $ws , clone $ws , $tok, $Iws ] );
		$testList = new ComponentValueList( [
			clone $ws , $tok, clone $ws , clone $ws , $tok, clone $Iws , $testBlock, clone $ws
		] );
		$origList = clone $testList;

		// Expect list. No cloning needed here.
		$expectBlock = SimpleBlock::newFromDelimiter( '[' );
		$expectBlock->getValue()->add( [ $Iws, $tok, $ws, $Iws, $tok, $ws ] );
		$expectList = new ComponentValueList( [
			$Iws, $tok, $ws, $Iws, $tok, $ws, $expectBlock, $ws
		] );

		$matcher = $this->getMockForAbstractClass( Matcher::class );
		$matcher->method( 'generateMatches' )->willReturn( new ArrayIterator( [
			new GrammarMatch( $testList, 1, 6, null, [
				new GrammarMatch( $testList, 2, 1, 'significantWhitespace' ),
				new GrammarMatch( $testList, 2, 2, 'not-significantWhitespace' ),
				new GrammarMatch( $testList, 6, 1, 'block', [
					new GrammarMatch( $testBlock->getValue(), 2, 1, 'significantWhitespace' ),
					new GrammarMatch( $testBlock->getValue(), 4, 2, 'something', [
						new GrammarMatch( $testBlock->getValue(), 5, 1, 'significantWhitespace' ),
					] ),
				] ),
				new GrammarMatch( $testList, 5, 1, 'significantWhitespace' ),
			] )
		] ) );
		'@phan-var Matcher $matcher';

		$options = [ 'mark-significance' => true, 'nonterminal' => false ];
		$this->assertFalse( (bool)$matcher->matchAgainst( $testList, $options ) );
		$this->assertEquals( $origList, $testList );

		$options = [ 'mark-significance' => true, 'nonterminal' => true ];
		$this->assertTrue( (bool)$matcher->matchAgainst( $testList, $options ) );
		$this->assertEquals( $expectList, $testList );
	}

	public function testMakeMatch() {
		$dummy = new ComponentValueList();
		$matcher = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Matcher::class )
		);
		$matcher2 = TestingAccessWrapper::newFromObject( $matcher->capture( 'foo' ) );

		$m0 = new GrammarMatch( $dummy, 0, 0 );
		$m1 = new GrammarMatch( $dummy, 1, 0, 'm1' );
		$m2 = new GrammarMatch( $dummy, 2, 0, 'm2' );
		$m3 = new GrammarMatch( $dummy, 3, 0, 'm3' );
		$m4 = new GrammarMatch( $dummy, 4, 0, 'm4', [ $m2, $m3 ] );
		$m5 = new GrammarMatch( $dummy, 5, 0, 'm5' );
		$m6 = new GrammarMatch( $dummy, 6, 0, 'm6' );
		$m7 = new GrammarMatch( $dummy, 7, 0, null, [ $m5, $m6 ] );
		$m8 = new GrammarMatch( $dummy, 8, 0, 'm8' );
		$stack = [ [ $m0 ], [ $m1 ], [ $m4 ], [ $m7 ] ];
		$expect = [ $m1, $m4, $m5, $m6, $m8 ];

		$this->assertMatch( new GrammarMatch( $dummy, 1, 1 ), $matcher->makeMatch( $dummy, 1, 2 ) );
		$this->assertMatch( new GrammarMatch( $dummy, 1, 1, 'foo' ), $matcher2->makeMatch( $dummy, 1, 2 ) );

		$this->assertMatch( new GrammarMatch( $dummy, 1, 1 ), $matcher->makeMatch( $dummy, 1, 2, $m0 ) );
		$this->assertMatch(
			new GrammarMatch( $dummy, 1, 1, 'foo' ), $matcher2->makeMatch( $dummy, 1, 2, $m0 )
		);

		$this->assertMatch(
			new GrammarMatch( $dummy, 1, 1, null, [ $m8 ] ), $matcher->makeMatch( $dummy, 1, 2, $m8 )
		);
		$this->assertMatch(
			new GrammarMatch( $dummy, 1, 1, 'foo', [ $m8 ] ), $matcher2->makeMatch( $dummy, 1, 2, $m8 )
		);

		$this->assertMatch(
			new GrammarMatch( $dummy, 1, 1, null, $expect ), $matcher->makeMatch( $dummy, 1, 2, $m8, $stack )
		);
		$this->assertMatch(
			new GrammarMatch( $dummy, 1, 1, 'foo', $expect ), $matcher2->makeMatch( $dummy, 1, 2, $m8, $stack )
		);

		$tok1 = new Token( Token::T_IDENT, 'a' );
		$tok2 = new Token( Token::T_IDENT, 'b' );
		$tok3 = new Token( Token::T_IDENT, 'c' );
		$tok4 = new Token( Token::T_IDENT, 'd' );
		$tok5 = new Token( Token::T_IDENT, 'e' );
		$list = new ComponentValueList( [ $tok1, $tok2, $tok3, $tok4, $tok5 ] );
		$match = new GrammarMatch( $list, 2, 2 );
		$this->assertSame( [ $tok3, $tok4 ], $match->getValues() );
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\CSSFunction;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\AnythingMatcher
 */
class AnythingMatcherTest extends MatcherTestBase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid quantifier
	 */
	public function testException() {
		new AnythingMatcher( [ 'quantifier' => '#' ] );
	}

	/**
	 * @dataProvider provideStuff
	 * @param ComponentValue[] $values
	 * @param bool $match
	 * @param array $options
	 */
	public function testStandard( $values, $match, $options = [ 'toplevel' => true ] ) {
		$matcher = TestingAccessWrapper::newFromObject( new AnythingMatcher( $options ) );

		$options = [ 'skip-whitespace' => true ];
		$list = new ComponentValueList( $values );
		$ret = $matcher->generateMatches( $list, 0, $options );
		$this->assertSame( $match, (bool)iterator_to_array( $ret ) );
	}

	public static function provideStuff() {
		$tok = new Token( Token::T_IDENT, 'foo' );
		$rp = new Token( Token::T_RIGHT_PAREN );
		$badString = new Token( Token::T_BAD_STRING );
		$badUrl = new Token( Token::T_BAD_URL );
		$bang = new Token( Token::T_DELIM, '!' );
		$delim = new Token( Token::T_DELIM, '?' );
		$semicolon = new Token( Token::T_SEMICOLON );

		$block1 = SimpleBlock::newFromDelimiter( '{' );
		$block2 = SimpleBlock::newFromDelimiter( '(' );
		$block2->getValue()->add( [ $tok, $bang, $semicolon ] );
		$block3 = SimpleBlock::newFromDelimiter( '[' );
		$block3->getValue()->add( $badString );

		$func1 = CSSFunction::newFromName( 'foo' );
		$func2 = CSSFunction::newFromName( 'foo' );
		$func2->getValue()->add( [ $tok, $bang, $semicolon ] );
		$func3 = CSSFunction::newFromName( 'foo' );
		$func3->getValue()->add( $badUrl );

		$block4 = SimpleBlock::newFromDelimiter( '[' );
		$block4->getValue()->add( $block2 );
		$block5 = SimpleBlock::newFromDelimiter( '[' );
		$block5->getValue()->add( $func3 );
		$func4 = CSSFunction::newFromName( 'foo' );
		$func4->getValue()->add( $func2 );
		$func5 = CSSFunction::newFromName( 'foo' );
		$func5->getValue()->add( $block3 );

		return [
			[ [ $tok ], true ],
			[ [ $rp ], false ],
			[ [ new Token( Token::T_RIGHT_BRACE ) ], false ],
			[ [ new Token( Token::T_RIGHT_BRACKET ) ], false ],
			[ [ $badString ], false ],
			[ [ $badUrl ], false ],
			[ [ $bang ], false ],
			[ [ $bang ], true, [] ],
			[ [ $semicolon ], false ],
			[ [ $semicolon ], true, [] ],
			[ [ $block1 ], true ],
			[ [ $block2 ], true ],
			[ [ $block3 ], false ],
			[ [ $block4 ], true ],
			[ [ $block5 ], false ],
			[ [ $func1 ], true ],
			[ [ $func2 ], true ],
			[ [ $func3 ], false ],
			[ [ $func4 ], true ],
			[ [ $func5 ], false ],
		];
	}

	public function testWhitespaceMatching() {
		$ws = new Token( Token::T_WHITESPACE );
		$nonws = new Token( Token::T_COLON );
		$block = SimpleBlock::newFromDelimiter( '[' );
		$bv = $block->getValue();
		$bv->add( [ $ws, $nonws, $ws, $ws, $nonws, $ws ] );
		$list = new ComponentValueList( [ $nonws, $ws, $ws, $block ] );

		$matcher = TestingAccessWrapper::newFromObject( new AnythingMatcher() );

		$options = [ 'skip-whitespace' => true ];
		$this->assertEquals(
			[ new Match( $list, 0, 3 ) ],
			iterator_to_array( $matcher->generateMatches( $list, 0, $options ) )
		);
		$this->assertEquals(
			[
				new Match( $list, 1, 2, null, [
					new Match( $list, 1, 1, 'significantWhitespace' ),
				] )
			],
			iterator_to_array( $matcher->generateMatches( $list, 1, $options ) )
		);
		$this->assertEquals(
			[ new Match( $list, 3, 1 ) ],
			iterator_to_array( $matcher->generateMatches( $list, 3, $options ) )
		);

		$options = [ 'skip-whitespace' => false ];
		$this->assertEquals(
			[ new Match( $list, 0, 1 ) ],
			iterator_to_array( $matcher->generateMatches( $list, 0, $options ) )
		);
		$this->assertEquals(
			[
				new Match( $list, 1, 1, null, [
					new Match( $list, 1, 1, 'significantWhitespace' ),
				] )
			],
			iterator_to_array( $matcher->generateMatches( $list, 1, $options ) )
		);
		$this->assertEquals(
			[
				new Match( $list, 3, 1, null, [
					new Match( $bv, 0, 1, 'significantWhitespace' ),
					new Match( $bv, 2, 1, 'significantWhitespace' ),
					new Match( $bv, 3, 1, 'significantWhitespace' ),
					new Match( $bv, 5, 1, 'significantWhitespace' ),
				] )
			],
			iterator_to_array( $matcher->generateMatches( $list, 3, $options ) )
		);
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Grammar\GrammarMatch
 */
class MatchTest extends TestCase {

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'$capturedMatches may only contain instances of Wikimedia\CSS\Grammar\GrammarMatch '
			. '(found stdClass at index 0)'
		);
		// @phan-suppress-next-line PhanTypeMismatchArgument,PhanNoopNew
		new GrammarMatch( new ComponentValueList(), 1, 2, null, [ new stdClass ] );
	}

	public function testMatch() {
		$tok1 = new Token( Token::T_IDENT, 'a' );
		$tok2 = new Token( Token::T_IDENT, 'b' );
		$tok3 = new Token( Token::T_IDENT, 'c' );
		$tok4 = new Token( Token::T_IDENT, 'd' );
		$tok5 = new Token( Token::T_IDENT, 'e' );
		$list = new ComponentValueList( [ $tok1, $tok2, $tok3, $tok4, $tok5 ] );

		$match = new GrammarMatch( $list, 1, 3 );
		$this->assertSame( 1, $match->getStart() );
		$this->assertSame( 3, $match->getLength() );
		$this->assertSame( 4, $match->getNext() );
		$this->assertSame( [ $tok2, $tok3, $tok4 ], $match->getValues() );
		$this->assertNull( $match->getName() );
		$this->assertSame( [], $match->getCapturedMatches() );

		$match2 = new GrammarMatch( $list, 2, 0, 'foo', [ $match ] );
		$this->assertSame( 2, $match2->getStart() );
		$this->assertSame( 0, $match2->getLength() );
		$this->assertSame( 2, $match2->getNext() );
		$this->assertSame( [], $match2->getValues() );
		$this->assertSame( 'foo', $match2->getName() );
		$this->assertSame( [ $match ], $match2->getCapturedMatches() );

		$this->assertNotSame( $match->getUniqueID(), $match2->getUniqueID() );

		$match3 = new GrammarMatch( $list, 1, 3 );
		$match4 = new GrammarMatch( $list, 2, 0, 'foo', [ $match3 ] );
		$this->assertSame( $match->getUniqueID(), $match3->getUniqueID() );
		$this->assertSame( $match2->getUniqueID(), $match4->getUniqueID() );
	}

	public function testFixWhitespace() {
		$tok1 = new Token( Token::T_WHITESPACE );
		$tok2 = new Token( Token::T_WHITESPACE );
		$tok3 = new Token( Token::T_WHITESPACE );
		$tok4 = new Token( Token::T_WHITESPACE );

		$match2 = new GrammarMatch( new ComponentValueList( [ $tok1, $tok2, $tok3 ] ), 0, 3 );
		$match = new GrammarMatch( new ComponentValueList( [ $tok1, $tok2, $tok3 ] ), 0, 3, null, [ $match2 ] );

		$match->fixWhitespace( $tok2, $tok4 );
		$this->assertSame( [ $tok1, $tok4, $tok3 ], $match->getValues() );
		$this->assertSame( [ $tok1, $tok4, $tok3 ], $match2->getValues() );
	}

	public function testToString() {
		$tok1 = new Token( Token::T_IDENT, 'a' );
		$tok2 = new Token( Token::T_DELIM, '.' );
		$tok3 = new Token( Token::T_IDENT, 'b' );
		$tok4 = new Token( Token::T_DELIM, '.' );
		$tok5 = new Token( Token::T_IDENT, 'c' );
		$match = new GrammarMatch( new ComponentValueList( [ $tok1, $tok2, $tok3, $tok4, $tok5 ] ), 0, 5 );
		$match2 = new GrammarMatch( new ComponentValueList( [ $tok1, $tok2, $tok3, $tok4, $tok5 ] ), 1, 2 );

		$this->assertSame( 'a.b.c', (string)$match );
		$this->assertSame( '.b', (string)$match2 );
	}

}

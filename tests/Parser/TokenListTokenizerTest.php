<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Parser;

use InvalidArgumentException;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Objects\TokenList;

/**
 * @covers \Wikimedia\CSS\Parser\TokenListTokenizer
 */
class TokenListTokenizerTest extends \PHPUnit\Framework\TestCase {

	public function testTokenizer() {
		$T = Token::T_WHITESPACE;
		$tokens = [ new Token( $T ), new Token( $T ), new Token( $T ) ];
		$eofToken = new Token( Token::T_EOF, [ 'position' => [ 123, 42 ] ] );

		$t = new TokenListTokenizer( $tokens, $eofToken );
		$this->assertSame( $tokens[0], $t->consumeToken() );
		$this->assertSame( $tokens[1], $t->consumeToken() );
		$this->assertSame( $tokens[2], $t->consumeToken() );
		$this->assertSame( $eofToken, $t->consumeToken() );
		$this->assertSame( $eofToken, $t->consumeToken() );
		$this->assertSame( $eofToken, $t->consumeToken() );
		$this->assertSame( [], $t->getParseErrors() );

		$t = new TokenListTokenizer( new TokenList( $tokens ), $eofToken );
		$this->assertSame( $tokens[0], $t->consumeToken() );
		$this->assertSame( $tokens[1], $t->consumeToken() );
		$this->assertSame( $tokens[2], $t->consumeToken() );
		$this->assertSame( $eofToken, $t->consumeToken() );

		try {
			new TokenListTokenizer( new ComponentValueList() );
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame( '$tokens must be a TokenList or an array of tokens', $ex->getMessage() );
		}

		$t = new TokenListTokenizer( [], $eofToken );
		$this->assertSame( $eofToken, $t->consumeToken() );

		$notEofToken = new Token( Token::T_SEMICOLON, [ 'position' => [ 456, 23 ] ] );
		$t = new TokenListTokenizer( $tokens, $notEofToken );
		$this->assertSame( $tokens[0], $t->consumeToken() );
		$this->assertSame( $tokens[1], $t->consumeToken() );
		$this->assertSame( $tokens[2], $t->consumeToken() );
		$tok = $t->consumeToken();
		$this->assertSame( Token::T_EOF, $tok->type() );
		$this->assertSame( $notEofToken->getPosition(), $tok->getPosition() );
		$this->assertSame( $tok, $t->consumeToken() );

		$t = new TokenListTokenizer( [] );
		$tok = $t->consumeToken();
		$this->assertSame( Token::T_EOF, $tok->type() );
		$this->assertSame( $tok, $t->consumeToken() );

		$t->clearParseErrors();
	}

	public function testException() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'$tokens may only contain instances of Wikimedia\CSS\Objects\Token (found string at index 0)'
		);
		new TokenListTokenizer( [ 'bad' ] );
	}
}

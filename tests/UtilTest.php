<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\RuleList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Objects\TokenList;

/**
 * @covers \Wikimedia\CSS\Util
 */
class UtilTest extends TestCase {

	public function testAllInstanceOf() {
		Util::assertAllInstanceOf( [], Token::class, 'Test' );
		Util::assertAllInstanceOf(
			[ new Token( Token::T_WHITESPACE ), new Token( Token::T_EOF ) ],
			Token::class,
			'Test'
		);

		try {
			Util::assertAllInstanceOf(
				[ new Token( Token::T_WHITESPACE ), null ],
				Token::class,
				'Test'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Test may only contain instances of Wikimedia\CSS\Objects\Token (found NULL at index 1)',
				$ex->getMessage()
			);
		}

		try {
			Util::assertAllInstanceOf(
				[ new Token( Token::T_WHITESPACE ), SimpleBlock::newFromDelimiter( Token::T_LEFT_PAREN ) ],
				Token::class,
				'Test'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Test may only contain instances of Wikimedia\CSS\Objects\Token '
					. '(found Wikimedia\CSS\Objects\SimpleBlock at index 1)',
				$ex->getMessage()
			);
		}
	}

	public function testAllTokensOfType() {
		Util::assertAllTokensOfType( [], Token::T_EOF, 'Test' );
		Util::assertAllTokensOfType(
			[ new Token( Token::T_EOF ), new Token( Token::T_EOF ) ],
			Token::T_EOF,
			'Test'
		);

		try {
			Util::assertAllTokensOfType(
				// @phan-suppress-next-line PhanTypeMismatchArgument The mismatch is tested here
				[ new Token( Token::T_WHITESPACE ), null ],
				Token::T_WHITESPACE,
				'Test'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Test may only contain instances of Wikimedia\CSS\Objects\Token (found NULL at index 1)',
				$ex->getMessage()
			);
		}

		try {
			Util::assertAllTokensOfType(
				// @phan-suppress-next-line PhanTypeMismatchArgument
				[ new Token( Token::T_WHITESPACE ), SimpleBlock::newFromDelimiter( Token::T_LEFT_PAREN ) ],
				Token::T_WHITESPACE,
				'Test'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Test may only contain instances of Wikimedia\CSS\Objects\Token '
					. '(found Wikimedia\CSS\Objects\SimpleBlock at index 1)',
				$ex->getMessage()
			);
		}

		try {
			Util::assertAllTokensOfType(
				[ new Token( Token::T_WHITESPACE ), new Token( Token::T_EOF ) ],
				Token::T_WHITESPACE,
				'Test'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Test may only contain "whitespace" tokens (found "EOF" at index 1)',
				$ex->getMessage()
			);
		}
	}

	public function testFindFirstNonWhitespace() {
		$values1 = [ new Token( Token::T_WHITESPACE ) ];
		$values2 = [ $values1[0], new Token( Token::T_COMMA ) ];
		$values3 = [ $values1[0], SimpleBlock::newFromDelimiter( Token::T_LEFT_PAREN ) ];

		$this->assertNull( Util::findFirstNonWhitespace( new TokenList( [] ) ) );
		$this->assertNull( Util::findFirstNonWhitespace( new ComponentValueList( [] ) ) );
		$this->assertNull( Util::findFirstNonWhitespace( new TokenList( $values1 ) ) );
		$this->assertNull( Util::findFirstNonWhitespace( new ComponentValueList( $values1 ) ) );
		$this->assertSame( $values2[1], Util::findFirstNonWhitespace( new TokenList( $values2 ) ) );
		$this->assertSame(
			$values2[1], Util::findFirstNonWhitespace( new ComponentValueList( $values2 ) )
		);
		$this->assertSame(
			$values3[1], Util::findFirstNonWhitespace( new ComponentValueList( $values3 ) )
		);

		try {
			// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal
			Util::findFirstNonWhitespace( new RuleList( [] ) );
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame( 'List must be TokenList or ComponentValueList', $ex->getMessage() );
		}
	}

	public function testStringify() {
		// Each object's toTokenArray() is already tested and each token type's
		// as well. Here we just need to test that combining works right.

		$tokenList = new TokenList( [
			new Token( Token::T_LEFT_BRACE ),
			new Token( Token::T_WHITESPACE ),
			new Token( Token::T_IDENT, 'Hello' ),
			new Token( Token::T_WHITESPACE, [ 'significant' => false ] ),
			new Token( Token::T_IDENT, 'wor' ),
			new Token( Token::T_IDENT, 'ld' ),
			new Token( Token::T_DELIM, '!' ),
			new Token( Token::T_WHITESPACE, [ 'significant' => false ] ),
			new Token( Token::T_DELIM, '!' ),
			new Token( Token::T_WHITESPACE, [ 'significant' => false ] ),
			new Token( Token::T_RIGHT_BRACE ),
		] );

		$this->assertSame( '{ Hello wor/**/ld! ! }', Util::stringify( $tokenList ) );
		$this->assertSame( '{ Hello wor/**/ld!!}', Util::stringify( $tokenList, [ 'minify' => true ] ) );

		$this->assertSame( '', Util::stringify( new TokenList() ) );

		$this->assertSame( '{}', Util::stringify( [
			new Token( Token::T_LEFT_BRACE ),
			new Token( Token::T_RIGHT_BRACE ),
		] ) );

		$this->assertSame( '</**//style>', Util::stringify( [
			new Token( Token::T_DELIM, '<' ),
			new Token( Token::T_DELIM, '/' ),
			new Token( Token::T_IDENT, 'style' ),
			new Token( Token::T_DELIM, '>' ),
			// should not be </style> (T381617)
		] ) );
	}

	/**
	 * @covers \Wikimedia\CSS\Objects\Token::urangeHack
	 */
	public function testStringify_urangeHack() {
		$n1 = new Token( Token::T_NUMBER, [
			'value' => 123, 'typeFlag' => 'integer', 'representation' => '+123'
		] );
		$n2 = new Token( Token::T_NUMBER, [
			'value' => -456, 'typeFlag' => 'integer', 'representation' => '-456'
		] );
		$q = new Token( Token::T_DELIM, '?' );

		$tokenList = new TokenList( [ new Token( Token::T_IDENT, 'U' ), $n1, $n2 ] );
		$this->assertSame( 'U/**/+123/**/-456', Util::stringify( $tokenList ) );

		$tokenList = new TokenList( [ new Token( Token::T_IDENT, 'U' ), $n1, $n2 ] );
		// @phan-suppress-next-line PhanNonClassMethodCall False positive
		$tokenList[0]->urangeHack( 3 );
		$this->assertSame( 'U+123-456', Util::stringify( $tokenList ) );

		$tokenList = new TokenList( [ new Token( Token::T_IDENT, 'U' ), $n1, $n2 ] );
		// @phan-suppress-next-line PhanNonClassMethodCall False positive
		$tokenList[0]->urangeHack( 2 );
		// @phan-suppress-next-line PhanNonClassMethodCall False positive
		$tokenList[0]->urangeHack( 1 );
		$this->assertSame( 'U+123/**/-456', Util::stringify( $tokenList ) );

		// Not really expected, but valid.
		$tokenList = new TokenList( [ new Token( Token::T_IDENT, 'U' ), $n1, $q, $q, $q ] );
		// @phan-suppress-next-line PhanNonClassMethodCall False positive
		$tokenList[0]->urangeHack( 3 );
		$this->assertSame( 'U+123???', Util::stringify( $tokenList ) );
	}
}

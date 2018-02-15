<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Parser;

use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Parser\DataSourceTokenizer
 */
class DataSourceTokenizerTest extends \PHPUnit\Framework\TestCase {

	public function testCharacterNormalization() {
		$t = TestingAccessWrapper::newFromObject(
			new DataSourceTokenizer( new StringDataSource( "\x00-\f-\r-\r\n-\n\r-" ) )
		);

		$this->assertSame( '�', $t->nextChar() );
		$this->assertSame( '-', $t->nextChar() );
		$this->assertSame( "\n", $t->nextChar() );
		$this->assertSame( '-', $t->nextChar() );
		$this->assertSame( "\n", $t->nextChar() );
		$this->assertSame( '-', $t->nextChar() );
		$this->assertSame( "\n", $t->nextChar() );
		$this->assertSame( '-', $t->nextChar() );
		$this->assertSame( "\n", $t->nextChar() );
		$this->assertSame( "\n", $t->nextChar() );
		$this->assertSame( '-', $t->nextChar() );
	}

	/**
	 * @dataProvider provideTokenization
	 * @param string $input
	 * @param (Token|array)[] $expect Token to expect, or parse error
	 * @param array $options
	 */
	public function testTokenization( $input, $expect, $options = [] ) {
		$t = new DataSourceTokenizer( new StringDataSource( $input ), $options );

		foreach ( $expect as $i => $e ) {
			if ( $e instanceof Token ) {
				$this->assertSame( [], $t->getParseErrors(), "No parse errors at $i" );
				$this->assertEquals( $e, $t->consumeToken(), "At $i" );
			} else {
				$this->assertSame( $e, $t->getParseErrors(), "Parse error at $i" );
				$t->clearParseErrors();
			}
		}
		$this->assertSame( [], $t->getParseErrors(), "No parse errors at end of test" );
	}

	protected static function t( $type, $line, $pos, $value = '', $data = [] ) {
		return new Token( $type, [ 'position' => [ $line, $pos ], 'value' => $value ] + $data );
	}

	protected static function nt( $type, $line, $pos, $value, $repr, $typeFlag, $unit = '' ) {
		return new Token( $type, [
			'position' => [ $line, $pos ],
			'value' => $value,
			'representation' => $repr,
			'typeFlag' => $typeFlag,
			'unit' => $unit,
		] );
	}

	public static function provideTokenization() {
		return [
			'whitespace' => [
				"  \n   \t\n\nx\n\n\n\n    \t\t\tz",
				[
					self::t( Token::T_WHITESPACE, 1, 1 ),
					self::t( Token::T_IDENT, 4, 1, 'x' ),
					self::t( Token::T_WHITESPACE, 4, 2 ),
					self::t( Token::T_IDENT, 8, 8, 'z' ),
					self::t( Token::T_EOF, 8, 9 ),
					self::t( Token::T_EOF, 8, 9 ),
				]
			],

			'string with double quotes' => [
				'"foo bar \1f36a \1F36A \01f36a \1F36Ax \01f36aa \"\' \0 \d800 \dfff \110000  ok?"',
				[
					self::t( Token::T_STRING, 1, 1, 'foo bar 🍪🍪🍪🍪x 🍪a "\' ���� ok?' ),
					self::t( Token::T_EOF, 1, 81 ),
				]
			],
			'string with single quotes' => [
				'\'foo bar \1f36a \1F36A \01f36a \1F36Ax \01f36aa "\\\' \0 \d800 \dfff \110000  ok?\'',
				[
					self::t( Token::T_STRING, 1, 1, 'foo bar 🍪🍪🍪🍪x 🍪a "\' ���� ok?' ),
					self::t( Token::T_EOF, 1, 81 ),
				]
			],
			'string to EOF' => [
				'"foo bar',
				[
					self::t( Token::T_STRING, 1, 1, 'foo bar' ),
					[ [ 'unclosed-string', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 9 ),
				]
			],
			'string with embedded newline' => [
				"'foo \nbar'",
				[
					self::t( Token::T_BAD_STRING, 1, 1 ),
					[ [ 'newline-in-string', 1, 6 ] ],
					self::t( Token::T_WHITESPACE, 1, 6 ),
					self::t( Token::T_IDENT, 2, 1, 'bar' ),
					self::t( Token::T_STRING, 2, 4, '' ),
					[ [ 'unclosed-string', 2, 4 ] ],
					self::t( Token::T_EOF, 2, 5 ),
				]
			],
			'string with escaped newline' => [
				"'foo \\\nbar'",
				[
					self::t( Token::T_STRING, 1, 1, 'foo bar' ),
					self::t( Token::T_EOF, 2, 5 ),
				]
			],
			'string with escape at EOF' => [
				"'foo \\",
				[
					self::t( Token::T_STRING, 1, 1, 'foo ' ),
					[ [ 'bad-escape', 1, 6 ], [ 'unclosed-string', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 7 ),
				]
			],
			'string with escape terminated with EOF' => [
				"'foo\\21",
				[
					self::t( Token::T_STRING, 1, 1, 'foo!' ),
					[ [ 'unclosed-string', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 8 ),
				]
			],

			'hash' => [
				"#foo\n#123abc\n#\\21xx\n#!",
				[
					self::t( Token::T_HASH, 1, 1, 'foo', [ 'typeFlag' => 'id' ] ),
					self::t( Token::T_WHITESPACE, 1, 5 ),
					self::t( Token::T_HASH, 2, 1, '123abc', [ 'typeFlag' => 'unrestricted' ] ),
					self::t( Token::T_WHITESPACE, 2, 8 ),
					self::t( Token::T_HASH, 3, 1, '!xx', [ 'typeFlag' => 'id' ] ),
					self::t( Token::T_WHITESPACE, 3, 7 ),
					self::t( Token::T_DELIM, 4, 1, '#' ),
					self::t( Token::T_DELIM, 4, 2, '!' ),
					self::t( Token::T_EOF, 4, 3 ),
				]
			],

			'punctuation' => [
				'$=$()[]{},:;^=^|=|||~=~%`**=',
				[
					self::t( Token::T_SUFFIX_MATCH, 1, 1 ),
					self::t( Token::T_DELIM, 1, 3, '$' ),
					self::t( Token::T_LEFT_PAREN, 1, 4 ),
					self::t( Token::T_RIGHT_PAREN, 1, 5 ),
					self::t( Token::T_LEFT_BRACKET, 1, 6 ),
					self::t( Token::T_RIGHT_BRACKET, 1, 7 ),
					self::t( Token::T_LEFT_BRACE, 1, 8 ),
					self::t( Token::T_RIGHT_BRACE, 1, 9 ),
					self::t( Token::T_COMMA, 1, 10 ),
					self::t( Token::T_COLON, 1, 11 ),
					self::t( Token::T_SEMICOLON, 1, 12 ),
					self::t( Token::T_PREFIX_MATCH, 1, 13 ),
					self::t( Token::T_DELIM, 1, 15, '^' ),
					self::t( Token::T_DASH_MATCH, 1, 16 ),
					self::t( Token::T_COLUMN, 1, 18 ),
					self::t( Token::T_DELIM, 1, 20, '|' ),
					self::t( Token::T_INCLUDE_MATCH, 1, 21 ),
					self::t( Token::T_DELIM, 1, 23, '~' ),
					self::t( Token::T_DELIM, 1, 24, '%' ),
					self::t( Token::T_DELIM, 1, 25, '`' ),
					self::t( Token::T_DELIM, 1, 26, '*' ),
					self::t( Token::T_SUBSTRING_MATCH, 1, 27 ),
					self::t( Token::T_EOF, 1, 29 ),
				]
			],

			'numbers' => [
				"0123\n12.56\n0px\n+.5em\n+.5e6m\n-12%\n3.5%\n12E-8\n12e+01\n.1ex\n+\n.x\n",
				[
					self::nt( Token::T_NUMBER, 1, 1, 123, '0123', 'integer' ),
					self::t( Token::T_WHITESPACE, 1, 5 ),
					self::nt( Token::T_NUMBER, 2, 1, 12.56, '12.56', 'number' ),
					self::t( Token::T_WHITESPACE, 2, 6 ),
					self::nt( Token::T_DIMENSION, 3, 1, 0, '0', 'integer', 'px' ),
					self::t( Token::T_WHITESPACE, 3, 4 ),
					self::nt( Token::T_DIMENSION, 4, 1, 0.5, '+.5', 'number', 'em' ),
					self::t( Token::T_WHITESPACE, 4, 6 ),
					self::nt( Token::T_DIMENSION, 5, 1, 500000, '+.5e6', 'number', 'm' ),
					self::t( Token::T_WHITESPACE, 5, 7 ),
					self::nt( Token::T_PERCENTAGE, 6, 1, -12, '-12', 'integer' ),
					self::t( Token::T_WHITESPACE, 6, 5 ),
					self::nt( Token::T_PERCENTAGE, 7, 1, 3.5, '3.5', 'number' ),
					self::t( Token::T_WHITESPACE, 7, 5 ),
					self::nt( Token::T_NUMBER, 8, 1, 0.00000012, '12E-8', 'number' ),
					self::t( Token::T_WHITESPACE, 8, 6 ),
					self::nt( Token::T_NUMBER, 9, 1, 120, '12E+01', 'number' ),
					self::t( Token::T_WHITESPACE, 9, 7 ),
					self::nt( Token::T_DIMENSION, 10, 1, 0.1, '.1', 'number', 'ex' ),
					self::t( Token::T_WHITESPACE, 10, 5 ),
					self::t( Token::T_DELIM, 11, 1, '+' ),
					self::t( Token::T_WHITESPACE, 11, 2 ),
					self::t( Token::T_DELIM, 12, 1, '.' ),
					self::t( Token::T_IDENT, 12, 2, 'x' ),
					self::t( Token::T_WHITESPACE, 12, 3 ),
					self::t( Token::T_EOF, 13, 1 ),
				]
			],

			'dash' => [
				"-x-yz\n-->\n--->\n-?",
				[
					self::t( Token::T_IDENT, 1, 1, '-x-yz' ),
					self::t( Token::T_WHITESPACE, 1, 6 ),
					self::t( Token::T_CDC, 2, 1 ),
					self::t( Token::T_WHITESPACE, 2, 4 ),
					self::t( Token::T_IDENT, 3, 1, '---' ),
					self::t( Token::T_DELIM, 3, 4, '>' ),
					self::t( Token::T_WHITESPACE, 3, 5 ),
					self::t( Token::T_DELIM, 4, 1, '-' ),
					self::t( Token::T_DELIM, 4, 2, '?' ),
					self::t( Token::T_EOF, 4, 3 ),
				]
			],

			'comment' => [
				"a/* foo \n bar * /* \*/b */",
				[
					self::t( Token::T_IDENT, 1, 1, 'a' ),
					self::t( Token::T_IDENT, 2, 14, 'b' ),
					self::t( Token::T_WHITESPACE, 2, 15 ),
					self::t( Token::T_DELIM, 2, 16, '*' ),
					self::t( Token::T_DELIM, 2, 17, '/' ),
					self::t( Token::T_EOF, 2, 18 ),
				]
			],
			'comment to EOF' => [
				"/* foo \n bar * /* / ?",
				[
					self::t( Token::T_EOF, 2, 14 ),
					[ [ 'unclosed-comment', 1, 1 ] ],
				]
			],
			'comment dividing an ident' => [
				'foo/**/bar',
				[
					self::t( Token::T_IDENT, 1, 1, 'foo' ),
					self::t( Token::T_IDENT, 1, 8, 'bar' ),
					self::t( Token::T_EOF, 1, 11 ),
				]
			],

			'less-than' => [
				"<!-- <3",
				[
					self::t( Token::T_CDO, 1, 1 ),
					self::t( Token::T_WHITESPACE, 1, 5 ),
					self::t( Token::T_DELIM, 1, 6, '<' ),
					self::nt( Token::T_NUMBER, 1, 7, 3, '3', 'integer' ),
					self::t( Token::T_EOF, 1, 8 ),
				]
			],

			'at' => [
				'@!foo @abc @-foo-bar @\21 foo',
				[
					self::t( Token::T_DELIM, 1, 1, '@' ),
					self::t( Token::T_DELIM, 1, 2, '!' ),
					self::t( Token::T_IDENT, 1, 3, 'foo' ),
					self::t( Token::T_WHITESPACE, 1, 6 ),
					self::t( Token::T_AT_KEYWORD, 1, 7, 'abc' ),
					self::t( Token::T_WHITESPACE, 1, 11 ),
					self::t( Token::T_AT_KEYWORD, 1, 12, '-foo-bar' ),
					self::t( Token::T_WHITESPACE, 1, 21 ),
					self::t( Token::T_AT_KEYWORD, 1, 22, '!foo' ),
					self::t( Token::T_EOF, 1, 30 ),
				]
			],

			'backslash' => [
				"\\10abcde \\<\\> \\\nok",
				[
					self::t( Token::T_IDENT, 1, 1, "\xf4\x8a\xaf\x8de" ),
					self::t( Token::T_WHITESPACE, 1, 9 ),
					self::t( Token::T_IDENT, 1, 10, '<>' ),
					self::t( Token::T_WHITESPACE, 1, 14 ),
					self::t( Token::T_DELIM, 1, 15, '\\' ),
					[ [ 'bad-escape', 1, 15 ] ],
					self::t( Token::T_WHITESPACE, 1, 16 ),
					self::t( Token::T_IDENT, 2, 1, 'ok' ),
					self::t( Token::T_EOF, 2, 3 ),
				]
			],

			'unicode range' => [
				'U+12-FdDd U+10????? u+0-f u+98- 120 U+???-abcd U-123 U+x U+200-100 U+FFFFFF',
				[
					self::t( Token::T_UNICODE_RANGE, 1, 1, '', [ 'start' => 0x12, 'end' => 0xfddd ] ),
					self::t( Token::T_WHITESPACE, 1, 10 ),
					self::t( Token::T_UNICODE_RANGE, 1, 11, '', [ 'start' => 0x100000, 'end' => 0x10ffff ] ),
					self::t( Token::T_DELIM, 1, 19, '?' ),
					self::t( Token::T_WHITESPACE, 1, 20 ),
					self::t( Token::T_UNICODE_RANGE, 1, 21, '', [ 'start' => 0, 'end' => 0xf ] ),
					self::t( Token::T_WHITESPACE, 1, 26 ),
					self::t( Token::T_UNICODE_RANGE, 1, 27, '', [ 'start' => 0x98, 'end' => 0x98 ] ),
					self::t( Token::T_DELIM, 1, 31, '-' ),
					self::t( Token::T_WHITESPACE, 1, 32 ),
					self::nt( Token::T_NUMBER, 1, 33, 120, '120', 'integer' ),
					self::t( Token::T_WHITESPACE, 1, 36 ),
					self::t( Token::T_UNICODE_RANGE, 1, 37, '', [ 'start' => 0x000, 'end' => 0xfff ] ),
					self::t( Token::T_IDENT, 1, 42, '-abcd' ),
					self::t( Token::T_WHITESPACE, 1, 47 ),
					self::t( Token::T_IDENT, 1, 48, 'U-123' ),
					self::t( Token::T_WHITESPACE, 1, 53 ),
					self::t( Token::T_IDENT, 1, 54, 'U' ),
					self::t( Token::T_DELIM, 1, 55, '+' ),
					self::t( Token::T_IDENT, 1, 56, 'x' ),
					self::t( Token::T_WHITESPACE, 1, 57 ),
					self::t( Token::T_UNICODE_RANGE, 1, 58, '', [ 'start' => 0x200, 'end' => 0x100 ] ),
					self::t( Token::T_WHITESPACE, 1, 67 ),
					self::t( Token::T_UNICODE_RANGE, 1, 68, '', [ 'start' => 0xffffff, 'end' => 0xffffff ] ),
					self::t( Token::T_EOF, 1, 76 ),
				]
			],

			'identifier with escape at EOF' => [
				'foo\\',
				[
					self::t( Token::T_IDENT, 1, 1, 'foo�' ),
					[ [ 'bad-escape', 1, 4 ] ],
					self::t( Token::T_EOF, 1, 5 ),
				]
			],

			'url token' => [
				'url( http://example.com/ )',
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/' ),
					self::t( Token::T_EOF, 1, 27 ),
				]
			],
			'url token 2' => [
				'Url(http://example.com/)',
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/' ),
					self::t( Token::T_EOF, 1, 25 ),
				]
			],
			'url token 3' => [
				"urL(\n   http://example.com/\n   )",
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/' ),
					self::t( Token::T_EOF, 3, 5 ),
				]
			],
			'url token at EOF' => [
				'url(',
				[
					self::t( Token::T_URL, 1, 1, '' ),
					[ [ 'unclosed-url', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 5 ),
				]
			],
			'url token at EOF with whitespace' => [
				'url( ',
				[
					self::t( Token::T_URL, 1, 1, '' ),
					[ [ 'unclosed-url', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 6 ),
				]
			],
			'url token with URL at EOF' => [
				"url( http://example.com/",
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/' ),
					[ [ 'unclosed-url', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 25 ),
				]
			],
			'url token with URL at EOF with whitespace' => [
				"url( http://example.com/ ",
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/' ),
					[ [ 'unclosed-url', 1, 1 ] ],
					self::t( Token::T_EOF, 1, 26 ),
				]
			],
			'unclosed url token' => [
				"url( http://example.com/ xyz\na\\)bc)def",
				[
					self::t( Token::T_BAD_URL, 1, 1 ),
					self::t( Token::T_IDENT, 2, 7, 'def' ),
					self::t( Token::T_EOF, 2, 10 ),
				]
			],
			'bad character in url' => [
				"url( http://example.com/'xyz )",
				[
					self::t( Token::T_BAD_URL, 1, 1 ),
					[ [ 'bad-character-in-url', 1, 25 ] ],
					self::t( Token::T_EOF, 1, 31 ),
				]
			],
			'bad character (non-printable) in url' => [
				"url( http://example.com/\x1fxyz )",
				[
					self::t( Token::T_BAD_URL, 1, 1 ),
					[ [ 'bad-character-in-url', 1, 25 ] ],
					self::t( Token::T_EOF, 1, 31 ),
				]
			],
			'escape in url' => [
				"url( http://example.com/\\'xyz )",
				[
					self::t( Token::T_URL, 1, 1, 'http://example.com/\'xyz' ),
					self::t( Token::T_EOF, 1, 32 ),
				]
			],
			'bad escape in url' => [
				"url( http://example.com/\\\nxyz )x",
				[
					self::t( Token::T_BAD_URL, 1, 1 ),
					[ [ 'bad-escape', 1, 25 ] ],
					self::t( Token::T_IDENT, 2, 6, 'x' ),
					self::t( Token::T_EOF, 2, 7 ),
				]
			],

			'url function' => [
				'url("http://example.com/()")',
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_STRING, 1, 5, 'http://example.com/()' ),
					self::t( Token::T_RIGHT_PAREN, 1, 28 ),
					self::t( Token::T_EOF, 1, 29 ),
				]
			],
			'url function 2' => [
				"url(\n'http://example.com/'\n)",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_WHITESPACE, 1, 5 ),
					self::t( Token::T_STRING, 2, 1, 'http://example.com/' ),
					self::t( Token::T_WHITESPACE, 2, 22 ),
					self::t( Token::T_RIGHT_PAREN, 3, 1 ),
					self::t( Token::T_EOF, 3, 2 ),
				]
			],
			'url function 3' => [
				"url(' http://example.com/')",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_STRING, 1, 5, ' http://example.com/' ),
					self::t( Token::T_RIGHT_PAREN, 1, 27 ),
					self::t( Token::T_EOF, 1, 28 ),
				]
			],
			'url function 4' => [
				"url(    'http://example.com/')",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_WHITESPACE, 1, 8 ),
					self::t( Token::T_STRING, 1, 9, 'http://example.com/' ),
					self::t( Token::T_RIGHT_PAREN, 1, 30 ),
					self::t( Token::T_EOF, 1, 31 ),
				]
			],
			'url function with a bad string' => [
				"url('http://example.com/\n')x",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_BAD_STRING, 1, 5 ),
					[ [ 'newline-in-string', 1, 25 ] ],
					self::t( Token::T_WHITESPACE, 1, 25 ),
					self::t( Token::T_STRING, 2, 1, ')x' ),
					[ [ 'unclosed-string', 2, 1 ] ],
					self::t( Token::T_EOF, 2, 4 ),
				]
			],
			'unterminated url function' => [
				"url('http://example.com/'",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_STRING, 1, 5, 'http://example.com/' ),
					self::t( Token::T_EOF, 1, 26 ),
				]
			],
			'unclosed url function' => [
				"url('http://example.com/' x",
				[
					self::t( Token::T_FUNCTION, 1, 1, 'url' ),
					self::t( Token::T_STRING, 1, 5, 'http://example.com/' ),
					self::t( Token::T_WHITESPACE, 1, 26 ),
					self::t( Token::T_IDENT, 1, 27, 'x' ),
					self::t( Token::T_EOF, 1, 28 ),
				]
			],

			'function token' => [
				'foobar( 123 )',
				[
					self::t( Token::T_FUNCTION, 1, 1, 'foobar' ),
					self::t( Token::T_WHITESPACE, 1, 8 ),
					self::nt( Token::T_NUMBER, 1, 9, 123, '123', 'integer' ),
					self::t( Token::T_WHITESPACE, 1, 12 ),
					self::t( Token::T_RIGHT_PAREN, 1, 13 ),
					self::t( Token::T_EOF, 1, 14 ),
				]
			],
		];
	}

}

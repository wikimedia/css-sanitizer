<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Parser;

use InvalidArgumentException;

/**
 * @covers \Wikimedia\CSS\Parser\StringDataSource
 */
class StringDataSourceTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideUtf8Detection
	 */
	public function testUtf8Detection( $string ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( '$string is not valid UTF-8' );
		// @phan-suppress-next-line PhanNoopNew
		new StringDataSource( $string );
	}

	public static function provideUtf8Detection() {
		return [
			'Truncated UTF-8' => [ "foo\xc2" ],
			'Bad sequence' => [ "foo\xc2bar" ],
			'Bad sequence 2' => [ "foo\x80bar" ],
			'Overlong sequence' => [ "foo\xc0\xa0bar" ],
			'Overlong sequence 2' => [ "foo\xc1\xbfbar" ],
			'Overlong sequence 3' => [ "foo\xe0\x9f\xbfbar" ],
			'Overlong sequence 4' => [ "foo\xf0\x8f\xbf\xbfbar" ],
			'Out of range codepoint' => [ "foo\xf4\x90\x80\x80bar" ],
		];
	}

	public function testOperation() {
		$source = new StringDataSource( "foo bár \x00 ♔ \xf0\x9f\x92\xa9" );

		$this->assertSame( 'f', $source->readCharacter() );
		$this->assertSame( 'o', $source->readCharacter() );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$this->assertSame( 'o', $source->readCharacter() );
		$this->assertSame( ' ', $source->readCharacter() );
		$source->putBackCharacter( 'X' );
		$this->assertSame( 'X', $source->readCharacter() );
		$this->assertSame( 'b', $source->readCharacter() );
		$this->assertSame( 'á', $source->readCharacter() );
		$this->assertSame( 'r', $source->readCharacter() );
		$this->assertSame( ' ', $source->readCharacter() );
		$this->assertSame( "\x00", $source->readCharacter() );
		$this->assertSame( ' ', $source->readCharacter() );
		$this->assertSame( '♔', $source->readCharacter() );
		$this->assertSame( ' ', $source->readCharacter() );
		$this->assertSame( "\xf0\x9f\x92\xa9", $source->readCharacter() );
		$this->assertSame( DataSource::EOF, $source->readCharacter() );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$this->assertSame( DataSource::EOF, $source->readCharacter() );
		$source->putBackCharacter( "\xe2\x98\x83" );
		$source->putBackCharacter( DataSource::EOF );
		$source->putBackCharacter( '!' );
		$source->putBackCharacter( '?' );
		$this->assertSame( '?', $source->readCharacter() );
		$this->assertSame( '!', $source->readCharacter() );
		$this->assertSame( "\xe2\x98\x83", $source->readCharacter() );
		$this->assertSame( DataSource::EOF, $source->readCharacter() );

		// Make sure empty string works right
		$source = new StringDataSource( '' );
		$this->assertSame( DataSource::EOF, $source->readCharacter() );

		// Test that the first and last character for 1-byte, 2-byte, 3-byte,
		// and 4-byte encodings are accepted for sanity's sake
		$source = new StringDataSource(
			"\x00\x7f\xc2\x80\xdf\xbf\xe0\xa0\x80\xef\xbf\xbf\xf0\x90\x80\x80\xf4\x8f\xbf\xbf"
		);
		$this->assertSame( 0, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x7f, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x80, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x7ff, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x800, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0xffff, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x10000, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( 0x10ffff, \UtfNormal\Utils::utf8ToCodepoint( $source->readCharacter() ) );
		$this->assertSame( DataSource::EOF, $source->readCharacter() );
	}
}

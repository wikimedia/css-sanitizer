<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Parser;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Wikimedia\CSS\Parser\Encoder
 */
class EncoderTest extends TestCase {

	/**
	 * @dataProvider provideConversion
	 *
	 * @param string $text
	 * @param string|Exception $expect
	 * @param array $encodings
	 */
	public function testConversion( $text, $expect, $encodings = [] ) {
		if ( $expect instanceof Exception ) {
			$this->expectException( get_class( $expect ) );
			$this->expectExceptionMessage( $expect->getMessage() );
		}
		$output = Encoder::convert( $text, $encodings );
		if ( !$expect instanceof Exception ) {
			$this->assertSame( $expect, $output );
		}
	}

	public static function provideConversion() {
		$z = "\0";
		$txt = "@charset \"windows-874\"; f\xffo";
		return [
			'Sanity check #1' => [ '@charset "iso-2022-cn";', '�' ],

			'UTF-8 with BOM' => [
				"\xef\xbb\xbf@charset \"iso-2022-cn\";",
				"@charset \"iso-2022-cn\";",
				[ 'transport' => 'iso-2022-cn', 'environment' => 'iso-2022-cn' ],
			],
			'non-UTF-8 with UTF-8 BOM' => [
				"\xef\xbb\xbf@charset \"iso-2022-cn\"; f\xf3o",
				"@charset \"iso-2022-cn\"; f�o",
				[ 'transport' => 'iso-2022-cn', 'environment' => 'iso-2022-cn' ],
			],
			'UTF-16BE with BOM' => [
				"\xfe\xff\0@\0c\0h\0a\0r\0s\0e\0t\0 \0\"\0i\0s\0o\0-{$z}2{$z}0{$z}2{$z}2\0-\0c\0n\0\"\0;",
				"@charset \"iso-2022-cn\";",
				[ 'transport' => 'iso-2022-cn', 'environment' => 'iso-2022-cn' ],
			],
			'UTF-16LE with BOM' => [
				"\xff\xfe@\0c\0h\0a\0r\0s\0e\0t\0 \0\"\0i\0s\0o\0-{$z}2{$z}0{$z}2{$z}2\0-\0c\0n\0\"\0;\0",
				"@charset \"iso-2022-cn\";",
				[ 'transport' => 'iso-2022-cn', 'environment' => 'iso-2022-cn' ],
			],
			'Transport encoding' => [
				'@charset "iso-2022-cn";',
				'@charset "iso-2022-cn";',
				[ 'transport' => 'utf-8', 'environment' => 'iso-2022-cn' ],
			],
			'@charset' => [
				'@charset "unicode-1-1-utf-8";',
				'@charset "unicode-1-1-utf-8";',
				[ 'transport' => 'bogus', 'environment' => 'iso-2022-cn' ],
			],
			'environment' => [
				"@charset \"piglatin1\"; f\xf3o",
				'@charset "piglatin1"; fóo',
				[ 'transport' => 'bogus', 'environment' => 'latin1' ],
			],
			'Transport encoding with whitespace' => [
				'@charset "iso-2022-cn";',
				'@charset "iso-2022-cn";',
				[ 'transport' => "\rutf-8\n", 'environment' => 'iso-2022-cn' ],
			],
			'@charset with whitespace' => [
				"@charset \"\funicode-1-1-utf-8\n\";",
				"@charset \"\funicode-1-1-utf-8\n\";",
				[ 'transport' => 'bogus', 'environment' => 'iso-2022-cn' ],
			],
			'environment with whitespace' => [
				"@charset \"piglatin1\"; f\xf3o",
				'@charset "piglatin1"; fóo',
				[ 'transport' => 'bogus', 'environment' => "\rlatin1\n" ],
			],
			'fallback to UTF-8' => [
				"@charset \"piglatin1\"; f\xf3o",
				'@charset "piglatin1"; f�o',
				[ 'transport' => 'bogus', 'environment' => 'bogus' ],
			],
			'lying @charset' => [
				"@charset \"utf-16be\"; f\xf3o",
				'@charset "utf-16be"; f�o',
				[ 'transport' => 'bogus', 'environment' => 'latin1' ],
			],
			'x-user-defined' => [
				'@charset "x-user-defined"; főo',
				"@charset \"x-user-defined\"; f\xef\x9f\x85\xef\x9e\x91o",
			],
			'iconv' => [
				"@charset \"mac\"; f\x97o",
				"@charset \"mac\"; fóo",
			],
			'iconv fail' => [
				$txt,
				new RuntimeException( "Cannot convert '$txt' from Windows-874" )
			],
		];
	}
}

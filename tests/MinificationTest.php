<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS;

use Wikimedia\CSS\Parser\Parser;
use Wikimedia\CSS\Sanitizer\StylesheetSanitizer;

/**
 * @coversNothing
 */
class MinificationTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideStylesheets
	 * @param string $input
	 * @param string|null $output
	 */
	public function testStylesheets( $input, $output ) {
		$sheet = Parser::newFromString( $input )->parseStylesheet();
		$ret = StylesheetSanitizer::newDefault()->sanitize( $sheet );
		$this->assertSame( $output, Util::stringify( $ret, [ 'minify' => true ] ) );
	}

	public static function provideStylesheets() {
		return [
			'Avoid IE border color misparsing' => [
				'.foo {
					border: 1px solid #000;
					border: solid 1px #000;
					border: solid 0 #000;
					border-color: #000 #000 #000 #000;
				}',
				// phpcs:ignore Generic.Files.LineLength
				'.foo{border:1px solid #000;border:solid 1px #000;border:solid 0 #000;border-color:#000 #000 #000 #000}',
			]
		];
	}
}

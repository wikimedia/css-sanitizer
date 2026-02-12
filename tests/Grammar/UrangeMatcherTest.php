<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\UrangeMatcher
 */
class UrangeMatcherTest extends MatcherTestBase {

	/**
	 * @dataProvider provideMatch
	 * @param string $text
	 * @param bool $match
	 * @param int $start
	 * @param int $end
	 * @param int $remaining
	 */
	public function testMatches( $text, $match, $start = 0, $end = 0, $remaining = 0 ) {
		$list = Parser::newFromString( $text )->parseComponentValueList();
		$opts = [
			'skip-whitespace' => true,
			'nonterminal' => true,
			'mark-significance' => false,
		];

		$matcher = ( new UrangeMatcher() )->capture( 'foo' );
		$matches = iterator_to_array(
			TestingAccessWrapper::newFromObject( $matcher )->generateMatches( $list, 0, $opts )
		);

		if ( !$match ) {
			$this->assertCount( 0, $matches );
			// @phan-suppress-next-line PhanUndeclaredMethod False positive
			$this->assertSame( 0, $list[0]->urangeHack() );
		} else {
			$this->assertNotCount( 0, $matches );
			$m = $matches[0];
			$this->assertSame( 0, $m->getStart() );

			$capt = [];
			foreach ( $m->getCapturedMatches() as $mm ) {
				$this->assertCount( 1, $mm->getValues() );
				$v = $mm->getValues()[0];
				$this->assertInstanceOf( Token::class, $v );
				$capt[$mm->getName()] = $v->value();
			}
			$this->assertEquals( [ 'start' => $start, 'end' => $end ], $capt );

			$len = count( $list ) - $remaining;
			$this->assertSame( $len, $m->getLength() );
			// @phan-suppress-next-line PhanUndeclaredMethod False positive
			$this->assertSame( $len, $list[0]->urangeHack() );
		}
	}

	public static function provideMatch() {
		return [
			'ident' => [ 'U+abcd', true, 0xabcd, 0xabcd ],
			'dimension' => [ 'U+12cd', true, 0x12cd, 0x12cd ],
			'dimension (2)' => [ 'U+12cd-12ef', true, 0x12cd, 0x12ef ],
			'number' => [ 'U+1234', true, 0x1234, 0x1234 ],
			'number dimension' => [ 'U+1234-12ab', true, 0x1234, 0x12ab ],
			'number number' => [ 'U+1234-1256', true, 0x1234, 0x1256 ],
			'number looking like an exponent' => [ 'U+12e4', true, 0x12e4, 0x12e4 ],
			'two numbers looking like an exponent' => [ 'U+1e-20', true, 0x1e, 0x20 ],

			'number ?s' => [ 'U+1?', true, 0x10, 0x1f ],
			'dimension ?s' => [ 'U+1a?', true, 0x1a0, 0x1af ],
			'ident ?s' => [ 'U+a?', true, 0xa0, 0xaf ],
			'2 ?s' => [ 'U+2??', true, 0x200, 0x2ff ],
			'3 ?s' => [ 'U+3???', true, 0x3000, 0x3fff ],
			'4 ?s' => [ 'U+4????', true, 0x40000, 0x4ffff ],
			'5 ?s' => [ 'U+0?????', true, 0, 0xfffff ],
			'5 ?s, out-of-range' => [ 'U+2?????', true, 0x20000, 0x2ffff, 1 ],
			'4 ?s, out-of-range' => [ 'U+10????', true, 0x100000, 0x10ffff ],
			'4 ?s, out-of-range (2)' => [ 'U+11????', true, 0x11000, 0x11fff, 1 ],

			// These two are odd, but the spec calls for it
			'6 ?s (is out-of-range)' => [ 'U+??????', true, 0, 0xfffff, 1 ],
			'5 ?s (is out-of-range)' => [ 'U+1?????', true, 0x10000, 0x1ffff, 1 ],

			'mixed case' => [ 'u+12-FdDd', true, 0x12, 0xfddd ],
			'excess ?s' => [ 'u+10?????', true, 0x100000, 0x10ffff, 1 ],
			'minimal numbers' => [ 'u+0-f', true, 0, 0xf ],
			'trailing -' => [ 'u+98-', true, 0x98, 0x98, 1 ],
			'Trying to do ?s and range' => [ 'u+???-abcd', true, 0, 0xfff, 1 ],
			'No +' => [ 'u-123', false ],
			'No + (2)' => [ 'u/**/-123', false ],
			'Bad digit' => [ 'U+x', false ],
			'Bad digit (2)' => [ 'U+1x', false ],
			'Out of order range' => [ 'U+200-100', true, 0x200, 0x200, 1 ],
			'Out of range ident' => [ 'U+FFFFFF', false ],
			'Out of range number' => [ 'U+222222', false ],
			'Out of range dimension' => [ 'U+11ffff', false ],
			'Space' => [ 'U+ 123', false ],
			'Space breaking range' => [ 'U+1234 -1256', true, 0x1234, 0x1234, 1 ],

			// Spec calls for this too
			'Comment doesn\'t break it up' => [ 'U/**/+123/**/-456', true, 0x123, 0x456 ],
		];
	}
}

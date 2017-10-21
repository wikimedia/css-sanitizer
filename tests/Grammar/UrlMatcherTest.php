<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\CSSFunction;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\UrlMatcher
 */
class UrlMatcherTest extends MatcherTestBase {

	/**
	 * @dataProvider provideMatch
	 * @param ComponentValue[] $values
	 * @param bool $useCB
	 * @param bool $useModifiers
	 * @param string|null $exUrl
	 * @param ComponentValue[] $exMods
	 * @param bool $match
	 */
	public function testMatch( $values, $useCB, $useModifiers, $exUrl, $exMods, $match ) {
		$called = null;

		$cb = $useCB ? function ( $url, $mods ) use ( $exUrl, $exMods, $match, &$called ) {
			$called = $url;
			$this->assertSame( $exUrl, $url );
			$this->assertEquals( $exMods, $mods );
			return $match;
		} : null;
		$options = $useModifiers ? [ 'modifierMatcher' => UrlMatcher::anyModifierMatcher() ] : [];
		$m = TestingAccessWrapper::newFromObject( new UrlMatcher( $cb, $options ) );

		$list = new ComponentValueList( $values );
		$opts = [ 'skip-whitespace' => true ];
		$this->assertCount( $match ? 1 : 0, iterator_to_array( $m->generateMatches( $list, 0, $opts ) ) );
		$this->assertSame( $exUrl, $called );
	}

	public static function provideMatch() {
		$url = 'http://example.com/foo.jpg';
		$ws = new Token( Token::T_WHITESPACE );
		$urlStr = new Token( Token::T_STRING, $url );
		$urlTok = new Token( Token::T_URL, $url );
		$ident = new Token( Token::T_IDENT, 'foo' );
		$rp = new Token( Token::T_RIGHT_PAREN );
		$func = CSSFunction::newFromName( 'func' );
		$func->getValue()->add( [ $ident, $ident ] );
		$urlFunc1 = CSSFunction::newFromName( 'url' );
		$urlFunc1->getValue()->add( $urlStr );
		$urlFunc2 = CSSFunction::newFromName( 'url' );
		$urlFunc2->getValue()->add( [ $ws, $urlStr, $ws, $ident, $ws, $func, $ws ] );
		$modifiers = [ $ident, $func ];

		return [
			[ [ $urlStr ], true, false, null, [], false ],
			[ [ $urlTok ], true, false, $url, [], true ],
			[ [ $urlTok ], true, false, $url, [], false ],
			[ [ $urlTok ], false, false, null, [], true ],
			[ [ $urlFunc1 ], true, false, $url, [], true ],
			[ [ $urlFunc1 ], true, false, $url, [], false ],
			[ [ $urlFunc1 ], false, false, null, [], true ],
			[ [ $urlFunc2 ], true, false, null, [], false ],
			[ [ $urlFunc2 ], true, true, $url, $modifiers, true ],
			[ [ $urlFunc2 ], true, true, $url, $modifiers, false ],
			[ [ $urlFunc2 ], false, true, null, [], true ],
		];
	}
}

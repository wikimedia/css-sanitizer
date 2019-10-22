<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\AnythingMatcher;
use Wikimedia\CSS\Objects\AtRule;
use Wikimedia\CSS\Objects\ComponentValue;
use Wikimedia\CSS\Objects\CSSFunction;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Sanitizer\RuleSanitizer
 */
class RuleSanitizerTest extends RuleSanitizerTestBase {

	protected function getSanitizer( $options = [] ) {
		$mb = $this->getMockBuilder( RuleSanitizer::class )
			->setMethods( [ 'doSanitize', 'handlesRule' ] );
		$san = $mb->getMockForAbstractClass();
		$san->method( 'handlesRule' )->willReturn( true );

		switch ( $options[0] ?? '' ) {
			case 'declarations':
				$method = 'sanitizeDeclarationBlock';
				$arg = new PropertySanitizer( [ 'foo' => new AnythingMatcher ] );
				break;

			case 'rules':
				$method = 'sanitizeRuleBlock';
				$arg = [ $mb->getMockForAbstractClass() ];
				$arg[0]->method( 'handlesRule' )->willReturnCallback( function ( $rule ) {
					return $rule instanceof AtRule && !strcasecmp( $rule->getName(), 'foo' );
				} );
				$arg[0]->method( 'doSanitize' )->willReturnArgument( 0 );
				break;

			default:
				$method = null;
				break;
		}
		if ( $method ) {
			$rm = new \ReflectionMethod( $san, $method );
			$rm->setAccessible( true );
			$san->method( 'doSanitize' )->willReturnCallback( function ( $rule ) use ( $rm, $san, $arg ) {
				$ret = clone $rule;
				$rm->invoke( $san, $ret->getBlock(), $arg );
				return $ret;
			} );
		}

		return $san;
	}

	/**
	 * @dataProvider provideFixPreludeWhitespace
	 * @param ComponentValue[] $input
	 * @param bool $cloneIfNecessary
	 * @param ComponentValue[] $expect
	 * @param bool $cloned
	 */
	public function testFixPreludeWhitespace( $input, $cloneIfNecessary, $expect, $cloned ) {
		$inRule = AtRule::newFromName( 'foo' );
		$inRule->getPrelude()->add( $input );
		$expectRule = AtRule::newFromName( 'foo' );
		$expectRule->getPrelude()->add( $expect );

		$outRule = TestingAccessWrapper::newFromObject( $this->getSanitizer() )
			->fixPreludeWhitespace( $inRule, $cloneIfNecessary );

		if ( $cloned ) {
			$this->assertNotSame( $inRule, $outRule );
		} else {
			$this->assertSame( $inRule, $outRule );
		}

		// So the 'offset' doesn't screw things up
		$outRule->getPrelude()->rewind();
		$expectRule->getPrelude()->rewind();

		$this->assertEquals( $expectRule, $outRule );
	}

	public static function provideFixPreludeWhitespace() {
		$ws = new Token( Token::T_WHITESPACE );
		$Iws = $ws->copyWithSignificance( false );
		$colon = new Token( Token::T_COLON );
		$ident = new Token( Token::T_IDENT, 'bar' );
		$block = SimpleBlock::newFromDelimiter( '(' );
		$func = CSSFunction::newFromName( 'func' );

		return [
			'empty' => [ [], true, [], false ],
			'all ws' => [ [ $ws, $ws ], true, [ $Iws, $Iws ], false ],
			'ws colon' => [ [ $ws, $colon ], true, [ $Iws, $colon ], false ],
			'iws ident' => [ [ $Iws, $ident ], true, [ $ws, $ident ], false ],
			'ws block' => [ [ $ws, $block ], true, [ $Iws, $block ], false ],
			'iws func' => [ [ $Iws, $func ], true, [ $ws, $func ], false ],
			'colon' => [ [ $colon ], true, [ $colon ], false ],
			'ident' => [ [ $ident ], true, [ $ws, $ident ], true ],
			'ident, no clone' => [ [ $ident ], false, [ $ws, $ident ], false ],
			'block' => [ [ $block ], true, [ $block ], false ],
			'func' => [ [ $func ], true, [ $ws, $func ], true ],
			'func, no clone' => [ [ $func ], false, [ $ws, $func ], false ],
		];
	}

	public static function provideRules() {
		return [
			'declaration block' => [
				'{ foo: bar; bar: baz; }',
				true,
				'{ foo: bar; }',
				'{foo:bar}',
				[ [ 'unrecognized-property', 1, 13 ] ],
				[ 'declarations' ]
			],
			'rule block' => [
				'{ @foo; @bar; }',
				true,
				'{ @foo; }',
				'{@foo;}',
				[ [ 'unrecognized-rule', 1, 9 ] ],
				[ 'rules' ]
			],
		];
	}
}

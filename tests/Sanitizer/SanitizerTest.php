<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use PHPUnit\Framework\TestCase;
use Wikimedia\CSS\Objects\AtRule;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\RuleList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Sanitizer\Sanitizer
 */
class SanitizerTest extends TestCase {

	public function testErrors() {
		$sanitizer = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Sanitizer::class )
		);

		$this->assertSame( [], $sanitizer->getSanitizationErrors() );
		$sanitizer->sanitizationError(
			'foobar', new Token( Token::T_WHITESPACE, [ 'position' => [ 42, 23 ] ] )
		);
		$sanitizer->sanitizationError(
			'baz', new Token( Token::T_WHITESPACE, [ 'position' => [ 1, 2 ] ] )
		);
		$this->assertSame(
			[ [ 'foobar', 42, 23 ], [ 'baz', 1, 2 ] ],
			$sanitizer->getSanitizationErrors()
		);
		$sanitizer->clearSanitizationErrors();
		$this->assertSame( [], $sanitizer->getSanitizationErrors() );
	}

	public function testStashErrors() {
		$sanitizer = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Sanitizer::class )
		);

		$this->assertSame( [], $sanitizer->getSanitizationErrors() );
		$sanitizer->sanitizationError(
			'foobar', new Token( Token::T_WHITESPACE, [ 'position' => [ 42, 23 ] ] )
		);
		$sanitizer->sanitizationError(
			'baz', new Token( Token::T_WHITESPACE, [ 'position' => [ 1, 2 ] ] )
		);
		$this->assertSame(
			[ [ 'foobar', 42, 23 ], [ 'baz', 1, 2 ] ],
			$sanitizer->getSanitizationErrors()
		);

		$reset = $sanitizer->stashSanitizationErrors();
		$this->assertSame( [], $sanitizer->getSanitizationErrors() );
		$sanitizer->sanitizationError(
			'xyz', new Token( Token::T_WHITESPACE, [ 'position' => [ 1, 2 ] ] )
		);
		$this->assertSame(
			[ [ 'xyz', 1, 2 ] ],
			$sanitizer->getSanitizationErrors()
		);

		unset( $reset );
		$this->assertSame(
			[ [ 'foobar', 42, 23 ], [ 'baz', 1, 2 ] ],
			$sanitizer->getSanitizationErrors()
		);
	}

	public function testSanitize() {
		$ws = new Token( Token::T_WHITESPACE );
		$block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );
		$block->getValue()->add( $ws );
		$block2 = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );

		$mb = $this->getMockBuilder( Sanitizer::class )->onlyMethods( [ 'doSanitize' ] );

		$san = $mb->getMockForAbstractClass();
		$san->expects( $this->once() )->method( 'doSanitize' )
			// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal False positive
			->with( $this->identicalTo( $block ) )
			->willReturn( null );
		'@phan-var Sanitizer $san';
		$this->assertNull( $san->sanitize( $block ) );

		$san = $mb->getMockForAbstractClass();
		$san->expects( $this->once() )->method( 'doSanitize' )
			// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal False positive
			->with( $this->identicalTo( $block ) )
			->willReturn( $block2 );
		'@phan-var Sanitizer $san';
		$this->assertSame( $block2, $san->sanitize( $block ) );

		$san = $mb->getMockForAbstractClass();
		$san->expects( $this->once() )->method( 'doSanitize' )
			// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal False positive
			->with( $this->identicalTo( $block ) )
			->willReturn( $block );
		'@phan-var Sanitizer $san';
		$this->assertSame( $block, $san->sanitize( $block ) );
	}

	public function testSanitizeObj() {
		$token1 = new Token( Token::T_WHITESPACE );
		$token2 = new Token( Token::T_COMMA );

		$sanitizer1 = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Sanitizer::class )
		);
		$sanitizer1->sanitizationErrors = [ [ 'x', 1, 2 ] ];

		$sanitizer2 = $this->getMockBuilder( Sanitizer::class )
			->onlyMethods( [ 'doSanitize', 'getSanitizationErrors', 'clearSanitizationErrors' ] )
			->getMockForAbstractClass();
		$sanitizer2->expects( $this->once() )->method( 'doSanitize' )
			// @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal False positive
			->with( $this->identicalTo( $token1 ) )
			->willReturn( $token2 );
		$sanitizer2->expects( $this->once() )->method( 'getSanitizationErrors' )
			->willReturn( [ [ 'foo', 42, 23 ] ] );
		$sanitizer2->expects( $this->once() )->method( 'clearSanitizationErrors' );

		$this->assertSame( $token2, $sanitizer1->sanitizeObj( $sanitizer2, $token1 ) );
		$this->assertSame( [ [ 'x', 1, 2 ], [ 'foo', 42, 23 ] ], $sanitizer1->getSanitizationErrors() );
	}

	public function testSanitizeList() {
		$token1i = new Token( Token::T_WHITESPACE );
		$token1o = new Token( Token::T_COMMA );
		$token2i = new Token( Token::T_RIGHT_PAREN );
		$token2o = null;
		$token3i = new Token( Token::T_COLON );
		$token3o = new Token( Token::T_SEMICOLON );

		$sanitizer1 = TestingAccessWrapper::newFromObject(
			$this->getMockForAbstractClass( Sanitizer::class )
		);
		$sanitizer1->sanitizationErrors = [ [ 'x', 1, 2 ] ];

		$sanitizer2 = $this->getMockBuilder( Sanitizer::class )
			->onlyMethods( [ 'doSanitize', 'getSanitizationErrors', 'clearSanitizationErrors' ] )
			->getMock();
		$sanitizer2->expects( $this->exactly( 3 ) )->method( 'doSanitize' )
			->willReturnMap( [
				[ $token1i, $token1o ],
				[ $token2i, $token2o ],
				[ $token3i, $token3o ],
			] );
		$sanitizer2->expects( $this->once() )->method( 'getSanitizationErrors' )
			->willReturn( [ [ 'foo', 42, 23 ] ] );

		$list = new ComponentValueList( [ $token1i, $token2i, $token3i ] );
		$ret = $sanitizer1->sanitizeList( $sanitizer2, $list );
		$this->assertInstanceOf( ComponentValueList::class, $ret );
		$this->assertSame( [ $token1o, $token3o ], iterator_to_array( $ret ) );
		$this->assertSame( [ [ 'x', 1, 2 ], [ 'foo', 42, 23 ] ], $sanitizer1->getSanitizationErrors() );
	}

	public function testSanitizeRules() {
		$mb = $this->getMockBuilder( RuleSanitizer::class )
			->onlyMethods( [ 'handlesRule', 'getIndex', 'doSanitize' ] );

		$san1 = $mb->getMockForAbstractClass();
		$san1->method( 'handlesRule' )->willReturnCallback( static function ( $rule ) {
			return $rule->getName() === 'san1';
		} );
		$san1->method( 'getIndex' )->willReturnCallback( static function () {
			return [ 1, 2 ];
		} );
		$san1->method( 'doSanitize' )->willReturnCallback( function ( $rule ) {
			$this->assertSame( 'san1', $rule->getName() );
			return $rule;
		} );

		$san2 = $mb->getMockForAbstractClass();
		$san2->method( 'handlesRule' )->willReturnCallback( static function ( $rule ) {
			return $rule->getName() === 'san2';
		} );
		$san2->method( 'getIndex' )->willReturnCallback( static function () {
			return 2;
		} );
		$san2->method( 'doSanitize' )->willReturnCallback( function ( $rule ) {
			$this->assertSame( 'san2', $rule->getName() );
			return $rule;
		} );

		$san3 = $mb->getMockForAbstractClass();
		$san3->method( 'handlesRule' )->willReturnCallback( static function ( $rule ) {
			return $rule->getName() === 'san3';
		} );
		$san3->method( 'getIndex' )->willReturnCallback( static function () {
			return 2;
		} );
		$san3->method( 'doSanitize' )->willReturnCallback( function ( $rule ) {
			$this->assertSame( 'san3', $rule->getName() );
			return null;
		} );

		$sanX = $mb->getMockForAbstractClass();
		$sanX->method( 'handlesRule' )->willReturnCallback( static function ( $rule ) {
			return $rule->getName() === 'san2';
		} );
		$sanX->expects( $this->never() )->method( 'getIndex' );
		$sanX->expects( $this->never() )->method( 'doSanitize' );

		$AT = Token::T_AT_KEYWORD;
		$r1 = new AtRule( new Token( $AT, [ 'value' => 'san1', 'position' => [ 1, 1 ] ] ) );
		$r2 = new AtRule( new Token( $AT, [ 'value' => 'san1', 'position' => [ 2, 1 ] ] ) );
		$r3 = new AtRule( new Token( $AT, [ 'value' => 'san2', 'position' => [ 3, 1 ] ] ) );
		$r4 = new AtRule( new Token( $AT, [ 'value' => 'san2', 'position' => [ 4, 1 ] ] ) );
		$r5 = new AtRule( new Token( $AT, [ 'value' => 'san3', 'position' => [ 5, 1 ] ] ) );
		$r6 = new AtRule( new Token( $AT, [ 'value' => 'san2', 'position' => [ 6, 1 ] ] ) );
		$r7 = new AtRule( new Token( $AT, [ 'value' => 'san4', 'position' => [ 7, 1 ] ] ) );
		$r8 = new AtRule( new Token( $AT, [ 'value' => 'san2', 'position' => [ 8, 1 ] ] ) );
		$test = new RuleList( [ $r1, $r2, $r3, $r4, $r5, $r6, $r7, $r8 ] );

		$san = TestingAccessWrapper::newFromObject( $this->getMockForAbstractClass( Sanitizer::class ) );
		$ret = $san->sanitizeRules( [ $san1, $san2, $san3, $sanX ], $test );
		$this->assertInstanceOf( RuleList::class, $ret );
		$this->assertSame( [ $r1, $r3, $r4, $r6, $r8 ], iterator_to_array( $ret ) );
		$this->assertSame( [
			[ 'misordered-rule', 2, 1 ],
			[ 'unrecognized-rule', 7, 1 ],
		], $san->getSanitizationErrors() );
	}

	/**
	 * @dataProvider provideSecurity
	 * @param string $input
	 * @param ?string $output
	 * @param array $errors
	 */
	public function testSecurity( string $input, ?string $output, array $errors = [] ) {
		$san = StylesheetSanitizer::newDefault();
		$sheet = Parser::newFromString( $input )->parseStylesheet();
		$ret = $san->sanitize( $sheet );
		$this->assertSame( $errors, $san->getSanitizationErrors() );
		if ( $output === null ) {
			$this->assertNull( $ret );
		} else {
			$this->assertNotNull( $ret );
			$this->assertSame( $output, (string)$ret );

			$sheet2 = Parser::newFromString( $output )->parseStylesheet();
			$ret2 = $san->sanitize( $sheet2 );
			$this->assertSame( $output, (string)$ret2 );
		}
	}

	public static function provideSecurity() {
		// A single custom property is allowed for `background`, but not
		// a concatenated series of them.
		yield 'Background' => [
			<<<INPUT
			* {
			  background: var(--foo);
			  background: var(--foo) var(--bar);
			}
			INPUT,
			<<<OUTPUT
			* { background:var(--foo); }
			OUTPUT,
			[
				[ 'bad-value-for-property', 3, 15, 'background' ],
			],
		];
		// `border-color` actually allows concatenated color values, so
		// we disallow custom properties here.
		yield 'Border' => [
			<<<INPUT
			.safe { border-color: red green blue white; }
			.unsafe { border-color: var(--red) var(--green) var(--blue) var(--white); }
			INPUT,
			<<<OUTPUT
			.safe { border-color:red green blue white; } .unsafe {}
			OUTPUT,
			[
				[ 'bad-value-for-property', 2, 25, 'border-color' ],
			],
		];
		// These examples are from
		// https://www.w3.org/TR/css-variables-1/
		// and confirm that custom property *definitions* are stripped
		// from the CSS, even though custom property *uses* are permitted
		// (in color-related attributes at least).
		yield 'Example 5: Basic usage (section 2)' => [
			<<<INPUT
			:root { --color: blue; }
			div { --color: green; }
			#alert { --color: red; }
			* { color: var(--color); }
			INPUT,
			<<<OUTPUT
			:root {} div {} #alert {} * { color:var(--color); }
			OUTPUT,
			[
				[ 'unrecognized-property', 1, 9 ],
				[ 'unrecognized-property', 2, 7 ],
				[ 'unrecognized-property', 3, 10 ],
			],
		];
		yield 'Shorthand properties (section 3.2)' => [
			<<<INPUT
			* { background: var(--custom); }
			INPUT,
			<<<OUTPUT
			* { background:var(--custom); }
			OUTPUT,
			[
			],
		];
		yield 'Example 16: A Billion Laughs (section 3.3)' => [
			<<<INPUT
			.foo {
			  --prop1: lol;
			  --prop2: var(--prop1) var(--prop1);
			  --prop3: var(--prop2) var(--prop2);
			  --prop4: var(--prop3) var(--prop3);
			  /* etc */
			}
			INPUT,
			<<<OUTPUT
			.foo {}
			OUTPUT,
			[
				[ 'unrecognized-property', 2, 3 ],
				[ 'unrecognized-property', 3, 3 ],
				[ 'unrecognized-property', 4, 3 ],
				[ 'unrecognized-property', 5, 3 ],
			],
		];
	}
}

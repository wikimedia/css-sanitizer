<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Grammar\MatcherFactory
 */
class MatcherFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testSingleton() {
		TestingAccessWrapper::newFromClass( MatcherFactory::class )->instance = null;
		$s1 = MatcherFactory::singleton();
		$this->assertSame( $s1, MatcherFactory::singleton() );
	}

	/**
	 * @dataProvider provideMatchers
	 * @param string|array $what Matcher factory method to call
	 * @param string $string String to parse into a ComponentValueList
	 * @param Matcher|null|bool $expect Whether/what the matcher should match
	 */
	public function testMatchers( $what, $string, $expect = true ) {
		if ( $what === 'url test' ) {
			$matcher = TestMatcherFactory::singleton()->url( 'dummy' );
		} elseif ( $what === 'url' || $what === 'urlstring' ) {
			$matcher = MatcherFactory::singleton()->$what( 'dummy' );
		} elseif ( $what === 'cssMediaQuery unstrict' ) {
			$matcher = MatcherFactory::singleton()->cssMediaQuery( false );
		} elseif ( is_array( $what ) ) {
			$func = array_shift( $what );
			$matcher = call_user_func_array( [ MatcherFactory::singleton(), $func ], $what );
		} else {
			$matcher = MatcherFactory::singleton()->$what();
			if ( $what === 'significantWhitespace' || $what === 'optionalWhitespace' ) {
				$matcher->setDefaultOptions( [ 'skip-whitespace' => false ] );
			}
		}
		$list = Parser::newFromString( $string )->parseComponentValueList();
		$ret = $matcher->match( $list );
		if ( is_bool( $expect ) ) {
			$this->assertSame( $expect, (bool)$ret );
		} else {
			$this->assertEquals(
				MatcherTestBase::stripMatch( $expect ),
				MatcherTestBase::stripMatch( $ret )
			);
		}
	}

	public function provideMatchers() {
		$img = 'http://www.example.com/foo.jpg';
		$dummy = new ComponentValueList();

		return [
			[ 'significantWhitespace', ' /**/ ' ],
			[ 'significantWhitespace', '', null ],
			[ 'significantWhitespace', 'x', null ],

			[ 'optionalWhitespace', ' /**/ ' ],
			[ 'optionalWhitespace', '' ],
			[ 'optionalWhitespace', 'x', null ],

			[ 'comma', ',' ],
			[ 'comma', '', null ],
			[ 'comma', 'x', null ],

			[ 'ident', 'x' ],
			[ 'ident', '', null ],
			[ 'ident', '!', null ],

			[ 'customIdent', 'x' ],
			[ 'customIdent', 'default', null ],
			[ 'customIdent', 'DeFaUlT', null ],
			[ [ 'customIdent', [ 'x' ] ], 'x', null ],
			[ [ 'customIdent', [ 'x' ] ], 'X', null ],

			[ 'string', '""' ],
			[ 'string', '', null ],
			[ 'string', 'x', null ],

			[ 'urlstring', '"http://www.example.com"' ],
			[ 'urlstring', 'url("http://www.example.com")', null ],

			[ 'url', 'url(http://www.example.com)',
				new Match( $dummy, 0, 1, null, [
					new Match( $dummy, 0, 1, 'url' ),
				] )
			],
			[ 'url', 'url(http://www.example.com x)', null ],
			[ 'url', 'url( "http://www.example.com" )',
				new Match( $dummy, 0, 1, null, [
					new Match( $dummy, 1, 2, 'url' ),
				] )
			],
			[ 'url', 'url( "http://www.example.com" x )', null ],

			[ 'url test', 'url( "http://www.example.com/" )', null ],
			[ 'url test', 'url( "http://www.example.com/dummy" )' ],
			[ 'url test', 'url( "http://www.example.com/dummy" x y z(z) )',
				new Match( $dummy, 0, 1, null, [
					new Match( $dummy, 1, 2, 'url' ),
					new Match( $dummy, 3, 2, 'modifier' ),
					new Match( $dummy, 5, 2, 'modifier' ),
					new Match( $dummy, 7, 2, 'modifier' ),
				] )
			],
			[ 'url test', 'url( "http://www.example.com/dummy" x y z(?) )', null ],
			[ 'url test', 'url( "http://www.example.com/dummy" a )', null ],

			[ 'cssWideKeywords', 'initial' ],
			[ 'cssWideKeywords', 'inherit' ],
			[ 'cssWideKeywords', 'unset' ],
			[ 'cssWideKeywords', 'foo', false ],

			[ 'integer', '12' ],
			[ 'integer', '+12' ],
			[ 'integer', '-12' ],
			[ 'integer', '12.2', false ],
			[ 'integer', '1e2', false ],
			[ 'integer', 'foo', false ],
			[ 'integer', 'calc( 1 + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'integer', 'calc(1 + 2*3*(4 + 5))' ],
			[ 'integer', 'calc(1/2)', false ],
			[ 'integer', 'calc(1)' ],
			[ 'integer', 'calc()', false ],
			[ 'integer', 'calc( 1.2 )', false ],
			[ 'integer', 'calc( 1ex )', false ],
			[ 'integer', 'calc( 1 + 2 )' ],
			[ 'integer', 'calc(1 + 2)' ],
			[ 'integer', 'calc( 1+ 2 )', false ],
			[ 'integer', 'calc( 1 +2 )', false ],
			[ 'integer', 'calc(calc(1 + 2) + 3)' ],

			[ 'number', '12' ],
			[ 'number', '+12' ],
			[ 'number', '-12' ],
			[ 'number', '12.2' ],
			[ 'number', '1e2' ],
			[ 'number', 'foo', false ],
			[ 'number', 'calc( 1 + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'number', 'calc(1 + 2*3*(4 + 5))' ],
			[ 'number', 'calc(1/2)' ],
			[ 'number', 'calc(1)' ],
			[ 'number', 'calc()', false ],
			[ 'number', 'calc( 1.2 )' ],
			[ 'number', 'calc( 1ex )', false ],
			[ 'number', 'calc(calc(1 + 2.2) + 3)' ],

			[ 'percentage', '12', false ],
			[ 'percentage', '12%' ],
			[ 'percentage', '+12%' ],
			[ 'percentage', '-12%' ],
			[ 'percentage', '12.2%' ],
			[ 'percentage', '1e2%' ],
			[ 'percentage', 'foo%', false ],
			[ 'percentage', 'calc( 1% + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'percentage', 'calc(1% + 2*3*(4 + 5))' ],
			[ 'percentage', 'calc(1%/2)' ],
			[ 'percentage', 'calc(1/2%)', false ],
			[ 'percentage', 'calc(1%)' ],
			[ 'percentage', 'calc()', false ],
			[ 'percentage', 'calc( 1.2% )' ],
			[ 'percentage', 'calc( 1ex )', false ],
			[ 'percentage', 'calc(calc(1 + 2) + 3)' ],

			[ 'numberPercentage', '12' ],
			[ 'numberPercentage', '12%' ],
			[ 'numberPercentage', 'calc(1% + 1)' ],
			[ 'numberPercentage', 'calc(calc(1 + 2) + 3)' ],

			[ 'dimension', '12', false ],
			[ 'dimension', '12%', false ],
			[ 'dimension', '12foobar' ],

			[ 'length', '12', false ],
			[ 'length', '12%', false ],
			[ 'length', '12em' ],
			[ 'length', '+12em' ],
			[ 'length', '-12em' ],
			[ 'length', '12.2em' ],
			[ 'length', '1e2em' ],
			[ 'length', 'fooem', false ],
			[ 'length', 'calc( 1em + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'length', 'calc(1em + 2em*3*(4 + 5))' ],
			[ 'length', 'calc(1em/2)' ],
			[ 'length', 'calc(1/2em)', false ],
			[ 'length', 'calc(1em)' ],
			[ 'length', 'calc()', false ],
			[ 'length', 'calc( 1.2em )' ],
			[ 'length', 'calc(calc(1em + 2) + 3)' ],
			[ 'length', '0' ],
			[ 'length', '1EM' ],
			[ 'length', '1Em' ],
			[ 'lengthPercentage', '1em' ],
			[ 'lengthPercentage', '1%' ],
			[ 'lengthPercentage', 'calc(1% + 1em)' ],

			[ 'angle', '12', false ],
			[ 'angle', '12%', false ],
			[ 'angle', '12deg' ],
			[ 'angle', '+12deg' ],
			[ 'angle', '-12deg' ],
			[ 'angle', '12.2deg' ],
			[ 'angle', '1e2deg' ],
			[ 'angle', 'foodeg', false ],
			[ 'angle', 'calc( 1deg + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'angle', 'calc(1deg + 2deg*3*(4 + 5))' ],
			[ 'angle', 'calc(1deg/2)' ],
			[ 'angle', 'calc(1/2deg)', false ],
			[ 'angle', 'calc(1deg)' ],
			[ 'angle', 'calc()', false ],
			[ 'angle', 'calc( 1.2deg )' ],
			[ 'angle', 'calc(calc(1deg + 2) + 3)' ],
			[ 'angle', '0' ],
			[ 'angle', '1DEG' ],
			[ 'angle', '1Deg' ],
			[ 'anglePercentage', '1deg' ],
			[ 'anglePercentage', '1%' ],
			[ 'anglePercentage', 'calc(1% + 1deg)' ],

			[ 'time', '12', false ],
			[ 'time', '12%', false ],
			[ 'time', '12s' ],
			[ 'time', '+12s' ],
			[ 'time', '-12s' ],
			[ 'time', '12.2s' ],
			[ 'time', '1e2s' ],
			[ 'time', 'foos', false ],
			[ 'time', 'calc( 1s + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'time', 'calc(1s + 2s*3*(4 + 5))' ],
			[ 'time', 'calc(1s/2)' ],
			[ 'time', 'calc(1/2s)', false ],
			[ 'time', 'calc(1s)' ],
			[ 'time', 'calc()', false ],
			[ 'time', 'calc( 1.2s )' ],
			[ 'time', 'calc(calc(1s + 2) + 3)' ],
			[ 'time', '0', false ],
			[ 'time', '1MS' ],
			[ 'time', '1Ms' ],
			[ 'timePercentage', '1s' ],
			[ 'timePercentage', '1%' ],
			[ 'timePercentage', 'calc(1% + 1s)' ],

			[ 'frequency', '12', false ],
			[ 'frequency', '12%', false ],
			[ 'frequency', '12hz' ],
			[ 'frequency', '+12hz' ],
			[ 'frequency', '-12hz' ],
			[ 'frequency', '12.2hz' ],
			[ 'frequency', '1e2hz' ],
			[ 'frequency', 'foohz', false ],
			[ 'frequency', 'calc( 1hz + 2 * 3 * ( 4 + 5 ) )' ],
			[ 'frequency', 'calc(1hz + 2hz*3*(4 + 5))' ],
			[ 'frequency', 'calc(1hz/2)' ],
			[ 'frequency', 'calc(1/2hz)', false ],
			[ 'frequency', 'calc(1hz)' ],
			[ 'frequency', 'calc()', false ],
			[ 'frequency', 'calc( 1.2hz )' ],
			[ 'frequency', 'calc(calc(1hz + 2) + 3)' ],
			[ 'frequency', '0', false ],
			[ 'frequency', '1HZ' ],
			[ 'frequency', '1Hz' ],
			[ 'frequencyPercentage', '1hz' ],
			[ 'frequencyPercentage', '1%' ],
			[ 'frequencyPercentage', 'calc(1% + 1hz)' ],

			[ 'resolution', '12', false ],
			[ 'resolution', '12%', false ],
			[ 'resolution', '12dpi' ],
			[ 'resolution', '+12dpi' ],
			[ 'resolution', '-12dpi' ],
			[ 'resolution', '12.2dpi' ],
			[ 'resolution', '1e2dpi' ],
			[ 'resolution', 'foodpi', false ],
			[ 'resolution', 'calc(1dpi)', false ],
			[ 'resolution', '0', false ],
			[ 'resolution', '1DPI' ],
			[ 'resolution', '1Dpi' ],

			[ 'color', 'white' ],
			[ 'color', 'WhItE' ],
			[ 'color', 'azul', false ],
			[ 'color', 'transparent' ],
			[ 'color', 'currentcolor' ],
			[ 'color', '#000' ],
			[ 'color', '#012345' ],
			[ 'color', '#aBc' ],
			[ 'color', '#aBcDeF' ],
			[ 'color', '#abg', false ],
			[ 'color', '#0000', false ],
			[ 'color', '#00000', false ],
			[ 'color', '#00000000', false ],
			[ 'color', 'rgb( 0, 0, 0 )' ],
			[ 'color', 'rgb(0,0,0)' ],
			[ 'color', 'rgb( 0 0 0 )', false ],
			[ 'color', 'rgb(0.1,0.1,0.1)', false ],
			[ 'color', 'rgb( 0 , 0 , 0 )' ],
			[ 'color', 'rgb(0,0,0,)', false ],
			[ 'color', 'rgb(0,0)', false ],
			[ 'color', 'rgb(0,0,0,0)', false ],
			[ 'color', 'rgb( 0%, 0%, 0% )' ],
			[ 'color', 'rgb(0%,0%,0%)' ],
			[ 'color', 'rgb(0.1%,0%,0%)' ],
			[ 'color', 'rgb( 0% , 0% , 0% )' ],
			[ 'color', 'rgb(0%,0%,0%,0)', false ],
			[ 'color', 'rgb(0%,0%,0%,)', false ],
			[ 'color', 'rgb(0%,0%)', false ],
			[ 'color', 'rgb(0%,0%,0)', false ],
			[ 'color', 'rgba(0,0,0,0)' ],
			[ 'color', 'rgba(0, 0, 0, 0)' ],
			[ 'color', 'rgba( 0 , 0 , 0 , 0 )' ],
			[ 'color', 'rgba(0 0 0 0)', false ],
			[ 'color', 'rgba(0,0,0,0.1)' ],
			[ 'color', 'rgba(0%,0%,0%,0)' ],
			[ 'color', 'rgba(0%,0%,0%,0.1)' ],
			[ 'color', 'rgba(0,0,0)', false ],
			[ 'color', 'rgba(0%,0%,0%)', false ],
			[ 'color', 'rgba(0%,0%,0%,0%)', false ],
			[ 'color', 'rgba(0%,0,0%,0)', false ],
			[ 'color', 'hsl(0,0%,0%)' ],
			[ 'color', 'hsl(0.1,0%,0%)' ],
			[ 'color', 'hsl(0 0% 0%)', false ],
			[ 'color', 'hsl(0,0%,0%,0)', false ],
			[ 'color', 'hsl(0%,0%,0%)', false ],
			[ 'color', 'hsl(0,0,0%)', false ],
			[ 'color', 'hsl(0,0%,0)', false ],
			[ 'color', 'hsla(0,0%,0%,0)' ],
			[ 'color', 'hsla(0.1,0%,0%,0)' ],
			[ 'color', 'hsla(0,0%,0%,0.1)' ],
			[ 'color', 'hsla(0 0% 0% 0)', false ],
			[ 'color', 'hsla(0,0%,0%)', false ],
			[ 'color', 'hsla(0%,0%,0%,0)', false ],
			[ 'color', 'hsla(0,0,0%,0)', false ],
			[ 'color', 'hsla(0,0%,0,0)', false ],
			[ 'color', 'hsla(0,0%,0%,0%)', false ],

			[ 'image', "url({$img})" ],
			[ 'image', "url('{$img}')" ],
			[ 'image', "image( url('{$img}') )" ],
			[ 'image', "image( '{$img}' )" ],
			[ 'image', 'image( black )' ],
			[ 'image', "image( url('{$img}'), '{$img}' )" ],
			[ 'image', "image( url('{$img}'), '{$img}', black )" ],
			[ 'image', "image( url('{$img}'), black, '{$img}' )", false ],
			[ 'image', 'image( black, black )', false ],
			[ 'image', "image('{$img}','{$img}')" ],
			[ 'image', "image( '{$img}' , '{$img}' )" ],
			[ 'image', 'linear-gradient( white, blue )' ],
			[ 'image', 'linear-gradient( to left, white, blue )' ],
			[ 'image', 'linear-gradient( to right, white, blue )' ],
			[ 'image', 'linear-gradient( to top, white, blue )' ],
			[ 'image', 'linear-gradient( to bottom, white, blue )' ],
			[ 'image', 'linear-gradient( to top left, white, blue )' ],
			[ 'image', 'linear-gradient( to left top, white, blue )' ],
			[ 'image', 'linear-gradient( to left right, white, blue )', false ],
			[ 'image', 'linear-gradient( to top bottom, white, blue )', false ],
			[ 'image', 'linear-gradient( to, white, blue )', false ],
			[ 'image', 'linear-gradient( 20deg, white, blue )' ],
			[ 'image', 'linear-gradient( 20deg to left, white, blue )', false ],
			[ 'image', 'linear-gradient( to left 20deg, white, blue )', false ],
			[ 'image', 'linear-gradient( white )', false ],
			[ 'image', 'linear-gradient( white 20% )', false ],
			[ 'image', 'linear-gradient( to left, white )', false ],
			[ 'image', 'linear-gradient( to right, white )', false ],
			[ 'image', 'linear-gradient( to top, white )', false ],
			[ 'image', 'linear-gradient( to bottom, white )', false ],
			[ 'image', 'linear-gradient( to top left, white )', false ],
			[ 'image', 'linear-gradient( to left top, white )', false ],
			[ 'image', 'linear-gradient( 20deg, white )', false ],
			[ 'image', 'linear-gradient( white 20%, blue 3em )' ],
			[ 'image', 'repeating-linear-gradient( to left, white, blue )' ],
			[ 'image', 'radial-gradient( white, blue )' ],
			[ 'image', 'radial-gradient( circle, white, blue )' ],
			[ 'image', 'radial-gradient( ellipse, white, blue )' ],
			[ 'image', 'radial-gradient( circle closest-side, white, blue )' ],
			[ 'image', 'radial-gradient( closest-side circle, white, blue )' ],
			[ 'image', 'radial-gradient( ellipse closest-side, white, blue )' ],
			[ 'image', 'radial-gradient( closest-side ellipse, white, blue )' ],
			[ 'image', 'radial-gradient( circle 10px, white, blue )' ],
			[ 'image', 'radial-gradient( 10px circle, white, blue )' ],
			[ 'image', 'radial-gradient( circle 10%, white, blue )', false ],
			[ 'image', 'radial-gradient( 10% circle, white, blue )', false ],
			[ 'image', 'radial-gradient( circle 10px 10px, white, blue )', false ],
			[ 'image', 'radial-gradient( 10px circle 10px, white, blue )', false ],
			[ 'image', 'radial-gradient( ellipse 10px 10px, white, blue )' ],
			[ 'image', 'radial-gradient( 10px 10px ellipse, white, blue )' ],
			[ 'image', 'radial-gradient( 10px ellipse 10px, white, blue )', false ],
			[ 'image', 'radial-gradient( ellipse 10% 10%, white, blue )' ],
			[ 'image', 'radial-gradient( ellipse 10px 10%, white, blue )' ],
			[ 'image', 'radial-gradient( ellipse 10% 10px, white, blue )' ],
			[ 'image', 'radial-gradient( ellipse 10px, white, blue )', false ],
			[ 'image', 'radial-gradient( 10px ellipse, white, blue )', false ],
			[ 'image', 'radial-gradient( 10px, white, blue )' ],
			[ 'image', 'radial-gradient( 10%, white, blue )', false ],
			[ 'image', 'radial-gradient( 10px 10px, white, blue )' ],
			[ 'image', 'radial-gradient( 10% 10px, white, blue )' ],
			[ 'image', 'radial-gradient( 10px 10%, white, blue )' ],
			[ 'image', 'radial-gradient( 10% 10%, white, blue )' ],
			[ 'image', 'radial-gradient( circle 10px at top, white, blue )' ],
			[ 'image', 'radial-gradient( circle at top, white, blue )' ],
			[ 'image', 'radial-gradient( 10px at top, white, blue )' ],
			[ 'image', 'radial-gradient( at top, white, blue )' ],
			[ 'image', 'radial-gradient( at top, white, blue )' ],
			[ 'image', 'radial-gradient( circle closest-side at top, white, blue )' ],
			[ 'image', 'radial-gradient( , white, blue )', false ],
			[ 'image', 'radial-gradient( white )', false ],
			[ 'image', 'radial-gradient( white 10%, blue 10em )' ],
			[ 'image', 'repeating-radial-gradient( circle closest-side at top, white, blue )' ],

			[ 'position', 'left' ],
			[ 'position', 'center' ],
			[ 'position', 'right' ],
			[ 'position', 'top' ],
			[ 'position', 'bottom' ],
			[ 'position', '10%' ],
			[ 'position', '10em' ],
			[ 'position', 'left top' ],
			[ 'position', 'center center' ],
			[ 'position', 'right bottom' ],
			[ 'position', 'top left' ],
			[ 'position', 'left right', false ],
			[ 'position', 'top bottom', false ],
			[ 'position', 'center left' ],
			[ 'position', 'top center' ],
			[ 'position', '10% 10em' ],
			[ 'position', 'left 10%' ],
			[ 'position', '10em top' ],
			[ 'position', 'top 10em', false ],
			[ 'position', '10em left', false ],
			[ 'position', 'left 10em top' ],
			[ 'position', 'left top 10%' ],
			[ 'position', 'left 10em top 10%' ],
			[ 'position', 'bottom 10em right' ],
			[ 'position', 'bottom right 10%' ],
			[ 'position', 'bottom 10em right 10%' ],
			[ 'position', 'top bottom 10%', false ],
			[ 'position', 'top 10em bottom 10%', false ],
			[ 'position', 'center bottom 10%' ],
			[ 'position', 'center left 10%' ],
			[ 'position', 'bottom 10% center' ],
			[ 'position', 'left 10% center' ],

			[ 'cssMediaQuery', 'screen' ],
			[ 'cssMediaQuery', '(width: 700px)' ],
			[ 'cssMediaQuery', '(color)' ],
			[ 'cssMediaQuery', '(400px <= min-width <= 700px)' ],
			[ 'cssMediaQuery', '(400px <= min-width < = 700px)', false ],
			[ 'cssMediaQuery', '(400px >= min-width > = 700px)', false ],
			[ 'cssMediaQuery', '(400px <= min-width >= 700px)', false ],
			[ 'cssMediaQuery', '(width >= 700px)' ],
			[ 'cssMediaQuery', 'width >= 700px', false ],
			[ 'cssMediaQuery', '(width > = 700px)', false ],
			[ 'cssMediaQuery', '(width < = 700px)', false ],
			[ 'cssMediaQuery', '(width > 700px)' ],
			[ 'cssMediaQuery', '(width = 700px)' ],
			[ 'cssMediaQuery', '(width 700px)' ], // Crazy, but allowed
			[ 'cssMediaQuery', 'print and (min-resolution: 118dpcm)' ],
			[ 'cssMediaQuery', '((width>100px) and (height>800px)) or ((width>800px) and (height>100px))' ],
			[ 'cssMediaQuery', 'not screen' ],
			[ 'cssMediaQuery', 'only screen' ],
			[ 'cssMediaQuery', 'not (width = 700px)' ],
			[ 'cssMediaQuery', '(aspect-ratio: 16/9)' ],
			[ 'cssMediaQuery', 'bogus', false ],
			[ 'cssMediaQuery', 'screen and (bogus)', false ],

			[ 'cssMediaQuery unstrict', 'bogus' ],
			[ 'cssMediaQuery unstrict', 'screen and (bogus)' ],
			[ 'cssMediaQuery unstrict', '( foobar( baz ? quux? ) ) and ( bogus ? x y z )' ],

			[ 'cssMediaQueryList', 'screen and (color), projection and (color)' ],
			[ 'cssMediaQueryList', '' ],

			[ 'cssSingleTimingFunction', 'ease' ],
			[ 'cssSingleTimingFunction', 'steps(5)' ],
			[ 'cssSingleTimingFunction', 'steps( 77, end )' ],
			[ 'cssSingleTimingFunction', 'cubic-bezier( 1, 3.4, +5, -1e2 )' ],
			[ 'cssSingleTimingFunction', 'frames( 42 )' ],

			[ 'cssSelector', '', null ],

			[ 'cssSelectorList', 'h1, h2, h3' ],
			[ 'cssSelectorList', 'h1, h2..foo, h3', null ],

			[ 'cssTypeSelector', 'foo|h1' ],
			[ 'cssTypeSelector', '|h1' ],
			[ 'cssTypeSelector', '*|h1' ],
			[ 'cssTypeSelector', 'h1' ],
			[ 'cssTypeSelector', 'foo |h1', null ],
			[ 'cssTypeSelector', 'foo| h1', null ],
			[ 'cssUniversal', 'foo|*' ],
			[ 'cssUniversal', '|*' ],
			[ 'cssUniversal', '*|*' ],
			[ 'cssUniversal', '*' ],
			[ 'cssUniversal', 'foo |*', null ],
			[ 'cssUniversal', 'foo| *', null ],

			[ 'cssAttrib', '[att]' ],
			[ 'cssAttrib', '[ att ]' ],
			[ 'cssAttrib', '[att=val]' ],
			[ 'cssAttrib', '[att="val"]' ],
			[ 'cssAttrib', '[ att = val ]' ],
			[ 'cssAttrib', '[att~=val]' ],
			[ 'cssAttrib', '[ att ~= val ]' ],
			[ 'cssAttrib', '[ att ~ = val ]', null ],
			[ 'cssAttrib', '[att|=val]' ],
			[ 'cssAttrib', '[ att |= val ]' ],
			[ 'cssAttrib', '[ att | = val ]', null ],
			[ 'cssAttrib', '[att^=val]' ],
			[ 'cssAttrib', '[ att ^= val ]' ],
			[ 'cssAttrib', '[ att ^ = val ]', null ],
			[ 'cssAttrib', '[att$=val]' ],
			[ 'cssAttrib', '[ att $= val ]' ],
			[ 'cssAttrib', '[ att $ = val ]', null ],
			[ 'cssAttrib', '[att*=val]' ],
			[ 'cssAttrib', '[ att *= val ]' ],
			[ 'cssAttrib', '[ att * = val ]', null ],
			[ 'cssAttrib', '[foo|att=val]' ],
			[ 'cssAttrib', '[|att=val]' ],
			[ 'cssAttrib', '[*|att=val]' ],
			[ 'cssAttrib', '[foo |att=val]', null ],
			[ 'cssAttrib', '[foo| att=val]', null ],

			[ 'cssID', '#foo' ],
			[ 'cssID', '# foo', null ],
			[ 'cssID', '#9foo', null ],
			[ 'cssID', '#\9foo' ],

			[ 'cssClass', '.foo' ],
			[ 'cssClass', '. foo', null ],

			[ 'cssPseudo', ':link' ],
			[ 'cssPseudo', '::before' ],
			[ 'cssPseudo', ':: before', null ],
			[ 'cssPseudo', ': :before', null ],
			[ 'cssPseudo', ': : before', null ],
			[ 'cssPseudo', ':lang(xyz)' ],
			[ 'cssPseudo', ':lang( xyz )' ],
			[ 'cssPseudo', ':nth-child(2n+1)' ],

			[ 'cssNegation', ':not(xyz)' ],
			[ 'cssNegation', ':not( xyz )' ],
			[ 'cssNegation', ': not( xyz )', null ],
			[ 'cssNegation', ':not( :link )' ],
			[ 'cssNegation', ':not( :nth-child( 2n + 1 ) )' ],
			[ 'cssNegation', ':not( :not( xyz ) )', null ],

			[ 'cssANplusB', '', null ],
			[ 'cssANplusB', 'even' ],
			[ 'cssANplusB', 'odd' ],
			[ 'cssANplusB', '2n+1' ],
			[ 'cssANplusB', '2n + 1' ],
			[ 'cssANplusB', '2 n+1', null ],
			[ 'cssANplusB', '+1' ],
			[ 'cssANplusB', '+ 1', null ],
			[ 'cssANplusB', '1' ],
			[ 'cssANplusB', 'n+1' ],
			[ 'cssANplusB', 'n + 1' ],
			[ 'cssANplusB', '2n' ],
			[ 'cssANplusB', 'n' ],
			[ 'cssANplusB', '-2n+-1', null ],
			[ 'cssANplusB', '-2n-1' ],
			[ 'cssANplusB', '- 2n + -1', null ],
			[ 'cssANplusB', '-2n -1' ],
			[ 'cssANplusB', '-2n - 1' ],
			[ 'cssANplusB', '+-1', null ],
			[ 'cssANplusB', '+ -1', null ],
			[ 'cssANplusB', '-1' ],
			[ 'cssANplusB', '- 1', null ],
			[ 'cssANplusB', '-n+-1', null ],
			[ 'cssANplusB', '-n-1' ],
			[ 'cssANplusB', '-n + -1', null ],
			[ 'cssANplusB', '-n + - 1', null ],
			[ 'cssANplusB', '-n -1' ],
			[ 'cssANplusB', '-n - 1' ],
			[ 'cssANplusB', '-2n' ],
			[ 'cssANplusB', '-n' ],

			[ 'cssSimpleSelectorSeq', '', null ],
			[ 'cssSimpleSelectorSeq', '#hash' ],
			[ 'cssSimpleSelectorSeq', '# hash', null ],
			[ 'cssSimpleSelectorSeq', 'h1#hash' ],
			[ 'cssSimpleSelectorSeq', 'h1# hash', null ],
			[ 'cssSimpleSelectorSeq', 'h1 #hash', null ],
			[ 'cssSimpleSelectorSeq', 'h1#hash.class[ att = "foo" ]:link::before' ],
			[ 'cssSimpleSelectorSeq', 'h1:not( xyz )' ],

			[ 'cssSelector', 'h1 h2 h3' ],
			[ 'cssSelector', 'h1 h2, h3', null ],
			[ 'cssSelector', 'h1 > h2 ~ h3 + h4' ],
			[ 'cssSelector', 'h1>h2~h3+h4' ],
			[ 'cssSelector', 'h1.class>h2[attr1][attr2].xyz[att~="123"]~h3+h4' ],
			[ 'cssSelector', ' x', null ],
			[ 'cssSelector', '> x', null ],
			[ 'cssSelector', '~ x', null ],
			[ 'cssSelector', '+ x', null ],

			[ 'cssSelectorList', 'h1.class[att] > h2 h3, #id[ attr = val ]:link:not( foo )',
				new Match( $dummy, 0, 18, null, [
					new Match( $dummy, 0, 10, 'selector', [
						new Match( $dummy, 0, 4, 'simple', [
							new Match( $dummy, 0, 1, 'element' ),
							new Match( $dummy, 1, 2, 'class' ),
							new Match( $dummy, 3, 1, 'attrib', [
								new Match( $dummy, 0, 1, 'attribute' ),
							] ),
						] ),
						new Match( $dummy, 4, 3, 'combinator' ),
						new Match( $dummy, 7, 1, 'simple', [
							new Match( $dummy, 7, 1, 'element' ),
						] ),
						new Match( $dummy, 8, 1, 'combinator', [
							new Match( $dummy, 8, 1, 'significantWhitespace' ),
						] ),
						new Match( $dummy, 9, 1, 'simple', [
							new Match( $dummy, 9, 1, 'element' ),
						] ),
					] ),
					new Match( $dummy, 12, 6, 'selector', [
						new Match( $dummy, 12, 6, 'simple', [
							new Match( $dummy, 12, 1, 'id' ),
							new Match( $dummy, 13, 1, 'attrib', [
								new Match( $dummy, 1, 1, 'attribute' ),
								new Match( $dummy, 3, 1, 'test' ),
								new Match( $dummy, 5, 1, 'value' ),
							] ),
							new Match( $dummy, 14, 2, 'pseudo' ),
							new Match( $dummy, 16, 2, 'negation' ),
						] ),
					] )
				] )
			]
		];
	}

	/**
	 * @dataProvider provideAllUnits
	 * @param string $type Matcher to use
	 * @param string $unit Unit to test
	 * @param bool $expectMatch Whether it should match
	 */
	public function testAllUnits( $type, $unit, $expectMatch ) {
		$matcher = MatcherFactory::singleton()->$type();
		$list = new ComponentValueList( [ new Token( Token::T_DIMENSION, [
			'value' => 1,
			'unit' => $unit,
			'representation' => '1',
			'typeFlag' => 'integer',
		] ) ] );

		$res = (bool)$matcher->match( $list );
		$this->assertSame( $expectMatch, $res, "1$unit as $type" );
	}

	public static function provideAllUnits() {
		$units = [
			'em' => 'length', 'ex' => 'length', 'ch' => 'length', 'rem' => 'length',
			'vw' => 'length', 'vh' => 'length', 'vmin' => 'length', 'vmax' => 'length',
			'cm' => 'length', 'mm' => 'length', 'Q' => 'length', 'in' => 'length',
			'pc' => 'length', 'pt' => 'length', 'px' => 'length',
			'deg' => 'angle', 'grad' => 'angle', 'rad' => 'angle', 'turn' => 'angle',
			's' => 'time', 'ms' => 'time',
			'Hz' => 'frequency', 'kHz' => 'frequency',
			'dpi' => 'resolution', 'dpcm' => 'resolution', 'dppx' => 'resolution',
		];
		$types = array_unique( array_values( $units ) );

		$ret = [];
		foreach ( $units as $unit => $utype ) {
			foreach ( $types as $type ) {
				$ret[] = [ $type, $unit, $type === $utype ];
			}
		}
		return $ret;
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;
use Wikimedia\CSS\Grammar\TokenMatcher;
use Wikimedia\CSS\Grammar\UrlMatcher;
use Wikimedia\CSS\Parser\Parser;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Sanitizer\StylePropertySanitizer
 */
class StylePropertySanitizerTest extends \PHPUnit_Framework_TestCase {

	private static $sanitizer;

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::$sanitizer = null;
	}

	protected function getSanitizer() {
		if ( !self::$sanitizer ) {
			self::$sanitizer = new StylePropertySanitizer( TestMatcherFactory::singleton() );
		} else {
			self::$sanitizer->clearSanitizationErrors();
		}
		return self::$sanitizer;
	}

	/**
	 * @dataProvider provideDeclarations
	 * @param string $input
	 * @param string|null $error
	 */
	public function testDeclarations( $input, $error = null ) {
		$declaration = Parser::newFromString( $input )->parseDeclaration();
		$san = $this->getSanitizer();
		$rm = new \ReflectionMethod( $san, 'doSanitize' );
		$rm->setAccessible( true );
		$ret = $rm->invoke( $san, $declaration );
		if ( $error ) {
			$this->assertNull( $ret );
			if ( $error === 'bad-value-for-property' && $declaration->getValue()->count() ) {
				$pos = Util::findFirstNonWhitespace( $declaration->getValue() )->getPosition();
				$extra = [ $declaration->getName() ];
			} else {
				$pos = $declaration->getPosition();
				$extra = [];
			}
			$this->assertSame( [ array_merge( [ $error ], $pos, $extra ) ], $san->getSanitizationErrors() );
		} else {
			$this->assertSame( $declaration, $ret );
			$this->assertSame( [], $san->getSanitizationErrors() );
		}
	}

	public static function provideDeclarations() {
		return [
			[ 'unknown: foo', 'unrecognized-property' ],

			// These mostly (but not entirely) just make sure every property
			// that should be recognized actually is.

			// misc
			[ 'all: inherit' ],
			[ 'touch-action: pan-y pan-x' ],
			[ 'page: auto' ],

			// css2
			[ 'margin-top: auto' ],
			[ 'margin-left: 1ex' ],
			[ 'margin-right: 10%' ],
			[ 'margin-bottom: initial' ],
			[ 'margin: 1px' ],
			[ 'margin: 1px 10% auto auto' ],
			[ 'padding-top: auto', 'bad-value-for-property' ],
			[ 'padding-left: 1ex' ],
			[ 'padding-right: 10%' ],
			[ 'padding-bottom: unset' ],
			[ 'padding: 1px' ],
			[ 'padding: 1px 10%' ],
			[ 'padding: 0 0 calc(10px * 10) 0' ],
			[ 'float: left' ],
			[ 'clear: both' ],
			[ 'width: auto' ],
			[ 'min-width: 10q' ],
			[ 'max-width: none' ],
			[ 'height: auto' ],
			[ 'min-height: 10q' ],
			[ 'max-height: none' ],
			[ 'line-height: normal' ],
			[ 'line-height: 3' ],
			[ 'vertical-align: middle' ],
			[ 'clip: rect(1px, auto, 1em, auto)' ],
			[ 'visibility: hidden' ],
			[ 'list-style-type: circle' ],
			[ 'content: "foo" url("image.jpg") counter(foobar) counter(foobaz, disc) attr(data-foo-baz)' ],
			[ 'content: "nope" url("bad.jpg")', 'bad-value-for-property' ],
			[ 'quotes: "«" "»" "<" ">"' ],
			[ 'quotes: "«" "»" "<"', 'bad-value-for-property' ],
			[ 'counter-reset: foobaz' ],
			[ 'counter-increment: foobaz 7' ],
			[ 'list-style-image: url("image.png")' ],
			[ 'list-style-image: linear-gradient( white, blue )' ],
			[ 'list-style-position: inside' ],
			[ 'list-style: linear-gradient( white, blue ) inside square' ],
			[ 'caption-side: top' ],
			[ 'table-layout: fixed' ],
			[ 'border-collapse: collapse' ],
			[ 'border-spacing: 1px' ],
			[ 'border-spacing: 1px 2px' ],
			[ 'empty-cells: show' ],

			// cssDisplay3
			[ 'display: none' ],
			[ 'display: grid' ],
			[ 'display: inline table' ],
			[ 'display: list-item' ],

			// cssPosition3
			[ 'position: absolute' ],
			[ 'position: sticky' ],
			[ 'top: 10px' ],
			[ 'left: auto' ],
			[ 'right: auto' ],
			[ 'bottom: 10%' ],
			[ 'offset-before: 10px' ],
			[ 'offset-after: auto' ],
			[ 'offset-start: auto' ],
			[ 'offset-end: auto' ],
			[ 'z-index: 100' ],
			[ 'z-index: auto' ],

			// cssColor3
			[ 'color: red' ],
			[ 'opacity: 0.5' ],

			// cssBorderBackground3
			[ 'background-color: #fff' ],
			[ 'background-image: url(/image.jpg), url(/image.jpg), none, linear-gradient( white, blue )' ],
			[ 'background-image: url(/bad.jpg)', 'bad-value-for-property' ],
			[ 'background-image: image("image1.jpg", "image2.jpg")' ],
			[ 'background-image: image("image1.jpg", "bad.jpg")', 'bad-value-for-property' ],
			[ 'background-repeat: repeat space, round, repeat-x' ],
			[ 'background-attachment: scroll, fixed, local' ],
			[ 'background-position: top left, 10% bottom, center' ],
			[ 'background-clip: border-box' ],
			[ 'background-origin: padding-box, content-box' ],
			[ 'background-size: 10em auto, cover, 6px' ],
			[ 'background: url(/image.jpg) top border-box, border-box right 1px/cover red' ],
			[ 'background: url(/bad.jpg), red', 'bad-value-for-property' ],
			[ 'border-top-color: red' ],
			[ 'border-right-color: red' ],
			[ 'border-left-color: red' ],
			[ 'border-bottom-color: red' ],
			[ 'border-color: red green transparent white' ],
			[ 'border-top-style: dotted' ],
			[ 'border-right-style: solid' ],
			[ 'border-left-style: none' ],
			[ 'border-bottom-style: outset' ],
			[ 'border-style: hidden double groove outset' ],
			[ 'border-top-width: 1px' ],
			[ 'border-right-width: medium' ],
			[ 'border-left-width: thin' ],
			[ 'border-bottom-width: thick' ],
			[ 'border-width: thick thin' ],
			[ 'border-top: 1px solid red' ],
			[ 'border-bottom: red solid 1px' ],
			[ 'border-left: red 1px' ],
			[ 'border-right: none' ],
			[ 'border: none blue' ],
			[ 'border-top-left-radius: 10%' ],
			[ 'border-top-right-radius: 0 5px' ],
			[ 'border-bottom-left-radius: 1em' ],
			[ 'border-bottom-right-radius: 5px 10px' ],
			[ 'border-radius: 5px 10px 15px 20%' ],
			[ 'border-radius: 5px 10px / 15px 20%' ],
			[ 'border-radius: 5px / 10px / 15px / 20%', 'bad-value-for-property' ],
			[ 'border-image-source: none' ],
			[ 'border-image-slice: 1' ],
			[ 'border-image-slice: fill 1 2 3 4' ],
			[ 'border-image-width: 1px 5% 42 auto' ],
			[ 'border-image-outset: 1px 42' ],
			[ 'border-image-repeat: stretch round' ],
			[ 'border-image: stretch round none 1 2 3' ],
			[ 'box-shadow: 1px 2px, red inset 0 0 0 0' ],

			// cssImages3
			[ 'object-fit: scale-down' ],
			[ 'object-position: top right' ],
			[ 'image-resolution: 0.01dpi' ],
			[ 'image-resolution: snap from-image' ],
			[ 'image-orientation: 90deg' ],

			// cssFonts3
			[ 'font-family: DejaVu Sans, "Gentium", monospace' ],
			[ 'font-weight: bold' ],
			[ 'font-weight: 600' ],
			[ 'font-weight: 601', 'bad-value-for-property' ],
			[ 'font-weight: 400.0', 'bad-value-for-property' ],
			[ 'font-weight: 4e2', 'bad-value-for-property' ],
			[ 'font-stretch: ultra-expanded' ],
			[ 'font-style: italic' ],
			[ 'font-size: small' ],
			[ 'font-size: 10pt' ],
			[ 'font-size-adjust: 9.3' ],
			[ 'font: small-caption' ],
			[ 'font: small-caps bold italic 10px/5 "DejaVu Sans", sans-serif' ],
			[ 'font-synthesis: none' ],
			[ 'font-synthesis: style' ],
			[ 'font-synthesis: style weight' ],
			[ 'font-kerning: normal' ],
			[ 'font-variant-ligatures: common-ligatures no-contextual' ],
			[ 'font-variant-position: sub' ],
			[ 'font-variant-caps: normal' ],
			[ 'font-variant-caps: unicase' ],
			[ 'font-variant-numeric: slashed-zero lining-nums tabular-nums' ],
			[ 'font-variant-alternates: normal' ],
			[ 'font-variant-east-asian: proportional-width' ],
			[ 'font-variant: slashed-zero common-ligatures unicase tabular-nums proportional-width' ],
			[ 'font-feature-settings: "abcd", "defg" off, "qq ~" 99' ],
			[ 'font-feature-settings: "abc"', 'bad-value-for-property' ],
			[ 'font-feature-settings: "abcde"', 'bad-value-for-property' ],
			[ 'font-language-override: "QQQ"' ],
			[ 'font-language-override: normal' ],
			[ 'font-language-override: "qqq"', 'bad-value-for-property' ],
			[ 'font-language-override: "QQQQ"', 'bad-value-for-property' ],

			// cssMulticol
			[ 'column-width: 30em' ],
			[ 'column-count: 3' ],
			[ 'columns: 3 30em' ],
			[ 'column-gap: 1px' ],
			[ 'column-rule-color: rgb(255,0,255)' ],
			[ 'column-rule-style: dashed' ],
			[ 'column-rule-width: 1px' ],
			[ 'column-rule: 1px rgb(255,0,255) dashed' ],
			[ 'column-span: all' ],
			[ 'column-fill: balance' ],

			// cssOverflow3
			[ 'overflow: scroll' ],
			[ 'overflow-x: hidden' ],
			[ 'overflow-y: auto' ],
			[ 'max-lines: 1000' ],

			// cssUI4
			[ 'box-sizing: border-box' ],
			[ 'outline-width: 1px' ],
			[ 'outline-style: dotted' ],
			[ 'outline-style: auto' ],
			[ 'outline-color: invert' ],
			[ 'outline: 10px invert dotted' ],
			[ 'outline-offset: 1em' ],
			[ 'resize: vertical' ],
			[ 'text-overflow: ellipsis' ],
			[ 'text-overflow: "???" "!!!"' ],
			[ 'text-overflow: fade(2em)' ],
			[ 'cursor: pointer' ],
			[ 'cursor: url(image.png), linear-gradient(white,blue) 1 2, auto' ],
			[ 'cursor: url(bad.png), linear-gradient(white,blue) 1 2, auto', 'bad-value-for-property' ],
			[ 'caret-color: auto' ],
			[ 'caret-color: maroon' ],
			[ 'caret-shape: auto' ],
			[ 'caret: auto maroon' ],
			[ 'nav-up: auto' ],
			[ 'nav-down: #foobar' ],
			[ 'nav-left: #foobar root' ],
			[ 'nav-right: #foobar "baz"' ],
			[ 'user-select: none' ],
			[ 'appearance: none' ],

			// cssCompositing1
			[ 'mix-blend-mode: overlay' ],
			[ 'isolation: isolate' ],
			[ 'background-blend-mode: screen, difference' ],

			// cssWritingModes3
			[ 'direction: rtl' ],
			[ 'unicode-bidi: isolate' ],
			[ 'writing-mode: horizontal-tb' ],
			[ 'text-orientation: upright' ],
			[ 'text-combine-upright: all' ],
			[ 'text-combine-upright: digits' ],
			[ 'text-combine-upright: digits 2' ],

			// cssTransitions
			[ 'transition-property: foo, bar, baz' ],
			[ 'transition-duration: 1s, 2000ms' ],
			[ 'transition-timing-function: ease-in, steps( 1 ), steps(3,end)' ],
			[ 'transition-delay: 1s, 2000ms' ],
			[ 'transition: foo ease-in 1s 1s, 5s linear bar' ],

			// cssAnimations
			[ 'animation-name: foo, bar, baz' ],
			[ 'animation-duration: 1s' ],
			[ 'animation-timing-function: cubic-bezier( 1, 2, 3, 4.0 ), linear' ],
			[ 'animation-iteration-count: infinite, 77' ],
			[ 'animation-direction: reverse, alternate-reverse' ],
			[ 'animation-play-state: paused, paused, paused' ],
			[ 'animation-delay: 1s, 2s, 3s' ],
			[ 'animation-fill-mode: both' ],
			[ 'animation: foo both infinite cubic-bezier( 1, 2, 3, 4.0 ), linear 1s' ],

			// cssFlexbox3
			[ 'flex-direction: row' ],
			[ 'flex-wrap: wrap-reverse' ],
			[ 'order: 6' ],
			[ 'flex-grow: 1e100' ],
			[ 'flex-shrink: 0.1' ],
			[ 'flex-basis: content' ],
			[ 'flex-basis: calc( 1px + 10% )' ],
			[ 'flex: 2' ],
			[ 'flex: content 3 4' ],

			// cssTransforms1
			[ 'transform: matrix( 1, 2, 3, 4, 5, 6 )' ],
			[ 'transform: translate( 2px )' ],
			[ 'transform: translate( 1, 2px )' ],
			[ 'transform: translateX( 1 )' ],
			[ 'transform: translateY( 2px )' ],
			[ 'transform: scale(9)' ],
			[ 'transform: scale(9.1,8)' ],
			[ 'transform: scaleX(9)' ],
			[ 'transform: scaleY(8.2)' ],
			[ 'transform: rotate( 0 )' ],
			[ 'transform: skew( 90deg, 2rad )' ],
			[ 'transform: skewX( 90deg )' ],
			[ 'transform: skewY( 0.5turn )' ],
			[ 'transform: matrix3D( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16 )' ],
			[ 'transform: translate3d( 1, 3, 5px )' ],
			[ 'transform: translatez( 5px )' ],
			[ 'transform: scale3d(1,2,3)' ],
			[ 'transform: scalez(3)' ],
			[ 'transform: rotate3d(1,2,3,90deg)' ],
			[ 'transform: rotatex(0)' ],
			[ 'transform: rotateY(1turn)' ],
			[ 'transform: rotateZ(720deg)' ],
			[ 'transform: perspective(300in)' ],
			[ 'transform-origin: 50px 50px' ],
			[ 'transform-origin: top left' ],
			[ 'transform-origin: left' ],
			[ 'perspective: 300in' ],
			[ 'perspective-origin: 50px bottom' ],
			[ 'backface-visibility: visible' ],

			// cssText3
			[ 'text-transform: uppercase' ],
			[ 'white-space: pre-wrap' ],
			[ 'tab-size: 7' ],
			[ 'line-break: normal' ],
			[ 'word-break: keep-all ' ],
			[ 'hyphens: manual' ],
			[ 'word-wrap: break-word' ],
			[ 'overflow-wrap: break-word' ],
			[ 'text-align: justify' ],
			[ 'text-align: start end' ],
			[ 'text-align-last: end' ],
			[ 'text-justify: inter-word' ],
			[ 'word-spacing: 500in' ],
			[ 'letter-spacing: 9px' ],
			[ 'text-indent: hanging 10% each-line' ],
			[ 'hanging-punctuation: last allow-end' ],

			// cssTextDecor3
			[ 'text-decoration-line: underline overline' ],
			[ 'text-decoration-line: blink', 'bad-value-for-property' ],
			[ 'text-decoration-color: red' ],
			[ 'text-decoration-style: wavy' ],
			[ 'text-decoration: wavy underline' ],
			[ 'text-decoration: blink', 'bad-value-for-property' ],
			[ 'text-decoration-skip: spaces ink' ],
			[ 'text-underline-position: under left' ],
			[ 'text-emphasis-style: open circle' ],
			[ 'text-emphasis-color: blue' ],
			[ 'text-emphasis: blue circle' ],
			[ 'text-emphasis-position: over right' ],
			[ 'text-shadow: red 2px 4px' ],

			// cssAlign3
			[ 'align-content: baseline' ],
			[ 'align-content: center unsafe' ],
			[ 'justify-content: center unsafe space-evenly' ],
			[ 'place-content: first baseline center' ],
			[ 'align-self: safe self-start' ],
			[ 'justify-self: auto' ],
			[ 'place-self: auto auto' ],
			[ 'align-items: normal' ],
			[ 'justify-items: center legacy' ],
			[ 'place-items: center normal' ],

			// cssBreak3
			[ 'break-before: avoid-column' ],
			[ 'break-after: auto' ],
			[ 'break-inside: avoid' ],
			[ 'orphans: 3' ],
			[ 'widows: calc( 3 * ( 2 - 1 ) + 1 )' ],
			[ 'box-decoration-break: clone' ],
			[ 'page-break-before: always' ],
			[ 'page-break-after: avoid' ],
			[ 'page-break-inside: auto' ],

			// cssSpeech
			[ 'voice-volume: medium +10db' ],
			[ 'voice-balance: left' ],
			[ 'speak: none' ],
			[ 'speak-as: no-punctuation digits' ],
			[ 'pause-before: weak' ],
			[ 'pause-after: 3s' ],
			[ 'pause: weak' ],
			[ 'rest-before: weak' ],
			[ 'rest-after: 3s' ],
			[ 'rest: weak 3s' ],
			[ 'cue-before: url("audio.wav") +1Db' ],
			[ 'cue-before: url("image.wav") +1Db', 'bad-value-for-property' ],
			[ 'cue-after: none' ],
			[ 'cue: url("audio.wav" y x) url(audio2.wav) +1Db' ],
			[ 'cue: url("audio.wav" x y) url(bad.wav) +1Db', 'bad-value-for-property' ],
			[ 'voice-family: "foo bar", foo bar, male, young female 3' ],
			[ 'voice-rate: 10% slow' ],
			[ 'voice-pitch: absolute 440Hz' ],
			[ 'voice-range: 3st' ],
			[ 'voice-stress: normal' ],
			[ 'voice-duration: 10s' ],

			// cssGrid1
			[ 'grid-template-columns: 100px 1fr max-content minmax(min-content, 1fr)' ],
			[ 'grid-template-rows: 1fr minmax(min-content, 1fr)' ],
			[ 'grid-template-rows: 10px repeat(2, 1fr auto minmax(30%, 1fr))' ],
			[ 'grid-template-rows: calc(4em - 5px)' ],
			[ 'grid-template-columns: [first nav-start] 150px [main-start] 1fr [last]' ],
			[ 'grid-template-rows: [first header-start] 50px [main-start] 1fr [footer-start] 50px [last]' ],
			[ 'grid-template-columns: repeat(4, 10px [col-start] 250px [col-end]) 10px' ],
			[ 'grid-template-columns: repeat(auto-fill, minmax(25ch, 1fr))' ],
			[
				'grid-template-columns: '
				. '[a] auto [b] minmax(min-content, 1fr) [b c d] repeat(2, [e] 40px) repeat(5, auto)'
			],
			[ 'grid-template-areas: "head head" "nav  main" "foot ...."' ],
			[ 'grid-template: auto 1fr / auto 1fr auto' ],
			[
				'grid-template: [header-top] "a   a   a"     [header-bottom]' . "\n"
				. '[main-top] "b   b   b" 1fr [main-bottom]' . "\n"
				. '/ auto 1fr auto'
			],
			[ 'grid-auto-columns: 40px' ],
			[ 'grid-auto-rows: fit-content( 10% )' ],
			[ 'grid-auto-flow: row dense' ],
			[ 'grid: "H    H " "A    B " "F    F " 30px / auto 1fr' ],
			[ 'grid: repeat(auto-fill, 5em) / auto-flow 1fr' ],
			[ 'grid-row-start: 4' ],
			[ 'grid-row-end: auto' ],
			[ 'grid-column-start: span C' ],
			[ 'grid-column-end: C -1' ],
			[ 'grid-column-start: 5 C span' ],
			[ 'grid-row: auto' ],
			[ 'grid-column: span R / 5 C span' ],
			[ 'grid-area: span 3' ],
			[ 'grid-area: span R / 5 C span / -1 / foobar' ],
			[ 'grid-row-gap: 2px' ],
			[ 'grid-column-gap: 1%' ],
			[ 'grid-gap: 1px 2px' ],

			// cssFilter1
			[ 'filter: none' ],
			[ 'filter: brightness(5%) contrast(3) drop-shadow(1px 2px 3px) drop-shadow(1px 2px red)' ],
			[ 'filter: blur(1px) grayscale(15) hue-rotate(10deg) invert(1) opacity(0.5) saturate(6)' ],
			[ 'filter: sepia(1%) url("ok.svg")' ] ,
			[ 'filter: url("bad.png")', 'bad-value-for-property' ] ,
			[ 'flood-color: #123456' ],
			[ 'flood-opacity: 0.9' ],
			[ 'color-interpolation-filters: srgb' ],
			[ 'lighting-color: rgba(1,2,3,0.5)' ],

			// cssShapes1
			[ 'shape-outside: inset( 0 )' ],
			[ 'shape-outside: inset( 0 0 100px 100px round 5px 10px / 15px 20% )' ],
			[ 'shape-outside: circle()' ],
			[ 'shape-outside: circle( 100px )' ],
			[ 'shape-outside: ellipse( farthest-side closest-side at 0 0 )' ],
			[ 'shape-outside: ellipse( at 0 0 )' ],
			[ 'shape-outside: polygon( nonzero, 0q 0q, 0q 1q, 1q 1q, 1q 0q )' ],
			[ 'shape-outside: polygon( 0q 0q, 0q 1q, 1q 1q, 1q 0q )' ],
			[ 'shape-outside: url(image.png)' ],
			[ 'shape-outside: url(bad.png)', 'bad-value-for-property' ],
			[ 'shape-image-threshold: 5' ],
			[ 'shape-margin: 2px' ],

			// cssMasking1
			[ 'clip-path: url("ok.svg#bar")' ],
			[ 'clip-path: url("bad.png")', 'bad-value-for-property' ],
			[ 'clip-rule: evenodd' ],
			[ 'mask-image: url(image.png), url(ok.svg)' ],
			[ 'mask-image: url(bad.png)', 'bad-value-for-property' ],
			[ 'mask-mode: alpha, luminance' ],
			[ 'mask-repeat: repeat space, round, repeat-x' ],
			[ 'mask-position: top left, 10% bottom, center' ],
			[ 'mask-clip: view-box, no-clip' ],
			[ 'mask-origin: view-box, fill-box' ],
			[ 'mask-size: 10em auto, cover, 6px' ],
			[ 'mask-composite: add, intersect' ],
			[ 'mask: url(image.png) alpha, top left/10em repeat space, bottom exclude no-clip' ],
			[ 'mask-border-source: none' ],
			[ 'mask-border-mode: alpha' ],
			[ 'mask-border-slice: 1' ],
			[ 'mask-border-slice: 1 2 3 4 fill' ],
			[ 'mask-border-width: 1px 5% 42 auto' ],
			[ 'mask-border-outset: 1px 42' ],
			[ 'mask-border-repeat: stretch round' ],
			[ 'mask-border: stretch round none 1 2 3 alpha' ],
			[ 'mask-type: luminance' ],
		];
	}
}

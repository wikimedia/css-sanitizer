<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\CheckedMatcher;
use Wikimedia\CSS\Grammar\GrammarMatch;
use Wikimedia\CSS\Grammar\MatcherFactory;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\Token;

/**
 * @covers \Wikimedia\CSS\Sanitizer\StyleRuleSanitizer
 */
class StyleRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		return new StyleRuleSanitizer(
			empty( $options['sel'] ) ? MatcherFactory::singleton()->cssSelectorList() : $options['sel'],
			new StylePropertySanitizer( MatcherFactory::singleton() ),
			$options
		);
	}

	public function testConstruct_prependSelectors() {
		$this->expectException( \InvalidArgumentException::class );
		// only test the error case, success is tested below
		$this->getSanitizer( [
			'prependSelectors' => [ '#content' ],
		] );
	}

	public function testConstruct_hoistableComponentMatcher() {
		$this->expectException( \InvalidArgumentException::class );
		// only test the error case, success is tested below
		$this->getSanitizer( [
			'hoistableComponentMatcher' => 'foo',
		] );
	}

	public static function provideRules() {
		$htmlOrBodySimpleSelectorSeqMatcher = new CheckedMatcher(
			MatcherFactory::singleton()->cssSimpleSelectorSeq(),
			static function ( ComponentValueList $values, GrammarMatch $match, array $options ) {
				foreach ( $match->getCapturedMatches() as $m ) {
					if ( $m->getName() !== 'element' ) {
						continue;
					}
					$str = (string)$m;
					return $str === 'html' || $str === 'body';
				}
				return false;
			}
		);

		return [
			'ok' => [
				'#foo {}',
				true,
				'#foo {}',
				'#foo{}',
			],
			'invalid selector' => [
				'.bar, ~ #foo {}',
				true,
				null,
				null,
				[ [ 'invalid-selector-list', 1, 1 ] ],
			],
			'invalid selector 2' => [
				'. bar {}',
				true,
				null,
				null,
				[ [ 'invalid-selector-list', 1, 1 ] ],
			],
			'no selector' => [
				'{}',
				true,
				null,
				null,
				[ [ 'missing-selector-list', 1, 1 ] ],
			],
			'invalid declarations in list' => [
				'#foo { display:none;ident; foo: bar; all: inherit !important }',
				true,
				'#foo { display:none; all:inherit !important; }',
				'#foo{display:none;all:inherit!important}',
				[ [ 'expected-colon', 1, 26 ], [ 'unrecognized-property', 1, 28 ] ],
			],
			'prefixing selectors' => [
				'#foo, .bar .baz, span.foo ~ div > * {}',
				true,
				'#x.y #foo, #x.y .bar .baz, #x.y span.foo ~ div > * {}',
				'#x.y #foo,#x.y .bar .baz,#x.y span.foo~div>*{}',
				[],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
						new Token( Token::T_DELIM, '.' ),
						new Token( Token::T_IDENT, 'y' ),
					],
				]
			],
			'hoisting selectors 1' => [
				'html.no-js #a, body.ltr .bar .baz, html body .foo, .bar {}',
				true,
				'html.no-js #x #a, body.ltr #x .bar .baz, html body #x .foo, #x .bar {}',
				'html.no-js #x #a,body.ltr #x .bar .baz,html body #x .foo,#x .bar{}',
				[],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
					],
					'hoistableComponentMatcher' => $htmlOrBodySimpleSelectorSeqMatcher,
				]
			],
			'hoisting selectors 2' => [
				'html.y:lang(en) body.foo ~ div > * {}',
				true,
				'html.y:lang(en) #x body.foo ~ div > * {}',
				'html.y:lang(en) #x body.foo~div>*{}',
				[],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
					],
					'hoistableComponentMatcher' => $htmlOrBodySimpleSelectorSeqMatcher,
				]
			],
			'hoistable components only' => [
				'html.no-js, html.no-js body.ltr {}',
				true,
				'#x html.no-js, html.no-js #x body.ltr {}',
				'#x html.no-js,html.no-js #x body.ltr{}',
				[],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
					],
					'hoistableComponentMatcher' => $htmlOrBodySimpleSelectorSeqMatcher,
				]
			],
			'invalid selector with prepending' => [
				'.bar, ~ #foo {}',
				true,
				null,
				null,
				[ [ 'invalid-selector-list', 1, 1 ] ],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
					],
				]
			],
			'missing selector with prepending' => [
				'{}',
				true,
				null,
				null,
				[ [ 'missing-selector-list', 1, 1 ] ],
				[
					'prependSelectors' => [
						new Token( Token::T_HASH, [ 'value' => 'x', 'typeFlag' => 'id' ] ),
					],
				]
			],
			'not a qualified rule' => [
				'@foo;',
				false,
				null,
				null,
				[ [ 'expected-qualified-rule', 1, 1 ] ],
			],
			'different selector' => [
				'"foo" {}',
				true,
				'"foo" {}',
				'"foo"{}',
				[],
				[ 'sel' => MatcherFactory::singleton()->string() ],
			],
		];
	}
}

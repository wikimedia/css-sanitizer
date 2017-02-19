<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\MatcherFactory;
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

	public static function provideRules() {
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
				'#foo { display:none; all: inherit !important; }',
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
						new Token( Token::T_WHITESPACE ),
					],
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
						new Token( Token::T_WHITESPACE ),
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
						new Token( Token::T_WHITESPACE ),
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

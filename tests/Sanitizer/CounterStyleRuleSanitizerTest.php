<?php

use Wikimedia\CSS\Grammar\TestMatcherFactory;
use Wikimedia\CSS\Sanitizer\CounterStyleAtRuleSanitizer;
use Wikimedia\CSS\Sanitizer\RuleSanitizerTestBase;

// phpcs:disable Generic.Files.LineLength
class CounterStyleRuleSanitizerTest extends RuleSanitizerTestBase {

	protected function getSanitizer( $options = [] ) {
		return new CounterStyleAtRuleSanitizer( TestMatcherFactory::singleton() );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'counter-style' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'counter-style' ] ],
			],
			'block required' => [
				'@counter-style x;',
				true,
				null,
				null,
				[ [ 'at-rule-block-required', 1, 1, 'counter-style' ] ]
			],
			'name required' => [
				'@counter-style {}',
				true,
				null,
				null,
				[ [ 'missing-counter-style-name', 1, 1 ] ]
			],
			'two names' => [
				'@counter-style foo bar {}',
				true,
				null,
				null,
				[ [ 'invalid-counter-style-name', 1, 16 ] ]
			],
			'ok' => [
				'@counter-style x {}',
				true,
				'@counter-style x {}',
				'@counter-style x{}',
			],
			'system cyclic' => [
				'@counter-style foo {
					system: cyclic;
				}',
				true,
				'@counter-style foo { system:cyclic; }',
				'@counter-style foo{system:cyclic}',
			],
			'system fixed' => [
				'@counter-style foo { system: fixed; }',
				true,
				'@counter-style foo { system:fixed; }',
				'@counter-style foo{system:fixed}',
			],
			'system fixed 11' => [
				'@counter-style foo { system: fixed 11; }',
				true,
				'@counter-style foo { system:fixed 11; }',
				'@counter-style foo{system:fixed 11}',
			],
			'system extends' => [
				'@counter-style foo { system: extends decimal; }',
				true,
				'@counter-style foo { system:extends decimal; }',
				'@counter-style foo{system:extends decimal}',
			],
			'circled-lower-latin' => [
				'@counter-style circled-lower-latin
				{
					system: alphabetic;
					speak-as: lower-latin;
					symbols: ⓐ ⓑ ⓒ ⓓ ⓔ ⓕ ⓖ ⓗ ⓘ ⓙ ⓚ ⓛ ⓜ ⓝ ⓞ ⓟ ⓠ ⓡ ⓢ ⓣ ⓤ ⓥ ⓦ ⓧ ⓨ ⓩ;
					suffix: " ";
				}',
				true,
				'@counter-style circled-lower-latin { system:alphabetic; speak-as:lower-latin; symbols:ⓐ ⓑ ⓒ ⓓ ⓔ ⓕ ⓖ ⓗ ⓘ ⓙ ⓚ ⓛ ⓜ ⓝ ⓞ ⓟ ⓠ ⓡ ⓢ ⓣ ⓤ ⓥ ⓦ ⓧ ⓨ ⓩ; suffix:" "; }',
				'@counter-style circled-lower-latin{system:alphabetic;speak-as:lower-latin;symbols:ⓐ ⓑ ⓒ ⓓ ⓔ ⓕ ⓖ ⓗ ⓘ ⓙ ⓚ ⓛ ⓜ ⓝ ⓞ ⓟ ⓠ ⓡ ⓢ ⓣ ⓤ ⓥ ⓦ ⓧ ⓨ ⓩ;suffix:" "}',
			],
			'additive-symbols' => [
				'@counter-style a { additive-symbols: 9000 s; }',
				true,
				'@counter-style a { additive-symbols:9000 s; }',
				'@counter-style a{additive-symbols:9000 s}',
			],
			'fallback' => [
				'@counter-style a { fallback:f; }',
				true,
				'@counter-style a { fallback:f; }',
				'@counter-style a{fallback:f}',
			],
			'negative' => [
				'@counter-style a { negative:"-"; }',
				true,
				'@counter-style a { negative:"-"; }',
				'@counter-style a{negative:"-"}',
			],
			'pad ident' => [
				'@counter-style a { pad:0 x; }',
				true,
				'@counter-style a { pad:0 x; }',
				'@counter-style a{pad:0 x}',
			],
			'pad string' => [
				'@counter-style a { pad:0 "x"; }',
				true,
				'@counter-style a { pad:0 "x"; }',
				'@counter-style a{pad:0"x"}',
			],
			'prefix ident' => [
				'@counter-style a { prefix:x; }',
				true,
				'@counter-style a { prefix:x; }',
				'@counter-style a{prefix:x}',
			],
			'prefix string' => [
				'@counter-style a { prefix:"x"; }',
				true,
				'@counter-style a { prefix:"x"; }',
				'@counter-style a{prefix:"x"}',
			],
			'range' => [
				'@counter-style a { range:1 10, 2 20, 3 30; }',
				true,
				'@counter-style a { range:1 10, 2 20, 3 30; }',
				'@counter-style a{range:1 10,2 20,3 30}',
			],
		];
	}
}

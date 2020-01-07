<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Sanitizer;

use Wikimedia\CSS\Grammar\TestMatcherFactory;

/**
 * @covers \Wikimedia\CSS\Sanitizer\ImportAtRuleSanitizer
 */
class ImportAtRuleSanitizerTest extends RuleSanitizerTestBase {

	/**
	 * @param array $options
	 */
	protected function getSanitizer( $options = [] ) {
		$matcherFactory = TestMatcherFactory::singleton();
		$propSan = new StylePropertySanitizer( $matcherFactory );
		if ( !empty( $options['declarationSanitizer'] ) ) {
			$options['declarationSanitizer'] = $propSan;
		}
		$san = new ImportAtRuleSanitizer( $matcherFactory, $options );
		return $san;
	}

	public function testException() {
		$matcherFactory = TestMatcherFactory::singleton();
		$this->expectException( \TypeError::class );
		// The exact TypeError message differs between php7 and php8 (nullables)
		$this->expectExceptionMessage( 'Wikimedia\CSS\Sanitizer\PropertySanitizer' );
		// @phan-suppress-next-line PhanNoopNew
		new ImportAtRuleSanitizer( $matcherFactory, [
			'declarationSanitizer' => new NamespaceAtRuleSanitizer( $matcherFactory ),
		] );
	}

	public static function provideRules() {
		return [
			'not at-rule' => [
				'.foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'import' ] ],
			],
			'wrong at-rule' => [
				'@foo {}',
				false,
				null,
				null,
				[ [ 'expected-at-rule', 1, 1, 'import' ] ],
			],
			'ok' => [
				'@import url("foo.css");',
				true,
				'@import url("foo.css");',
				'@import url("foo.css");',
			],
			'ok 2' => [
				'@import "foo.css";',
				true,
				'@import "foo.css";',
				'@import"foo.css";',
			],
			'bad url' => [
				'@import url("bad.xss");',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'bad url 2' => [
				'@import "bad.xss";',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'bad value' => [
				'@import foo.css;',
				true,
				null,
				null,
				[ [ 'invalid-import-value', 1, 9 ] ],
			],
			'missing value' => [
				'@import ;',
				true,
				null,
				null,
				[ [ 'missing-import-source', 1, 1 ] ],
			],
			'block not allowed' => [
				'@import "foo.css" {}',
				true,
				null,
				null,
				[ [ 'at-rule-block-not-allowed', 1, 19, 'import' ] ],
			],
			'ok with media query list' => [
				'@import "foo.css" handheld and (max-width: 400px);',
				true,
				'@import "foo.css" handheld and (max-width: 400px);',
				'@import"foo.css"handheld and (max-width:400px);',
			],
			'ok with supports condition' => [
				'@import "foo.css" supports( ( ( color : red ) or (color:blue)));',
				true,
				'@import "foo.css" supports( ( ( color : red ) or (color:blue)));',
				'@import"foo.css"supports(((color:red) or (color:blue)));',
			],
			'ok with supports declaration' => [
				'@import "foo.css" supports( color : red );',
				true,
				'@import "foo.css" supports( color : red );',
				'@import"foo.css"supports(color:red);',
			],
			'ok with supports declaration and media query list' => [
				'@import url(foo.css) supports(color:red) handheld and (max-width: 400px);',
				true,
				'@import url("foo.css") supports(color:red) handheld and (max-width: 400px);',
				'@import url("foo.css")supports(color:red)handheld and (max-width:400px);',
			],
		];
	}
}

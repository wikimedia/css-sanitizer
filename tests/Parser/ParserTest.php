<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Parser;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Wikimedia\CSS\Objects\AtRule;
use Wikimedia\CSS\Objects\ComponentValueList;
use Wikimedia\CSS\Objects\CSSFunction;
use Wikimedia\CSS\Objects\CSSObject;
use Wikimedia\CSS\Objects\Declaration;
use Wikimedia\CSS\Objects\DeclarationList;
use Wikimedia\CSS\Objects\DeclarationOrAtRuleList;
use Wikimedia\CSS\Objects\QualifiedRule;
use Wikimedia\CSS\Objects\RuleList;
use Wikimedia\CSS\Objects\SimpleBlock;
use Wikimedia\CSS\Objects\Stylesheet;
use Wikimedia\CSS\Objects\Token;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Parser\Parser
 */
class ParserTest extends TestCase {

	public function testConstructors() {
		$parser = Parser::newFromString( 'foobar', [ 'options' ] );
		$this->assertInstanceOf( Parser::class, $parser );

		$ds = $this->getMockBuilder( DataSource::class )->getMock();
		'@phan-var DataSource $ds';
		$parser = Parser::newFromDataSource( $ds, [ 'options' ] );
		$this->assertInstanceOf( Parser::class, $parser );

		$tokens = [ new Token( Token::T_WHITESPACE ) ];
		$parser = Parser::newFromTokens( $tokens, new Token( Token::T_EOF ) );
		$this->assertInstanceOf( Parser::class, $parser );
	}

	/**
	 * @dataProvider provideParsing
	 * @param string $method One of the parse* methods
	 * @param string $input Input stylesheet
	 * @param CSSObject $expect Expected output
	 * @param array $errors Expected parse errors
	 * @param array $options
	 */
	public function testParsing( $method, $input, $expect, $errors = [], $options = [] ) {
		$parser = Parser::newFromString( $input, $options );

		$output = $parser->$method();

		$this->assertEquals( $expect, $output );
		$this->assertEquals( $errors, $parser->getParseErrors() );

		$parser->clearParseErrors();
		$this->assertEquals( [], $parser->getParseErrors() );
	}

	/**
	 * Class-builder for testing
	 * @param CSSObject $obj
	 * @param array $vars Values for the class's fields
	 * @return object
	 */
	private static function mk( $obj, $vars ) {
		$wrapper = TestingAccessWrapper::newFromObject( $obj );
		foreach ( $vars as $k => $v ) {
			$wrapper->$k = $v;
		}
		return $obj;
	}

	public static function provideParsing() {
		return [
			'Parse a stylesheet' => [
				'parseStylesheet',
				'<!--
				#foo .bar { color: blue; }

				@media (tv) {
					#foo .bar {
						color: red;
						margin: 0 !important
					}
				}
				-->',
				new Stylesheet( new RuleList( [
					self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 2, 5 ] ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'hash', [ 'position' => [ 2, 5 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
							new Token( 'whitespace', [ 'position' => [ 2, 9 ] ] ),
							new Token( 'delim', [ 'position' => [ 2, 10 ], 'value' => '.' ] ),
							new Token( 'ident', [ 'position' => [ 2, 11 ], 'value' => 'bar' ] ),
							new Token( 'whitespace', [ 'position' => [ 2, 14 ] ] )
						] ),
						'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 2, 15 ] ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 2, 16 ] ] ),
								new Token( 'ident', [ 'position' => [ 2, 17 ], 'value' => 'color' ] ),
								new Token( 'colon', [ 'position' => [ 2, 22 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 2, 23 ] ] ),
								new Token( 'ident', [ 'position' => [ 2, 24 ], 'value' => 'blue' ] ),
								new Token( 'semicolon', [ 'position' => [ 2, 28 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 2, 29 ] ] )
							] )
						] )
					] ),
					self::mk(
						new AtRule( new Token( 'at-keyword', [ 'position' => [ 4, 5 ], 'value' => 'media' ] ) ),
						[
							'prelude' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 4, 11 ] ] ),
								self::mk( new SimpleBlock( new Token( '(', [ 'position' => [ 4, 12 ] ] ) ), [
									'value' => new ComponentValueList( [
										new Token( 'ident', [ 'position' => [ 4, 13 ], 'value' => 'tv' ] )
									] )
								] ),
								new Token( 'whitespace', [ 'position' => [ 4, 16 ] ] )
							] ),
							'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 4, 17 ] ] ) ), [
								'value' => new ComponentValueList( [
									new Token( 'whitespace', [ 'position' => [ 4, 18 ] ] ),
									new Token( 'hash', [
										'position' => [ 5, 6 ], 'value' => 'foo', 'typeFlag' => 'id'
									] ),
									new Token( 'whitespace', [ 'position' => [ 5, 10 ] ] ),
									new Token( 'delim', [ 'position' => [ 5, 11 ], 'value' => '.' ] ),
									new Token( 'ident', [ 'position' => [ 5, 12 ], 'value' => 'bar' ] ),
									new Token( 'whitespace', [ 'position' => [ 5, 15 ] ] ),
									self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 5, 16 ] ] ) ), [
										'value' => new ComponentValueList( [
											new Token( 'whitespace', [ 'position' => [ 5, 17 ] ] ),
											new Token( 'ident', [ 'position' => [ 6, 7 ], 'value' => 'color' ] ),
											new Token( 'colon', [ 'position' => [ 6, 12 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 6, 13 ] ] ),
											new Token( 'ident', [ 'position' => [ 6, 14 ], 'value' => 'red' ] ),
											new Token( 'semicolon', [ 'position' => [ 6, 17 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 6, 18 ] ] ),
											new Token( 'ident', [ 'position' => [ 7, 7 ], 'value' => 'margin' ] ),
											new Token( 'colon', [ 'position' => [ 7, 13 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 7, 14 ] ] ),
											new Token( 'number', [
												'position' => [ 7, 15 ],
												'value' => 0,
												'typeFlag' => 'integer',
												'representation' => '0'
											] ),
											new Token( 'whitespace', [ 'position' => [ 7, 16 ] ] ),
											new Token( 'delim', [ 'position' => [ 7, 17 ], 'value' => '!' ] ),
											new Token( 'ident', [ 'position' => [ 7, 18 ], 'value' => 'important' ] ),
											new Token( 'whitespace', [ 'position' => [ 7, 27 ] ] )
										] )
									] ),
									new Token( 'whitespace', [ 'position' => [ 8, 7 ] ] )
								] )
							] )
						]
					)
				] ) )
			],
			'Parse a stylesheet, doesn\'t drop @charset' => [
				'parseStylesheet',
				'@charset "foo"; @bogus;',
				new Stylesheet( new RuleList( [
					self::mk(
						new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 1 ], 'value' => 'charset' ] ) ), [
							'prelude' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 1, 9 ] ] ),
								new Token( 'string', [ 'position' => [ 1, 10 ], 'value' => 'foo' ] ),
							] ),
						]
					),
					new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 17 ], 'value' => 'bogus' ] ) ),
				] ) )
			],

			'Parse a rule list' => [
				'parseRuleList',
				'<!--
				#foo .bar { color: blue; }

				@media (tv) {
					#foo .bar {
						color: red;
						margin: 0 !important
					}
				}
				-->',
				new RuleList( [
					self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 1, 1 ] ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'CDO', [ 'position' => [ 1, 1 ] ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 5 ] ] ),
							new Token( 'hash', [ 'position' => [ 2, 5 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
							new Token( 'whitespace', [ 'position' => [ 2, 9 ] ] ),
							new Token( 'delim', [ 'position' => [ 2, 10 ], 'value' => '.' ] ),
							new Token( 'ident', [ 'position' => [ 2, 11 ], 'value' => 'bar' ] ),
							new Token( 'whitespace', [ 'position' => [ 2, 14 ] ] )
						] ),
						'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 2, 15 ] ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 2, 16 ] ] ),
								new Token( 'ident', [ 'position' => [ 2, 17 ], 'value' => 'color' ] ),
								new Token( 'colon', [ 'position' => [ 2, 22 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 2, 23 ] ] ),
								new Token( 'ident', [ 'position' => [ 2, 24 ], 'value' => 'blue' ] ),
								new Token( 'semicolon', [ 'position' => [ 2, 28 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 2, 29 ] ] )
							] )
						] )
					] ),
					self::mk(
						new AtRule( new Token( 'at-keyword', [ 'position' => [ 4, 5 ], 'value' => 'media' ] ) ), [
							'prelude' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 4, 11 ] ] ),
								self::mk( new SimpleBlock( new Token( '(', [ 'position' => [ 4, 12 ] ] ) ), [
									'value' => new ComponentValueList( [
										new Token( 'ident', [ 'position' => [ 4, 13 ], 'value' => 'tv' ] )
									] )
								] ),
								new Token( 'whitespace', [ 'position' => [ 4, 16 ] ] )
							] ),
							'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 4, 17 ] ] ) ), [
								'value' => new ComponentValueList( [
									new Token( 'whitespace', [ 'position' => [ 4, 18 ] ] ),
									new Token( 'hash', [
										'position' => [ 5, 6 ], 'value' => 'foo', 'typeFlag' => 'id'
									] ),
									new Token( 'whitespace', [ 'position' => [ 5, 10 ] ] ),
									new Token( 'delim', [ 'position' => [ 5, 11 ], 'value' => '.' ] ),
									new Token( 'ident', [ 'position' => [ 5, 12 ], 'value' => 'bar' ] ),
									new Token( 'whitespace', [ 'position' => [ 5, 15 ] ] ),
									self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 5, 16 ] ] ) ), [
										'value' => new ComponentValueList( [
											new Token( 'whitespace', [ 'position' => [ 5, 17 ] ] ),
											new Token( 'ident', [ 'position' => [ 6, 7 ], 'value' => 'color' ] ),
											new Token( 'colon', [ 'position' => [ 6, 12 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 6, 13 ] ] ),
											new Token( 'ident', [ 'position' => [ 6, 14 ], 'value' => 'red' ] ),
											new Token( 'semicolon', [ 'position' => [ 6, 17 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 6, 18 ] ] ),
											new Token( 'ident', [ 'position' => [ 7, 7 ], 'value' => 'margin' ] ),
											new Token( 'colon', [ 'position' => [ 7, 13 ] ] ),
											new Token( 'whitespace', [ 'position' => [ 7, 14 ] ] ),
											new Token( 'number', [
												'position' => [ 7, 15 ],
												'value' => 0,
												'typeFlag' => 'integer',
												'representation' => '0'
											] ),
											new Token( 'whitespace', [ 'position' => [ 7, 16 ] ] ),
											new Token( 'delim', [ 'position' => [ 7, 17 ], 'value' => '!' ] ),
											new Token( 'ident', [ 'position' => [ 7, 18 ], 'value' => 'important' ] ),
											new Token( 'whitespace', [ 'position' => [ 7, 27 ] ] )
										] )
									] ),
									new Token( 'whitespace', [ 'position' => [ 8, 7 ] ] )
								] )
							] )
						]
					)
				] ),
				[ [ 'unexpected-eof-in-rule', 10, 8 ] ]
			],
			'Parse a rule list with comments' => [
				'parseRuleList',
				'#foo {}

				/* @foobar */
				#foo { /* @baz */ color: blue; }',
				new RuleList( [
					self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 1, 1 ] ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'hash', [ 'position' => [ 1, 1 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 5 ] ] )
						] ),
						'block' => new SimpleBlock( new Token( '{', [ 'position' => [ 1, 6 ] ] ) )
					] ),
					self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 4, 5 ] ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'hash', [ 'position' => [ 4, 5 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
							new Token( 'whitespace', [ 'position' => [ 4, 9 ] ] )
						] ),
						'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 4, 10 ] ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 4, 11 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 4, 22 ] ] ),
								new Token( 'ident', [ 'position' => [ 4, 23 ], 'value' => 'color' ] ),
								new Token( 'colon', [ 'position' => [ 4, 28 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 4, 29 ] ] ),
								new Token( 'ident', [ 'position' => [ 4, 30 ], 'value' => 'blue' ] ),
								new Token( 'semicolon', [ 'position' => [ 4, 34 ] ] ),
								new Token( 'whitespace', [ 'position' => [ 4, 35 ] ] )
							] )
						] )
					] )
				] )
			],
			'Parse a rule list containing an invalid rule' => [
				'parseRuleList',
				'#foo {} foo',
				new RuleList( [
					self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 1, 1 ] ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'hash', [ 'position' => [ 1, 1 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 5 ] ] )
						] ),
						'block' => new SimpleBlock( new Token( '{', [ 'position' => [ 1, 6 ] ] ) )
					] )
				] ),
				[ [ 'unexpected-eof-in-rule', 1, 12 ], ]
			],
			'Parse a rule list, doesn\'t drop @charset' => [
				'parseRuleList',
				'@charset "foo"; @bogus;',
				new RuleList( [
					self::mk(
						new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 1 ], 'value' => 'charset' ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 1, 9 ] ] ),
							new Token( 'string', [ 'position' => [ 1, 10 ], 'value' => 'foo' ] ),
						] )
					] ),
					new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 17 ], 'value' => 'bogus' ] ) ),
				] )
			],

			'Parse a rule' => [
				'parseRule',
				'#foo .x { color: blue; }',
				self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 1, 1 ] ] ) ), [
					'prelude' => new ComponentValueList( [
						new Token( 'hash', [ 'position' => [ 1, 1 ], 'value' => 'foo', 'typeFlag' => 'id' ] ),
						new Token( 'whitespace', [ 'position' => [ 1, 5 ] ] ),
						new Token( 'delim', [ 'position' => [ 1, 6 ], 'value' => '.' ] ),
						new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'x' ] ),
						new Token( 'whitespace', [ 'position' => [ 1, 8 ] ] )
					] ),
					'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 1, 9 ] ] ) ), [
						'value' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 1, 10 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 11 ], 'value' => 'color' ] ),
							new Token( 'colon', [ 'position' => [ 1, 16 ] ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 17 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 18 ], 'value' => 'blue' ] ),
							new Token( 'semicolon', [ 'position' => [ 1, 22 ] ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 23 ] ] )
						] )
					] )
				] )
			],
			'Parse a rule, at rule' => [
				'parseRule',
				'@foobar;',
				new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 1 ], 'value' => 'foobar' ] ) )
			],
			'Parse a rule, unterminated at rule' => [
				'parseRule',
				'@foobar',
				new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 1 ], 'value' => 'foobar' ] ) ),
				[ [ 'unexpected-eof-in-rule', 1, 8 ] ]
			],
			'Parse a rule, at rule with comment' => [
				'parseRule',
				'/* @qwerty */ @foobar /* @ignored */ baz /* @ignored */;',
				self::mk(
					new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 15 ], 'value' => 'foobar' ] ) ), [
						'prelude' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 1, 22 ] ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 37 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 38 ], 'value' => 'baz' ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 41 ] ] )
						] )
					]
				)
			],
			'Parse a rule, but no rule' => [
				'parseRule',
				'',
				null,
				[ [ 'unexpected-eof', 1, 1 ], ]
			],
			'Parse a rule, but qualified rule is bad' => [
				'parseRule',
				'x',
				null,
				[ [ 'unexpected-eof-in-rule', 1, 2 ], ]
			],
			'Parse a rule, but not just one rule' => [
				'parseRule',
				'@foobar; @foobaz;',
				null,
				[ [ 'expected-eof', 1, 10 ], ]
			],

			'Parse a declaration' => [
				'parseDeclaration',
				'foo: 32px',
				self::mk(
					new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'foo' ] ) ),
					[
						'value' => new ComponentValueList( [
							new Token( 'dimension', [
								'position' => [ 1, 6 ], 'value' => 32, 'typeFlag' => 'integer',
								'representation' => '32', 'unit' => 'px'
							] )
						] )
					]
				)
			],
			'Parse a declaration, !important' => [
				'parseDeclaration',
				'foo: x !important ',
				self::mk(
					new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'foo' ] ) ),
					[
						'value' => new ComponentValueList( [
							new Token( 'ident', [ 'position' => [ 1, 6 ], 'value' => 'x' ] ),
						] ),
						'important' => true
					]
				)
			],
			'Parse a declaration, !important with whitespace' => [
				'parseDeclaration',
				"foo: x\n\n\n!\n\n\nimportant",
				self::mk(
					new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'foo' ] ) ),
					[
						'value' => new ComponentValueList( [
							new Token( 'ident', [ 'position' => [ 1, 6 ], 'value' => 'x' ] ),
						] ),
						'important' => true
					]
				)
			],
			'Parse a declaration, not a declaration' => [
				'parseDeclaration',
				"@foo: bar",
				null,
				[ [ 'expected-ident', 1, 1 ], ]
			],
			'Parse a declaration, containing non-token component values' => [
				'parseDeclaration',
				'foo: bar (baz;baz2) quux',
				self::mk(
					new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'foo' ] ) ),
					[
						'value' => new ComponentValueList( [
							new Token( 'ident', [ 'position' => [ 1, 6 ], 'value' => 'bar' ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 9 ] ] ),
							self::mk( new SimpleBlock( new Token( '(', [ 'position' => [ 1, 10 ] ] ) ), [
								'value' => new ComponentValueList( [
									new Token( 'ident', [ 'position' => [ 1, 11 ], 'value' => 'baz' ] ),
									new Token( 'semicolon', [ 'position' => [ 1, 14 ] ] ),
									new Token( 'ident', [ 'position' => [ 1, 15 ], 'value' => 'baz2' ] ),
								] ),
							] ),
							new Token( 'whitespace', [ 'position' => [ 1, 20 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 21 ], 'value' => 'quux' ] ),
						] )
					]
				)
			],

			'Parse a declaration-or-at-rule list' => [
				'parseDeclarationOrAtRuleList',
				"color:red;@foo {}x:y;;display:block",
				new DeclarationOrAtRuleList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'color' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'red' ] )
							] )
						]
					),
					self::mk(
						new AtRule( new Token( 'at-keyword', [ 'position' => [ 1, 11 ], 'value' => 'foo' ] ) ), [
							'prelude' => new ComponentValueList( [
								new Token( 'whitespace', [ 'position' => [ 1, 15 ] ] )
							] ),
							'block' => new SimpleBlock( new Token( '{', [ 'position' => [ 1, 16 ] ] ) )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 18 ], 'value' => 'x' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 20 ], 'value' => 'y' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 23 ], 'value' => 'display' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 31 ], 'value' => 'block' ] )
							] )
						]
					)
				] )
			],
			'Parse a declaration-or-at-rule list, invalid token' => [
				'parseDeclarationOrAtRuleList',
				"color:red;!invalid;display:block",
				new DeclarationOrAtRuleList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'color' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'red' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 20 ], 'value' => 'display' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 28 ], 'value' => 'block' ] )
							] )
						]
					)
				] ),
				[ [ 'unexpected-token-in-declaration-list', 1, 11 ], ]
			],
			'Parse a declaration-or-at-rule list, invalid token with complicated resumption' => [
				'parseDeclarationOrAtRuleList',
				"{a:b;c:d;};display:block",
				new DeclarationOrAtRuleList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 12 ], 'value' => 'display' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 20 ], 'value' => 'block' ] )
							] )
						]
					)
				] ),
				[ [ 'unexpected-token-in-declaration-list', 1, 1 ], ]
			],
			'Parse a declaration-or-at-rule list, invalid declaration' => [
				'parseDeclarationOrAtRuleList',
				"color:red;invalid;display:block",
				new DeclarationOrAtRuleList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'color' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'red' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 19 ], 'value' => 'display' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 27 ], 'value' => 'block' ] )
							] )
						]
					)
				] ),
				[ [ 'expected-colon', 1, 18 ] ]
			],
			'Parse a declaration-or-at-rule list, with comments' => [
				'parseDeclarationOrAtRuleList',
				"/*@pp*/a/*@x*/:/*@pp*/b/*@pp*/; /*@pp2*/c:d; /*@pp*/invalid;e:f",
				new DeclarationOrAtRuleList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 8 ], 'value' => 'a' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 23 ], 'value' => 'b' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 41 ], 'value' => 'c' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 43 ], 'value' => 'd' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 61 ], 'value' => 'e' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 63 ], 'value' => 'f' ] )
							] )
						] )
					]
				),
				[ [ 'expected-colon', 1, 60 ] ]
			],

			'Parse a declaration list' => [
				'parseDeclarationList',
				"color:red;@foo {}x:y;;bad;display:block",
				new DeclarationList( [
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'color' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'red' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 18 ], 'value' => 'x' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 20 ], 'value' => 'y' ] )
							] )
						]
					),
					self::mk(
						new Declaration( new Token( 'ident', [ 'position' => [ 1, 27 ], 'value' => 'display' ] ) ), [
							'value' => new ComponentValueList( [
								new Token( 'ident', [ 'position' => [ 1, 35 ], 'value' => 'block' ] )
							] )
						]
					)
				] ),
				[ [ 'unexpected-token-in-declaration-list', 1, 11 ], [ 'expected-colon', 1, 26 ] ]
			],

			'Parse a component value' => [
				'parseComponentValue',
				'  ?  ',
				new Token( 'delim', [ 'position' => [ 1, 3 ], 'value' => '?' ] )
			],
			'Parse a component value, no component value' => [
				'parseComponentValue',
				'  ',
				null,
				[ [ 'unexpected-eof', 1, 3 ], ]
			],
			'Parse a component value, multiple component values' => [
				'parseComponentValue',
				'  ??',
				null,
				[ [ 'expected-eof', 1, 4 ], ]
			],
			'Parse a component value, block' => [
				'parseComponentValue',
				'{!@#}',
				self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 1, 1 ] ] ) ), [
					'value' => new ComponentValueList( [
						new Token( 'delim', [ 'position' => [ 1, 2 ], 'value' => '!' ] ),
						new Token( 'delim', [ 'position' => [ 1, 3 ], 'value' => '@' ] ),
						new Token( 'delim', [ 'position' => [ 1, 4 ], 'value' => '#' ] )
					] )
				] )
			],
			'Parse a component value, unterminated block' => [
				'parseComponentValue',
				'{',
				new SimpleBlock( new Token( '{', [ 'position' => [ 1, 1 ] ] ) ),
				[ [ 'unexpected-eof-in-block', 1, 2 ] ]
			],
			'Parse a component value, function' => [
				'parseComponentValue',
				'calc()',
				new CSSFunction( new Token( 'function', [ 'position' => [ 1, 1 ], 'value' => 'calc' ] ) )
			],
			'Parse a component value, function with args' => [
				'parseComponentValue',
				'calc( a++ )',
				self::mk(
					new CSSFunction( new Token( 'function', [ 'position' => [ 1, 1 ], 'value' => 'calc' ] ) ), [
						'value' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 1, 6 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'a' ] ),
							new Token( 'delim', [ 'position' => [ 1, 8 ], 'value' => '+' ] ),
							new Token( 'delim', [ 'position' => [ 1, 9 ], 'value' => '+' ] ),
							new Token( 'whitespace', [ 'position' => [ 1, 10 ] ] )
						] )
					]
				)
			],
			'Parse a component value, unterminated function' => [
				'parseComponentValue',
				'calc( a++',
				self::mk(
					new CSSFunction( new Token( 'function', [ 'position' => [ 1, 1 ], 'value' => 'calc' ] ) ), [
						'value' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 1, 6 ] ] ),
							new Token( 'ident', [ 'position' => [ 1, 7 ], 'value' => 'a' ] ),
							new Token( 'delim', [ 'position' => [ 1, 8 ], 'value' => '+' ] ),
							new Token( 'delim', [ 'position' => [ 1, 9 ], 'value' => '+' ] )
						] )
					]
				),
				[ [ 'unexpected-eof-in-function', 1, 10 ], ]
			],

			'Parse a list of component values' => [
				'parseComponentValueList',
				',.?{}foo/*@pp*/bar',
				new ComponentValueList( [
					new Token( 'comma', [ 'position' => [ 1, 1 ] ] ),
					new Token( 'delim', [ 'position' => [ 1, 2 ], 'value' => '.' ] ),
					new Token( 'delim', [ 'position' => [ 1, 3 ], 'value' => '?' ] ),
					new SimpleBlock( new Token( '{', [ 'position' => [ 1, 4 ] ] ) ),
					new Token( 'ident', [ 'position' => [ 1, 6 ], 'value' => 'foo' ] ),
					new Token( 'ident', [ 'position' => [ 1, 16 ], 'value' => 'bar' ] )
				] )
			],

			'Parse a list of comma separated component values' => [
				'parseCommaSeparatedComponentValueList',
				',.?{}foo,/*@pp*/bar',
				[
					new ComponentValueList( [] ),
					new ComponentValueList( [
						new Token( 'delim', [ 'position' => [ 1, 2 ], 'value' => '.' ] ),
						new Token( 'delim', [ 'position' => [ 1, 3 ], 'value' => '?' ] ),
						new SimpleBlock( new Token( '{', [ 'position' => [ 1, 4 ] ] ) ),
						new Token( 'ident', [ 'position' => [ 1, 6 ], 'value' => 'foo' ] ),
					] ),
					new ComponentValueList( [
						new Token( 'ident', [ 'position' => [ 1, 17 ], 'value' => 'bar' ] )
					] ),
				]
			],

			'Parse a list of comma separated component values (2)' => [
				'parseCommaSeparatedComponentValueList',
				',,,,',
				[
					new ComponentValueList( [] ),
					new ComponentValueList( [] ),
					new ComponentValueList( [] ),
					new ComponentValueList( [] ),
					new ComponentValueList( [] ),
				]
			],

			'Tokenizer errors are returned' => [
				'parseRule',
				"x\\\n { 'bad\n}",
				self::mk( new QualifiedRule( new Token( 'EOF', [ 'position' => [ 1, 1 ] ] ) ), [
					'prelude' => new ComponentValueList( [
						new Token( 'ident', [ 'position' => [ 1, 1 ], 'value' => 'x' ] ),
						new Token( 'delim', [ 'position' => [ 1, 2 ], 'value' => '\\' ] ),
						new Token( 'whitespace', [ 'position' => [ 1, 3 ] ] )
					] ),
					'block' => self::mk( new SimpleBlock( new Token( '{', [ 'position' => [ 2, 2 ] ] ) ), [
						'value' => new ComponentValueList( [
							new Token( 'whitespace', [ 'position' => [ 2, 3 ] ] ),
							new Token( 'bad-string', [ 'position' => [ 2, 4 ] ] ),
							new Token( 'whitespace', [ 'position' => [ 2, 8 ] ] )
						] )
					] )
				] ),
				[ [ 'bad-escape', 1, 2 ], [ 'newline-in-string', 2, 8 ], ]
			],

			'String conversion' => [
				'parseComponentValueList',
				'@charset "x-user-defined"; főo',
				new ComponentValueList( [
					new Token( 'at-keyword', [ 'position' => [ 1, 1 ], 'value' => 'charset' ] ),
					new Token( 'whitespace', [ 'position' => [ 1, 9 ] ] ),
					new Token( 'string', [ 'position' => [ 1, 10 ], 'value' => 'x-user-defined' ] ),
					new Token( 'semicolon', [ 'position' => [ 1, 26 ] ] ),
					new Token( 'whitespace', [ 'position' => [ 1, 27 ] ] ),
					new Token( 'ident', [ 'position' => [ 1, 28 ], 'value' => "f\xef\x9f\x85\xef\x9e\x91o" ] ),
				] ),
				[],
				[ 'convert' => [] ]
			],

		];
	}

	public function testDepthLimit() {
		$l = ( new ReflectionClass( Parser::class ) )->getConstant( 'CV_DEPTH_LIMIT' );

		// Make sure it's not exceeded by non-nested CVs.
		$parser = Parser::newFromString( str_repeat( 'x ', $l ) );
		$list = $parser->parseComponentValueList();
		$this->assertEquals( $l * 2, $list->count() );
		$this->assertEquals( [], $parser->getParseErrors() );

		// Just at the limit, not over.
		$parser = Parser::newFromString( str_repeat( '{', $l ) . str_repeat( '}', $l ) . '?' );
		$list = $parser->parseComponentValueList();
		$this->assertEquals( 2, $list->count() );
		$this->assertInstanceOf( SimpleBlock::class, $list[0] );
		$this->assertEquals(
			new Token( Token::T_DELIM, [ 'position' => [ 1, 201 ], 'value' => '?' ] ),
			$list[1]
		);
		$this->assertEquals( [], $parser->getParseErrors() );

		$overlimit = str_repeat( '{', $l ) . 'x' . str_repeat( '}', $l ) . '?';

		// This one is over. This also tests "unexpected EOF" error suppression for blocks.
		$parser = Parser::newFromString( $overlimit );
		$list = $parser->parseComponentValueList();
		$this->assertSame( 1, $list->count() );
		$this->assertEquals( [ [ 'recursion-depth-exceeded', 1, 101 ] ], $parser->getParseErrors() );

		// Test "unexpected EOF" error suppression for functions
		$parser = Parser::newFromString( 'foo(' . $overlimit . ')' );
		$list = $parser->parseComponentValue();
		$this->assertEquals( [ [ 'recursion-depth-exceeded', 1, 104 ] ], $parser->getParseErrors() );

		// Test "unexpected EOF" error suppression for at-rules
		$parser = Parser::newFromString( '@foo {' . $overlimit . '}' );
		$list = $parser->parseRule();
		$this->assertEquals( [ [ 'recursion-depth-exceeded', 1, 107 ] ], $parser->getParseErrors() );

		// Test "unexpected EOF" error suppression for qualified rules
		$parser = Parser::newFromString( '.foo {' . $overlimit . '}' );
		$list = $parser->parseRule();
		$this->assertEquals( [ [ 'recursion-depth-exceeded', 1, 107 ] ], $parser->getParseErrors() );
	}
}

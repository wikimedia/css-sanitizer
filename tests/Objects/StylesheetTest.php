<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\Stylesheet
 */
class StylesheetTest extends \PHPUnit_Framework_TestCase {

	public function testClone() {
		$stylesheet = new Stylesheet( new RuleList() );

		$stylesheet2 = clone( $stylesheet );
		$this->assertNotSame( $stylesheet, $stylesheet2 );
		$this->assertNotSame( $stylesheet->getRuleList(), $stylesheet2->getRuleList() );
		$this->assertEquals( $stylesheet, $stylesheet2 );
	}

	public function testBasics() {
		$atToken = new Token( Token::T_AT_KEYWORD, 'foobar' );
		$ws = new Token( Token::T_WHITESPACE );
		$ows = $ws->copyWithSignificance( false );
		$semicolon = new Token( Token::T_SEMICOLON );
		$leftBrace = new Token( Token::T_LEFT_BRACE );
		$rightBrace = new Token( Token::T_RIGHT_BRACE );
		$block = SimpleBlock::newFromDelimiter( Token::T_LEFT_BRACE );

		$list = new RuleList();
		$stylesheet = new Stylesheet( $list );
		$this->assertSame( $list, $stylesheet->getRuleList() );
		$this->assertSame( [ 0, 0 ], $stylesheet->getPosition() );
		$this->assertSame( [], $stylesheet->toTokenArray() );
		$this->assertSame( [], $stylesheet->toComponentValueArray() );
		$this->assertSame( '', (string)$stylesheet );

		$list->add( new AtRule( $atToken ) );
		$list[0]->getPrelude()->add( $ws );
		$this->assertEquals( [ $atToken, $ws, $semicolon ], $stylesheet->toTokenArray() );
		$this->assertEquals( [ $atToken, $ws, $semicolon ], $stylesheet->toComponentValueArray() );
		$this->assertSame( Util::stringify( $stylesheet ), (string)$stylesheet );

		$list[0]->setBlock( $block );
		$this->assertEquals( [ $atToken, $ws, $leftBrace, $rightBrace ], $stylesheet->toTokenArray() );
		$this->assertEquals( [ $atToken, $ws, $block ], $stylesheet->toComponentValueArray() );
		$this->assertSame( Util::stringify( $stylesheet ), (string)$stylesheet );

		$stylesheet = new Stylesheet();
		$this->assertInstanceOf( RuleList::class, $stylesheet->getRuleList() );
	}
}

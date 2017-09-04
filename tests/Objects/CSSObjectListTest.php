<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use InvalidArgumentException;
use Wikimedia\CSS\Util;

/**
 * @covers \Wikimedia\CSS\Objects\CSSObjectList
 */
class CSSObjectListTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Wikimedia\CSS\Objects\TestCSSObjectList may only contain instances of
	 *  Wikimedia\CSS\Objects\TestCSSObjectListItem (found string at index X)
	 */
	public function testException() {
		$item = new TestCSSObjectListItem( 1 );
		new TestCSSObjectList( [ $item, 'X' => 'bad' ] );
	}

	public function testAdd() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$value4 = new TestCSSObjectListItem( 4 );
		$value5 = new TestCSSObjectListItem( 5 );
		$value6 = new TestCSSObjectListItem( 6 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );

		try {
			$list->add( new Token( Token::T_WHITESPACE ) );
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Wikimedia\CSS\Objects\TestCSSObjectList may only contain instances of '
					. 'Wikimedia\CSS\Objects\TestCSSObjectListItem.',
				$ex->getMessage()
			);
		}

		try {
			$list->add( $value1, -1 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Index is out of range.', $ex->getMessage() );
		}
		try {
			$list->add( $value1, 4 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Index is out of range.', $ex->getMessage() );
		}

		$this->assertCount( 3, $list );
		$list->add( $value4 );
		$list->add( $value5, 1 );
		$list->add( $value6, 5 );
		$this->assertCount( 6, $list );
		$this->assertSame(
			[ $value1, $value5, $value2, $value3, $value4, $value6 ],
			iterator_to_array( $list )
		);

		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );
		$list->add( [ $value4, $value5, $value6 ], 1 );
		$this->assertSame(
			[ $value1, $value4, $value5, $value6, $value2, $value3 ],
			iterator_to_array( $list )
		);

		// Test that iterator methods work sanely with insertion
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );
		$list->next();
		$list->next();
		$this->assertSame( $value3, $list->current() );
		// Inserting at the current position keeps the current position
		$list->add( $value4, 2 );
		$this->assertSame( $value4, $list->current() );
		// Inserting before the current position keeps the current item
		$list->add( $value5, 0 );
		$this->assertSame( $value4, $list->current() );
		// Inserting after the current position keeps the current item
		$list->add( $value6, 5 );
		$this->assertSame( $value4, $list->current() );

		// Test multi-item insertion
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );
		$list->next();
		$list->next();
		$list->add( [ $value4, $value5 ], 0 );
		$this->assertSame( $value3, $list->current() );
		$this->assertSame(
			[ $value4, $value5, $value1, $value2, $value3 ],
			iterator_to_array( $list )
		);

		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );
		$list->next();
		$list->next();
		$list->add( new TestCSSObjectList( [ $value4, $value5 ] ), 0 );
		$this->assertSame( $value3, $list->current() );
		$this->assertSame(
			[ $value4, $value5, $value1, $value2, $value3 ],
			iterator_to_array( $list )
		);
	}

	public function testRemove() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$value4 = new TestCSSObjectListItem( 4 );
		$value5 = new TestCSSObjectListItem( 5 );
		$value6 = new TestCSSObjectListItem( 6 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3, $value4, $value5 ] );

		try {
			$list->remove( -1 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Index is out of range.', $ex->getMessage() );
		}
		try {
			$list->remove( 5 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Index is out of range.', $ex->getMessage() );
		}

		$this->assertCount( 5, $list );
		$this->assertSame( $value4, $list->remove( 3 ) );
		$this->assertSame( $value2, $list->remove( 1 ) );
		$this->assertSame( $value5, $list->remove( 2 ) );
		$this->assertCount( 2, $list );
		$this->assertSame(
			[ $value1, $value3 ],
			iterator_to_array( $list )
		);

		// Test that iterator methods work sanely with removal
		$list = new TestCSSObjectList( [ $value1, $value2, $value3, $value4, $value5, $value6 ] );
		$list->next();
		$list->next();
		$list->next();
		$this->assertSame( $value4, $list->current() );
		// Removing before the current position keeps the current item
		$this->assertSame( $value1, $list->remove( 0 ) );
		$this->assertSame( $value4, $list->current() );
		// Removing at the current position moves back one
		$this->assertSame( $value4, $list->remove( 2 ) );
		$this->assertSame( $value3, $list->current() );
		// Removing before the current position still keeps the current item
		$this->assertSame( $value2, $list->remove( 0 ) );
		$this->assertSame( $value3, $list->current() );
		// Removing after the current position keeps the current item
		$this->assertSame( $value6, $list->remove( 2 ) );
		$this->assertSame( $value3, $list->current() );

		// This is why "removing at the current position moves back one"
		$array = [ $value1, $value2, $value3, $value4, $value5 ];
		$list = new TestCSSObjectList( $array );
		$i = 0;
		foreach ( $list as $k => $v ) {
			$this->assertSame( $array[$i++], $v );
			$list->remove( $k );
		}
		$this->assertSame( [], iterator_to_array( $list ) );
	}

	public function testSlice() {
		$val0 = new TestCSSObjectListItem( 0 );
		$val1 = new TestCSSObjectListItem( 1 );
		$val2 = new TestCSSObjectListItem( 2 );
		$val3 = new TestCSSObjectListItem( 3 );
		$val4 = new TestCSSObjectListItem( 4 );
		$val5 = new TestCSSObjectListItem( 5 );
		$val6 = new TestCSSObjectListItem( 6 );
		$list = new TestCSSObjectList( [ $val0, $val1, $val2, $val3, $val4, $val5, $val6 ] );

		$this->assertSame( [ $val2, $val3, $val4 ], $list->slice( 2, 3 ) );
		$this->assertSame( [ $val2, $val3, $val4, $val5, $val6 ], $list->slice( 2 ) );
		$this->assertSame( [ $val5, $val6 ], $list->slice( -2 ) );
		$this->assertSame( [], $list->slice( 7 ) );
		$this->assertSame( [ $val5, $val6 ], $list->slice( 5, 10 ) );
		$this->assertSame( [ $val2, $val3, $val4 ], $list->slice( 2, -2 ) );
	}

	public function testEmpty() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );

		$this->assertCount( 3, $list );
		$list->clear();
		$this->assertCount( 0, $list );
		$this->assertSame( [], iterator_to_array( $list ) );
	}

	public function testSeek() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$value4 = new TestCSSObjectListItem( 4 );
		$value5 = new TestCSSObjectListItem( 5 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3, $value4, $value5 ] );

		$key = $list->key();
		try {
			$list->seek( -1 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		$this->assertSame( $key, $list->key() );
		try {
			$list->seek( 5 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		$this->assertSame( $key, $list->key() );

		$list->next();
		$list->next();
		$this->assertSame( 2, $list->key() );
		$this->assertSame( $value3, $list->current() );
		$list->seek( 4 );
		$this->assertSame( 4, $list->key() );
		$this->assertSame( $value5, $list->current() );
		$list->rewind();
		$this->assertSame( 0, $list->key() );
		$this->assertSame( $value1, $list->current() );
	}

	public function testArrayAccess() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$value4 = new TestCSSObjectListItem( 4 );
		$value5 = new TestCSSObjectListItem( 5 );
		$value6 = new TestCSSObjectListItem( 6 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );

		// Exists
		$this->assertTrue( isset( $list[0] ) );
		$this->assertTrue( isset( $list[1] ) );
		$this->assertTrue( isset( $list[2] ) );
		$this->assertFalse( isset( $list[3] ) );
		$this->assertFalse( isset( $list[-1] ) );
		$this->assertFalse( isset( $list['foo'] ) );

		// Get
		$this->assertSame( $value2, $list[1] );

		try {
			$dummy = $list[-1];
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		try {
			$dummy = $list[5];
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		try {
			$dummy = $list['foo'];
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Offset must be an integer.', $ex->getMessage() );
		}
		try {
			$dummy = $list[1.2];
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Offset must be an integer.', $ex->getMessage() );
		}

		// Set
		$list[1] = $value4;
		$this->assertSame( [ $value1, $value4, $value3 ], iterator_to_array( $list ) );
		$list[3] = $value5;
		$this->assertSame( [ $value1, $value4, $value3, $value5 ], iterator_to_array( $list ) );

		try {
			$list[-1] = $value6;
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		try {
			$list[5] = $value6;
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Offset is out of range.', $ex->getMessage() );
		}
		try {
			$list['foo'] = $value6;
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Offset must be an integer.', $ex->getMessage() );
		}
		try {
			$list[1.2] = $value6;
			$this->fail( 'Expected exception not thrown' );
		} catch ( \InvalidArgumentException $ex ) {
			$this->assertSame( 'Offset must be an integer.', $ex->getMessage() );
		}

		try {
			$list[1] = new Token( Token::T_WHITESPACE );
			$this->fail( 'Expected exception not thrown' );
		} catch ( InvalidArgumentException $ex ) {
			$this->assertSame(
				'Wikimedia\CSS\Objects\TestCSSObjectList may only contain instances of '
					. 'Wikimedia\CSS\Objects\TestCSSObjectListItem.',
				$ex->getMessage()
			);
		}

		$this->assertSame( [ $value1, $value4, $value3, $value5 ], iterator_to_array( $list ) );

		// Unset
		unset( $list[3] );
		$this->assertSame( [ $value1, $value4, $value3 ], iterator_to_array( $list ) );

		try {
			unset( $list[1] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \OutOfBoundsException $ex ) {
			$this->assertSame( 'Cannot leave holes in the list.', $ex->getMessage() );
		}
		$this->assertSame( [ $value1, $value4, $value3 ], iterator_to_array( $list ) );
	}

	/**
	 * @dataProvider provideGetPosition
	 * @param Token[] $tokens
	 * @param array $expect
	 */
	public function testGetPosition( $tokens, $expect ) {
		$list = new TestCSSObjectList( $tokens );
		$this->assertSame( $expect, $list->getPosition() );
	}

	public static function provideGetPosition() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value1->position = [ 1, 7 ];
		$value2 = new TestCSSObjectListItem( 2 );
		$value2->position = [ 2, 1 ];
		$value3 = new TestCSSObjectListItem( 3 );
		$value3->position = [ 2, 3 ];
		$value4 = new TestCSSObjectListItem( 4 );

		return [
			[ [], [ -1, -1 ] ],
			[ [ $value3 ], [ 2, 3 ] ],
			[ [ $value2, $value3 ], [ 2, 1 ] ],
			[ [ $value3, $value2 ], [ 2, 1 ] ],
			[ [ $value1, $value2, $value3, $value4 ], [ 1, 7 ] ],
			[ [ $value1, $value3, $value4, $value2 ], [ 1, 7 ] ],
			[ [ $value2, $value4, $value1, $value3 ], [ 1, 7 ] ],
			[ [ $value4, $value2, $value3, $value1 ], [ 1, 7 ] ],
			[ [ $value4, $value3, $value4, $value1, $value4, $value2, $value4 ], [ 1, 7 ] ],
			[ [ $value3, $value2, $value1 ], [ 1, 7 ] ],
		];
	}

	public function testToTokenArray() {
		$value1 = new TestCSSObjectListItem( 1 );
		$value2 = new TestCSSObjectListItem( 2 );
		$value3 = new TestCSSObjectListItem( 3 );
		$list = new TestCSSObjectList( [ $value1, $value2, $value3 ] );
		$this->assertEquals( [
			new Token( Token::T_STRING, 'T1' ),
			new Token( Token::T_STRING, 'T2' ),
			new Token( Token::T_STRING, 'T3' ),
		], $list->toTokenArray() );
		$this->assertEquals( [
			new Token( Token::T_STRING, 'CV1' ),
			new Token( Token::T_STRING, 'CV2' ),
			new Token( Token::T_STRING, 'CV3' ),
		], $list->toComponentValueArray() );
		$this->assertSame( Util::stringify( $list ), (string)$list );

		$list->separator = [ new Token( Token::T_SEMICOLON ) ];
		$this->assertEquals( [
			new Token( Token::T_STRING, 'T1' ), new Token( Token::T_SEMICOLON ),
			new Token( Token::T_STRING, 'T2' ), new Token( Token::T_SEMICOLON ),
			new Token( Token::T_STRING, 'T3' ), new Token( Token::T_SEMICOLON ),
		], $list->toTokenArray() );
		$this->assertEquals( [
			new Token( Token::T_STRING, 'CV1' ), new Token( Token::T_SEMICOLON ),
			new Token( Token::T_STRING, 'CV2' ), new Token( Token::T_SEMICOLON ),
			new Token( Token::T_STRING, 'CV3' ), new Token( Token::T_SEMICOLON ),
		], $list->toComponentValueArray() );
		$this->assertSame( Util::stringify( $list ), (string)$list );
	}
}

<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Objects;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\CSS\Objects\Token
 * @covers \Wikimedia\CSS\Objects\ComponentValue
 */
class TokenTest extends TestCase {

	protected static function nt( $type, $value, $repr, $typeFlag, $unit = '' ) {
		$hackRepr = $repr !== null && (float)$value !== (float)$repr;
		$data = [
			'value' => $value,
			'typeFlag' => $typeFlag,
			'unit' => $unit,
		];
		if ( !$hackRepr ) {
			$data['representation'] = $repr;
		}

		$token = new Token( $type, $data );

		if ( $hackRepr ) {
			// Assign separately to allow testing the value != representation case.
			TestingAccessWrapper::newFromObject( $token )->representation = $repr;
		}

		return $token;
	}

	public function testCopyWithSignificance() {
		$yes = new Token( Token::T_WHITESPACE );
		$no = new Token( Token::T_WHITESPACE, [ 'significant' => false ] );

		$this->assertTrue( $yes->significant(), 'sanity check' );
		$this->assertFalse( $no->significant(), 'sanity check' );
		$this->assertSame( $yes, $yes->copyWithSignificance( true ) );
		$this->assertSame( $no, $no->copyWithSignificance( false ) );
		$this->assertFalse( $yes->copyWithSignificance( false )->significant() );
		$this->assertTrue( $no->copyWithSignificance( true )->significant() );
	}

	/**
	 * @dataProvider provideConstructor
	 *
	 * @param string $type
	 * @param array|string|null $value
	 * @param array|Exception $expect Expectation overrides
	 */
	public function testConstructor( $type, $value, $expect = [] ) {
		if ( $expect instanceof Exception ) {
			$this->expectException( get_class( $expect ) );
			$this->expectExceptionMessage( $expect->getMessage() );
		} else {
			// We generally expect $type and $value to be reflected in the output
			$expect['type'] = $type;
			$expect += is_string( $value ) ? [ 'value' => $value ] : (array)$value;

			// Defaults for all other fields
			$expect += [
				'type' => 'unset',
				'position' => [ -1, -1 ],
				'significant' => true,
				'value' => '',
				'typeFlag' => '',
				'representation' => null,
				'unit' => '',
				'start' => 0,
			];
			$expect += [
				'end' => $expect['start'],
			];
		}

		if ( $value === null ) {
			$token = new Token( $type );
		} else {
			$token = new Token( $type, $value );
		}

		if ( !$expect instanceof Exception ) {
			// Convert some properties to their accessors
			$expect['getPosition'] = $expect['position'];
			unset( $expect['position'], $expect['start'], $expect['end'] );

			foreach ( $expect as $k => $v ) {
				$this->assertSame( $v, $token->$k(), $k );
			}

			// Test this too
			$this->assertSame( [ $token ], $token->toTokenArray() );

			// Extra junk is ignored
			$value2 = ( is_string( $value ) ? [ 'value' => $value ] : (array)$value ) + [
				'value' => 'XXX',
				'unit' => 'XXX',
			];
			if ( $type !== Token::T_EOF ) {
				$value2 += [ 'typeFlag' => 'XXX' ];
			}
			if ( !in_array( $type, [ Token::T_NUMBER, Token::T_PERCENTAGE, Token::T_DIMENSION ], true ) ) {
				$value2 += [ 'representation' => 'XXX' ];
			}

			$token = new Token( $type, $value2 );
			foreach ( $expect as $k => $v ) {
				$this->assertSame( $v, $token->$k(), $k );
			}
		}
	}

	public static function provideConstructor() {
		$iae = static function ( $msg ) {
			return new InvalidArgumentException( $msg );
		};

		return [
			[ Token::T_EOF, [ 'position' => 1 ], $iae( 'Position must be an array of two integers' ) ],
			[ Token::T_EOF, [ 'position' => [ 1 ] ], $iae( 'Position must be an array of two integers' ) ],
			[ Token::T_EOF, [ 'position' => [ 1, 2, 3 ] ],
				$iae( 'Position must be an array of two integers' ) ],
			[ Token::T_EOF, [ 'position' => [ 1.0, 2 ] ],
				$iae( 'Position must be an array of two integers' ) ],
			[ Token::T_EOF, [ 'position' => [ 1, 2.0 ] ],
				$iae( 'Position must be an array of two integers' ) ],
			[ Token::T_EOF, [ 'position' => [ 42, 23 ] ] ],
			[ Token::T_EOF, [ 'typeFlag' => 'recursion-depth-exceeded' ] ],
			[ Token::T_EOF, [ 'typeFlag' => 'foo' ], $iae( 'Invalid type flag for Token type EOF' ) ],

			[ Token::T_COLON, [ 'significant' => false ] ],

			[ Token::T_IDENT, null, $iae( 'Token type ident requires a value' ) ],
			[ Token::T_IDENT, 'foobar' ],
			[ Token::T_IDENT, [ 'value' => 'foobar' ] ],
			[ Token::T_FUNCTION, null, $iae( 'Token type function requires a value' ) ],
			[ Token::T_FUNCTION, 'foobar' ],
			[ Token::T_AT_KEYWORD, null, $iae( 'Token type at-keyword requires a value' ) ],
			[ Token::T_AT_KEYWORD, 'foobar' ],
			[ Token::T_STRING, null, $iae( 'Token type string requires a value' ) ],
			[ Token::T_STRING, 'foobar' ],
			[ Token::T_URL, null, $iae( 'Token type url requires a value' ) ],
			[ Token::T_URL, 'foobar' ],

			[ Token::T_HASH, null, $iae( 'Token type hash requires a value' ) ],
			[ Token::T_HASH, 'foobar', $iae( 'Token type hash requires a typeFlag' ) ],
			[ Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'foo' ],
				$iae( 'Invalid type flag for Token type hash' ) ],
			[ Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'id' ] ],
			[ Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'unrestricted' ] ],

			[ Token::T_DELIM, null, $iae( 'Token type delim requires a value' ) ],
			[ Token::T_DELIM, 'xx', $iae( 'Value for Token type delim must be a single character' ) ],
			[ Token::T_DELIM, 'x' ],
			[ Token::T_DELIM, '💩' ],

			[ Token::T_NUMBER, null, $iae( 'Token type number requires a numeric value' ) ],
			[ Token::T_NUMBER, 'foo', $iae( 'Token type number requires a numeric value' ) ],
			[ Token::T_NUMBER, 42, $iae( 'Token type number requires a typeFlag' ) ],
			[ Token::T_NUMBER, [ 'value' => 42.5, 'typeFlag' => 'integer' ],
				$iae( "typeFlag is 'integer', but value supplied is not an integer" ) ],
			[ Token::T_NUMBER, [ 'value' => 42.5, 'typeFlag' => 'foobar' ],
				$iae( 'Invalid type flag for Token type number' ) ],
			[ Token::T_NUMBER, [ 'value' => 42, 'typeFlag' => 'integer' ] ],
			[ Token::T_NUMBER, [ 'value' => 42.5, 'typeFlag' => 'number' ] ],
			[ Token::T_NUMBER, [ 'value' => NAN, 'typeFlag' => 'number' ],
				$iae( 'Token type number requires a numeric value' ) ],
			[ Token::T_NUMBER, [ 'value' => INF, 'typeFlag' => 'number' ],
				$iae( 'Token type number requires a numeric value' ) ],
			[ Token::T_NUMBER, [ 'value' => 42.0, 'representation' => '4.2e1', 'typeFlag' => 'number' ] ],
			[ Token::T_NUMBER, [ 'value' => 42.0, 'representation' => '4.2', 'typeFlag' => 'number' ],
				$iae( 'Representation "4.2" does not match value "42"' ) ],
			[ Token::T_NUMBER, [ 'value' => 42, 'representation' => 'foo', 'typeFlag' => 'number' ],
				$iae( 'Representation must be numeric' ) ],

			[ Token::T_PERCENTAGE, null, $iae( 'Token type percentage requires a numeric value' ) ],
			[ Token::T_PERCENTAGE, 'foo', $iae( 'Token type percentage requires a numeric value' ) ],
			[ Token::T_PERCENTAGE, 42, $iae( 'Token type percentage requires a typeFlag' ) ],
			[ Token::T_PERCENTAGE, [ 'value' => 42.5, 'typeFlag' => 'integer' ],
				$iae( "typeFlag is 'integer', but value supplied is not an integer" ) ],
			[ Token::T_PERCENTAGE, [ 'value' => 42.5, 'typeFlag' => 'foobar' ],
				$iae( 'Invalid type flag for Token type percentage' ) ],
			[ Token::T_PERCENTAGE, [ 'value' => 42, 'typeFlag' => 'integer' ] ],
			[ Token::T_PERCENTAGE, [ 'value' => 42.5, 'typeFlag' => 'number' ] ],
			[ Token::T_PERCENTAGE, [ 'value' => NAN, 'typeFlag' => 'number' ],
				$iae( 'Token type percentage requires a numeric value' ) ],
			[ Token::T_PERCENTAGE, [ 'value' => INF, 'typeFlag' => 'number' ],
				$iae( 'Token type percentage requires a numeric value' ) ],
			[ Token::T_PERCENTAGE,
				[ 'value' => 42.0, 'representation' => '4.2e1', 'typeFlag' => 'number' ] ],
			[ Token::T_PERCENTAGE, [ 'value' => 42.0, 'representation' => '4.2', 'typeFlag' => 'number' ],
				$iae( 'Representation "4.2" does not match value "42"' ) ],
			[ Token::T_PERCENTAGE, [ 'value' => 42, 'representation' => 'foo', 'typeFlag' => 'number' ],
				$iae( 'Representation must be numeric' ) ],

			[ Token::T_DIMENSION, null, $iae( 'Token type dimension requires a numeric value' ) ],
			[ Token::T_DIMENSION, 'foo', $iae( 'Token type dimension requires a numeric value' ) ],
			[ Token::T_DIMENSION, 42, $iae( 'Token type dimension requires a typeFlag' ) ],
			[ Token::T_DIMENSION, [ 'value' => 42.5, 'typeFlag' => 'integer' ],
				$iae( "typeFlag is 'integer', but value supplied is not an integer" ) ],
			[ Token::T_DIMENSION, [ 'value' => 42.5, 'typeFlag' => 'foobar' ],
				$iae( 'Invalid type flag for Token type dimension' ) ],
			[ Token::T_DIMENSION, [ 'value' => 42, 'typeFlag' => 'integer' ],
				$iae( 'Token type dimension requires a unit' ) ],
			[ Token::T_DIMENSION, [ 'value' => 42, 'typeFlag' => 'integer', 'unit' => 'x' ] ],
			[ Token::T_DIMENSION, [ 'value' => 42.5, 'typeFlag' => 'number', 'unit' => 'x' ] ],
			[ Token::T_DIMENSION, [ 'value' => NAN, 'typeFlag' => 'number', 'unit' => 'x' ],
				$iae( 'Token type dimension requires a numeric value' ) ],
			[ Token::T_DIMENSION, [ 'value' => INF, 'typeFlag' => 'number', 'unit' => 'x' ],
				$iae( 'Token type dimension requires a numeric value' ) ],
			[ Token::T_DIMENSION,
				[ 'value' => 42.0, 'representation' => '4.2e1', 'typeFlag' => 'number', 'unit' => 'x' ] ],
			[ Token::T_DIMENSION,
				[ 'value' => 42.0, 'representation' => '4.2', 'typeFlag' => 'number', 'unit' => 'x' ],
				$iae( 'Representation "4.2" does not match value "42"' ) ],
			[ Token::T_DIMENSION,
				[ 'value' => 42, 'representation' => 'foo', 'typeFlag' => 'number', 'unit' => 'x' ],
				$iae( 'Representation must be numeric' ) ],

			[ Token::T_BAD_STRING, null ],
			[ Token::T_BAD_URL, null ],
			[ Token::T_WHITESPACE, null ],
			[ Token::T_CDO, null ],
			[ Token::T_CDC, null ],
			[ Token::T_COLON, null ],
			[ Token::T_SEMICOLON, null ],
			[ Token::T_COMMA, null ],
			[ Token::T_LEFT_BRACKET, null ],
			[ Token::T_RIGHT_BRACKET, null ],
			[ Token::T_LEFT_PAREN, null ],
			[ Token::T_RIGHT_PAREN, null ],
			[ Token::T_LEFT_BRACE, null ],
			[ Token::T_RIGHT_BRACE, null ],

			[ 'bogus', null, $iae( 'Unknown token type "bogus"' ) ],
		];
	}

	/**
	 * @dataProvider provideStringification
	 * @param Token $token
	 * @param string $expect
	 */
	public function testStringification( $token, $expect ) {
		$this->assertSame( $expect, (string)$token );
	}

	public static function provideStringification() {
		return [
			[ new Token( Token::T_IDENT, 'foobar' ), 'foobar' ],
			[ new Token( Token::T_IDENT, 'foobar(' ), 'foobar\(' ],
			[ new Token( Token::T_IDENT, '@foobar' ), '\@foobar' ],
			[ new Token( Token::T_IDENT, 'fo#bár' ), 'fo\#bár' ],
			[ new Token( Token::T_IDENT, '9foo9' ), '\39 foo9' ],
			[ new Token( Token::T_IDENT, '-9foo9' ), '-\39 foo9' ],
			[ new Token( Token::T_IDENT, '--foo-' ), '--foo-' ],
			[ new Token( Token::T_IDENT, "foo bar" ), 'foo\ bar' ],
			[ new Token( Token::T_IDENT, "foo\nbar" ), 'foo\a bar' ],
			[ new Token( Token::T_IDENT, "foo\x7fbar" ), 'foo\7f bar' ],
			[ new Token( Token::T_IDENT, "<foo>" ), '\3c foo\3e ' ],

			[ new Token( Token::T_FUNCTION, 'foobar' ), 'foobar(' ],
			[ new Token( Token::T_FUNCTION, 'foobar(' ), 'foobar\((' ],
			[ new Token( Token::T_FUNCTION, '@foobar' ), '\@foobar(' ],
			[ new Token( Token::T_FUNCTION, 'fo#bár' ), 'fo\#bár(' ],
			[ new Token( Token::T_FUNCTION, '9foo9' ), '\39 foo9(' ],
			[ new Token( Token::T_FUNCTION, '-9foo9' ), '-\39 foo9(' ],
			[ new Token( Token::T_FUNCTION, '--foo-' ), '--foo-(' ],
			[ new Token( Token::T_FUNCTION, "foo bar" ), 'foo\ bar(' ],
			[ new Token( Token::T_FUNCTION, "foo\nbar" ), 'foo\a bar(' ],
			[ new Token( Token::T_FUNCTION, "foo\x7fbar" ), 'foo\7f bar(' ],

			[ new Token( Token::T_AT_KEYWORD, 'foobar' ), '@foobar' ],
			[ new Token( Token::T_AT_KEYWORD, 'foobar(' ), '@foobar\(' ],
			[ new Token( Token::T_AT_KEYWORD, '@foobar' ), '@\@foobar' ],
			[ new Token( Token::T_AT_KEYWORD, 'fo#bár' ), '@fo\#bár' ],
			[ new Token( Token::T_AT_KEYWORD, '9foo9' ), '@\39 foo9' ],
			[ new Token( Token::T_AT_KEYWORD, '-9foo9' ), '@-\39 foo9' ],
			[ new Token( Token::T_AT_KEYWORD, '--foo-' ), '@--foo-' ],
			[ new Token( Token::T_AT_KEYWORD, "foo bar" ), '@foo\ bar' ],
			[ new Token( Token::T_AT_KEYWORD, "foo\nbar" ), '@foo\a bar' ],
			[ new Token( Token::T_AT_KEYWORD, "foo\x7fbar" ), '@foo\7f bar' ],

			[ new Token( Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'id' ] ), '#foobar' ],
			[ new Token( Token::T_HASH, [ 'value' => 'foobar(', 'typeFlag' => 'id' ] ), '#foobar\(' ],
			[ new Token( Token::T_HASH, [ 'value' => '@foobar', 'typeFlag' => 'id' ] ), '#\@foobar' ],
			[ new Token( Token::T_HASH, [ 'value' => 'fo#bár', 'typeFlag' => 'id' ] ), '#fo\#bár' ],
			[ new Token( Token::T_HASH, [ 'value' => '9foo9', 'typeFlag' => 'id' ] ), '#\39 foo9' ],
			[ new Token( Token::T_HASH, [ 'value' => '-9foo9', 'typeFlag' => 'id' ] ), '#-\39 foo9' ],
			[ new Token( Token::T_HASH, [ 'value' => '--foo-', 'typeFlag' => 'id' ] ), '#--foo-' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo bar", 'typeFlag' => 'id' ] ), '#foo\ bar' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo\nbar", 'typeFlag' => 'id' ] ), '#foo\a bar' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo\x7fbar", 'typeFlag' => 'id' ] ), '#foo\7f bar' ],

			[ new Token( Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'unrestricted' ] ),
				'#foobar' ],
			[ new Token( Token::T_HASH, [ 'value' => 'foobar(', 'typeFlag' => 'unrestricted' ] ),
				'#foobar\(' ],
			[ new Token( Token::T_HASH, [ 'value' => '@foobar', 'typeFlag' => 'unrestricted' ] ),
				'#\@foobar' ],
			[ new Token( Token::T_HASH, [ 'value' => 'fo#bár', 'typeFlag' => 'unrestricted' ] ),
				'#fo\#bár' ],
			[ new Token( Token::T_HASH, [ 'value' => '9foo9', 'typeFlag' => 'unrestricted' ] ),
				'#9foo9' ],
			[ new Token( Token::T_HASH, [ 'value' => '-9foo9', 'typeFlag' => 'unrestricted' ] ),
				'#-9foo9' ],
			[ new Token( Token::T_HASH, [ 'value' => '--foo-', 'typeFlag' => 'unrestricted' ] ),
				'#--foo-' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo bar", 'typeFlag' => 'unrestricted' ] ),
				'#foo\ bar' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo\nbar", 'typeFlag' => 'unrestricted' ] ),
				'#foo\a bar' ],
			[ new Token( Token::T_HASH, [ 'value' => "foo\x7fbar", 'typeFlag' => 'unrestricted' ] ),
				'#foo\7f bar' ],
			[ new Token( Token::T_HASH, [ 'value' => "<foo>", 'typeFlag' => 'unrestricted' ] ),
				'#\3c foo\3e ' ],

			[ new Token( Token::T_STRING, 'foobar' ), '"foobar"' ],
			[ new Token( Token::T_STRING, "foo\"b\\a\nr\r\f\t\x7f?" ), '"foo\"b\\\\a\a r\d \c \9 \7f ?"' ],
			[ new Token( Token::T_STRING, "<foo>" ), '"\3c foo\3e "' ],

			[ new Token( Token::T_URL, 'http://www.example.com/' ), 'url("http://www.example.com/")' ],
			[ new Token( Token::T_URL, "foo\"b\\a\nr" ), 'url("foo\"b\\\\a\a r")' ],
			[ new Token( Token::T_URL, "foo\"b\\a\nr\r\f\t\x7f?" ), 'url("foo\"b\\\\a\a r\d \c \9 \7f ?")' ],

			[ new Token( Token::T_BAD_STRING ), "'badstring\n" ],
			[ new Token( Token::T_BAD_URL ), "url(badurl'')" ],

			[ new Token( Token::T_DELIM, '*' ), '*' ],
			[ new Token( Token::T_DELIM, '\\' ), "\\\n" ],

			[ self::nt( Token::T_NUMBER, 123, '123', 'integer' ), '123' ],
			[ self::nt( Token::T_NUMBER, 123, '+123', 'integer' ), '+123' ],
			[ self::nt( Token::T_NUMBER, 123, '0124', 'integer' ), '123' ],
			[ self::nt( Token::T_NUMBER, 12.3, '12.30', 'number' ), '12.30' ],
			[ self::nt( Token::T_NUMBER, 12.3, null, 'number' ), '12.3' ],
			[ self::nt( Token::T_NUMBER, 12.3456789, null, 'number' ), '12.3456789' ],
			[ self::nt( Token::T_NUMBER, 100 / 3, null, 'number' ), '33.3333333333333' ],
			[ self::nt( Token::T_NUMBER, 1e100, null, 'number' ), '1.0e+100' ],
			[ self::nt( Token::T_NUMBER, -123, null, 'integer' ), '-123' ],

			[ self::nt( Token::T_PERCENTAGE, 123, '123', 'integer' ), '123%' ],
			[ self::nt( Token::T_PERCENTAGE, 123, '+123', 'integer' ), '+123%' ],
			[ self::nt( Token::T_PERCENTAGE, 123, '0124', 'integer' ), '123%' ],
			[ self::nt( Token::T_PERCENTAGE, 12.3, '12.30', 'number' ), '12.30%' ],
			[ self::nt( Token::T_PERCENTAGE, 12.3, null, 'number' ), '12.3%' ],
			[ self::nt( Token::T_PERCENTAGE, 12.3456789, null, 'number' ), '12.3456789%' ],
			[ self::nt( Token::T_PERCENTAGE, 100 / 3, null, 'number' ), '33.3333333333333%' ],
			[ self::nt( Token::T_PERCENTAGE, 1e100, null, 'number' ), '1.0e+100%' ],
			[ self::nt( Token::T_PERCENTAGE, -123, null, 'integer' ), '-123%' ],

			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', 'px' ), '123px' ],
			[ self::nt( Token::T_DIMENSION, 123, '+123', 'integer', 'px' ), '+123px' ],
			[ self::nt( Token::T_DIMENSION, 123, '0124', 'integer', 'px' ), '123px' ],
			[ self::nt( Token::T_DIMENSION, 12.3, '12.30', 'number', 'px' ), '12.30px' ],
			[ self::nt( Token::T_DIMENSION, 12.3, null, 'number', 'px' ), '12.3px' ],
			[ self::nt( Token::T_DIMENSION, 12.3456789, null, 'number', 'px' ), '12.3456789px' ],
			[ self::nt( Token::T_DIMENSION, 100 / 3, null, 'number', 'px' ), '33.3333333333333px' ],
			[ self::nt( Token::T_DIMENSION, 1e100, null, 'number', 'px' ), '1.0e+100px' ],
			[ self::nt( Token::T_DIMENSION, -123, null, 'integer', 'px' ), '-123px' ],

			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', '9foo' ), '123\39 foo' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', '-9foo' ), '123-\39 foo' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', '--foo' ), '123--foo' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', "foo bar" ), '123foo\ bar' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', "foo\nbar" ), '123foo\a bar' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', "foo\x7fbar" ), '123foo\7f bar' ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', 'e-9' ), '123\65 -9' ],
			[ self::nt( Token::T_DIMENSION, 123, '1.23e2', 'integer', 'e-9' ), '1.23e2e-9' ],

			[ new Token( Token::T_WHITESPACE ), ' ' ],
			[ new Token( Token::T_CDO ), '<!--' ],
			[ new Token( Token::T_CDC ), '-->' ],
			[ new Token( Token::T_COLON ), ':' ],
			[ new Token( Token::T_SEMICOLON ), ';' ],
			[ new Token( Token::T_COMMA ), ',' ],
			[ new Token( Token::T_LEFT_BRACKET ), '[' ],
			[ new Token( Token::T_RIGHT_BRACKET ), ']' ],
			[ new Token( Token::T_LEFT_PAREN ), '(' ],
			[ new Token( Token::T_RIGHT_PAREN ), ')' ],
			[ new Token( Token::T_LEFT_BRACE ), '{' ],
			[ new Token( Token::T_RIGHT_BRACE ), '}' ],
			[ new Token( Token::T_EOF ), '' ],
		];
	}

	public function testStringificationError() {
		$t = new Token( Token::T_WHITESPACE );
		TestingAccessWrapper::newFromObject( $t )->type = 'bogus';
		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Unknown token type "bogus".' );
		$t->__toString();
	}

	/**
	 * @dataProvider provideToComponentValueArray
	 * @param Token $token
	 * @param bool $ok
	 */
	public function testToComponentValueArray( $token, $ok ) {
		if ( !$ok ) {
			$this->expectException( UnexpectedValueException::class );
			$this->expectExceptionMessage(
				"Token type \"{$token->type()}\" is not valid in a ComponentValueList."
			);
		}
		$this->assertSame( [ $token ], $token->toComponentValueArray() );
	}

	public static function provideToComponentValueArray() {
		return [
			[ new Token( Token::T_IDENT, 'foobar' ), true ],
			[ new Token( Token::T_FUNCTION, 'foobar' ), false ],
			[ new Token( Token::T_AT_KEYWORD, 'foobar' ), true ],
			[ new Token( Token::T_HASH, [ 'value' => 'foobar', 'typeFlag' => 'id' ] ), true ],
			[ new Token( Token::T_STRING, 'foobar' ), true ],
			[ new Token( Token::T_URL, 'http://www.example.com/' ), true ],
			[ new Token( Token::T_BAD_STRING ), true ],
			[ new Token( Token::T_BAD_URL ), true ],
			[ new Token( Token::T_DELIM, '*' ), true ],
			[ self::nt( Token::T_NUMBER, 123, '123', 'integer' ), true ],
			[ self::nt( Token::T_PERCENTAGE, 123, '123', 'integer' ), true ],
			[ self::nt( Token::T_DIMENSION, 123, '123', 'integer', 'px' ), true ],
			[ new Token( Token::T_WHITESPACE ), true ],
			[ new Token( Token::T_CDO ), true ],
			[ new Token( Token::T_CDC ), true ],
			[ new Token( Token::T_COLON ), true ],
			[ new Token( Token::T_SEMICOLON ), true ],
			[ new Token( Token::T_COMMA ), true ],
			[ new Token( Token::T_LEFT_BRACKET ), false ],
			[ new Token( Token::T_RIGHT_BRACKET ), true ],
			[ new Token( Token::T_LEFT_PAREN ), false ],
			[ new Token( Token::T_RIGHT_PAREN ), true ],
			[ new Token( Token::T_LEFT_BRACE ), false ],
			[ new Token( Token::T_RIGHT_BRACE ), true ],
			[ new Token( Token::T_EOF ), true ],
		];
	}

	/**
	 * @dataProvider provideSeparate
	 * @param Token $firstToken
	 * @param Token $secondToken
	 * @param bool $expect
	 */
	public function testSeparate( $firstToken, $secondToken, $expect ) {
		$this->assertSame( $expect, Token::separate( $firstToken, $secondToken ) );
	}

	public static function provideSeparate() {
		$identToken = new Token( Token::T_IDENT, 'foo' );
		$atKeywordToken = new Token( Token::T_AT_KEYWORD, 'at' );
		$functionToken = new Token( Token::T_FUNCTION, 'func' );
		$hashToken = new Token( Token::T_HASH, [ 'value' => 'hash', 'typeFlag' => 'id' ] );
		$urlToken = new Token( Token::T_URL, 'http://example.com/' );
		$badurlToken = new Token( Token::T_BAD_URL );
		$numberToken = new Token( Token::T_NUMBER, [ 'value' => 42, 'typeFlag' => 'integer' ] );
		$percentageToken = new Token( Token::T_PERCENTAGE, [ 'value' => 42, 'typeFlag' => 'integer' ] );
		$dimensionToken = new Token( Token::T_DIMENSION,
			[ 'value' => 42, 'typeFlag' => 'integer', 'unit' => 'foo' ] );
		$cdcToken = new Token( Token::T_CDC );
		$parenToken = new Token( Token::T_LEFT_PAREN );

		$minusToken = new Token( Token::T_DELIM, '-' );
		$octothorpeToken = new Token( Token::T_DELIM, '#' );
		$atToken = new Token( Token::T_DELIM, '@' );
		$periodToken = new Token( Token::T_DELIM, '.' );
		$plusToken = new Token( Token::T_DELIM, '+' );
		$starToken = new Token( Token::T_DELIM, '*' );
		$slashToken = new Token( Token::T_DELIM, '/' );
		$ltToken = new Token( Token::T_DELIM, '<' );
		$percentToken = new Token( Token::T_DELIM, '%' );
		$bangToken = new Token( Token::T_DELIM, '!' );

		return [
			[ $identToken, $identToken, true ],
			[ $identToken, $functionToken, true ],
			[ $identToken, $urlToken, true ],
			[ $identToken, $badurlToken, true ],
			[ $identToken, $minusToken, true ],
			[ $identToken, $numberToken, true ],
			[ $identToken, $percentageToken, true ],
			[ $identToken, $dimensionToken, true ],
			[ $identToken, $cdcToken, true ],
			[ $identToken, $parenToken, true ],
			[ $identToken, $starToken, false ],
			[ $identToken, $percentToken, false ],

			[ $atKeywordToken, $identToken, true ],
			[ $atKeywordToken, $functionToken, true ],
			[ $atKeywordToken, $urlToken, true ],
			[ $atKeywordToken, $badurlToken, true ],
			[ $atKeywordToken, $minusToken, true ],
			[ $atKeywordToken, $numberToken, true ],
			[ $atKeywordToken, $percentageToken, true ],
			[ $atKeywordToken, $dimensionToken, true ],
			[ $atKeywordToken, $cdcToken, true ],
			[ $atKeywordToken, $parenToken, false ],
			[ $atKeywordToken, $starToken, false ],
			[ $atKeywordToken, $percentToken, false ],

			[ $hashToken, $identToken, true ],
			[ $hashToken, $functionToken, true ],
			[ $hashToken, $urlToken, true ],
			[ $hashToken, $badurlToken, true ],
			[ $hashToken, $minusToken, true ],
			[ $hashToken, $numberToken, true ],
			[ $hashToken, $percentageToken, true ],
			[ $hashToken, $dimensionToken, true ],
			[ $hashToken, $cdcToken, true ],
			[ $hashToken, $parenToken, false ],
			[ $hashToken, $starToken, false ],
			[ $hashToken, $percentToken, false ],

			[ $dimensionToken, $identToken, true ],
			[ $dimensionToken, $functionToken, true ],
			[ $dimensionToken, $urlToken, true ],
			[ $dimensionToken, $badurlToken, true ],
			[ $dimensionToken, $minusToken, true ],
			[ $dimensionToken, $numberToken, true ],
			[ $dimensionToken, $percentageToken, true ],
			[ $dimensionToken, $dimensionToken, true ],
			[ $dimensionToken, $cdcToken, true ],
			[ $dimensionToken, $parenToken, false ],
			[ $dimensionToken, $starToken, false ],
			[ $dimensionToken, $percentToken, false ],

			[ $octothorpeToken, $identToken, true ],
			[ $octothorpeToken, $functionToken, true ],
			[ $octothorpeToken, $urlToken, true ],
			[ $octothorpeToken, $badurlToken, true ],
			[ $octothorpeToken, $minusToken, true ],
			[ $octothorpeToken, $numberToken, true ],
			[ $octothorpeToken, $percentageToken, true ],
			[ $octothorpeToken, $dimensionToken, true ],
			[ $octothorpeToken, $cdcToken, false ],
			[ $octothorpeToken, $parenToken, false ],
			[ $octothorpeToken, $starToken, false ],
			[ $octothorpeToken, $percentToken, false ],

			[ $minusToken, $identToken, true ],
			[ $minusToken, $functionToken, true ],
			[ $minusToken, $urlToken, true ],
			[ $minusToken, $badurlToken, true ],
			[ $minusToken, $minusToken, true ],
			[ $minusToken, $numberToken, true ],
			[ $minusToken, $percentageToken, true ],
			[ $minusToken, $dimensionToken, true ],
			[ $minusToken, $cdcToken, false ],
			[ $minusToken, $parenToken, false ],
			[ $minusToken, $starToken, false ],
			[ $minusToken, $percentToken, false ],

			[ $numberToken, $identToken, true ],
			[ $numberToken, $functionToken, true ],
			[ $numberToken, $urlToken, true ],
			[ $numberToken, $badurlToken, true ],
			[ $numberToken, $minusToken, false ],
			[ $numberToken, $numberToken, true ],
			[ $numberToken, $percentageToken, true ],
			[ $numberToken, $dimensionToken, true ],
			[ $numberToken, $cdcToken, false ],
			[ $numberToken, $parenToken, false ],
			[ $numberToken, $starToken, false ],
			[ $numberToken, $percentToken, true ],

			[ $atToken, $identToken, true ],
			[ $atToken, $functionToken, true ],
			[ $atToken, $urlToken, true ],
			[ $atToken, $badurlToken, true ],
			[ $atToken, $minusToken, true ],
			[ $atToken, $numberToken, false ],
			[ $atToken, $percentageToken, false ],
			[ $atToken, $dimensionToken, false ],
			[ $atToken, $cdcToken, false ],
			[ $atToken, $parenToken, false ],
			[ $atToken, $starToken, false ],
			[ $atToken, $percentToken, false ],

			[ $periodToken, $identToken, false ],
			[ $periodToken, $functionToken, false ],
			[ $periodToken, $urlToken, false ],
			[ $periodToken, $badurlToken, false ],
			[ $periodToken, $minusToken, false ],
			[ $periodToken, $numberToken, true ],
			[ $periodToken, $percentageToken, true ],
			[ $periodToken, $dimensionToken, true ],
			[ $periodToken, $cdcToken, false ],
			[ $periodToken, $parenToken, false ],
			[ $periodToken, $starToken, false ],
			[ $periodToken, $percentToken, false ],

			[ $plusToken, $identToken, false ],
			[ $plusToken, $functionToken, false ],
			[ $plusToken, $urlToken, false ],
			[ $plusToken, $badurlToken, false ],
			[ $plusToken, $minusToken, false ],
			[ $plusToken, $numberToken, true ],
			[ $plusToken, $percentageToken, true ],
			[ $plusToken, $dimensionToken, true ],
			[ $plusToken, $cdcToken, false ],
			[ $plusToken, $parenToken, false ],
			[ $plusToken, $starToken, false ],
			[ $plusToken, $percentToken, false ],

			[ $slashToken, $identToken, false ],
			[ $slashToken, $functionToken, false ],
			[ $slashToken, $urlToken, false ],
			[ $slashToken, $badurlToken, false ],
			[ $slashToken, $minusToken, false ],
			[ $slashToken, $numberToken, false ],
			[ $slashToken, $percentageToken, false ],
			[ $slashToken, $dimensionToken, false ],
			[ $slashToken, $cdcToken, false ],
			[ $slashToken, $parenToken, false ],
			[ $slashToken, $starToken, true ],
			[ $slashToken, $percentToken, false ],

			// Not required by spec, but help prevent XSS in foreign content (T381617)
			[ $ltToken, $identToken, true ],
			[ $ltToken, $functionToken, true ],
			[ $ltToken, $urlToken, true ],
			[ $ltToken, $badurlToken, true ],
			[ $ltToken, $minusToken, false ],
			[ $ltToken, $numberToken, false ],
			[ $ltToken, $percentageToken, false ],
			[ $ltToken, $cdcToken, false ],
			[ $ltToken, $parenToken, false ],
			[ $ltToken, $starToken, false ],
			[ $ltToken, $percentToken, false ],
			[ $ltToken, $bangToken, true ],
			[ $ltToken, $slashToken, true ],

			// Something not in either table, for good measure
			[ new Token( Token::T_EOF ), new Token( Token::T_EOF ), false ],

			// Hacks for IE bugs
			[ $identToken, $hashToken, true ],
			[ $hashToken, $hashToken, true ],
			[ $dimensionToken, $hashToken, true ],
			[ $numberToken, $hashToken, true ],
		];
	}
}

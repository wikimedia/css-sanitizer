<?php
/**
 * @file
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Wikimedia\CSS\Grammar;

use Wikimedia\CSS\Objects\Token;

/**
 * Matcher factory that does simple url() and urlstring() validation.
 */
class TestMatcherFactory extends MatcherFactory {
	/** @var MatcherFactory|null */
	private static $instance = null;

	/**
	 * Create an instance for test
	 * @return TestMatcherFactory
	 */
	public static function singleton() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** @inheritDoc */
	public function urlstring( $type ) {
		$key = __METHOD__ . ':' . $type;
		if ( !isset( $this->cache[$key] ) ) {
			$this->cache[$key] = new TokenMatcher( Token::T_STRING, static function ( $url ) use ( $type ) {
				return strpos( $url, $type ) !== false;
			} );
		}
		return $this->cache[$key];
	}

	/** @inheritDoc */
	public function url( $type ) {
		$key = __METHOD__ . ':' . $type;
		if ( !isset( $this->cache[$key] ) ) {
			$this->cache[$key] = new UrlMatcher( static function ( $url, $modifiers ) use ( $type ) {
				return strpos( $url, $type ) !== false;
			}, [
				'modifierMatcher' => new Alternative( [
					new KeywordMatcher( [ 'x', 'y' ] ),
					new FunctionMatcher( 'z', new KeywordMatcher( 'z' ) ),
				] ),
			] );
		}
		return $this->cache[$key];
	}
}

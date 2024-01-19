<?php
/**
 * @package Share_On_Mastodon\Notes
 */

namespace Share_On_Mastodon\Notes;

use Share_On_Mastodon\zz\Html\HTMLMinify;

/**
 * ActivityPub filtering and the like.
 */
class ActivityPub {
	/**
	 * Registers callback functions.
	 */
	public static function register() {
		add_filter( 'activitypub_the_content', array( __CLASS__, 'filter_content' ), 100, 2 );
	}

	/**
	 * Filters ActivityPub objects' contents.
	 *
	 * @param  string   $content Original content.
	 * @param  \WP_Post $obj     Post, for now, object.
	 * @return string            Altered content.
	 */
	public static function filter_content( $content, $obj ) {
		$allowed_tags = array(
			'a'          => array(
				'href'  => array(),
				'title' => array(),
				'class' => array(),
				'rel'   => array(),
			),
			'br'         => array(),
			'p'          => array(
				'class' => array(),
			),
			'span'       => array(
				'class' => array(),
			),
			'ul'         => array(),
			'ol'         => array(
				'reversed' => array(),
				'start'    => array(),
			),
			'li'         => array(
				'value' => array(),
			),
			'strong'     => array(
				'class' => array(),
			),
			'b'          => array(
				'class' => array(),
			),
			'i'          => array(
				'class' => array(),
			),
			'em'         => array(
				'class' => array(),
			),
			'blockquote' => array(),
			'cite'       => array(),
			'code'       => array(
				'class' => array(),
			),
			'pre'        => array(
				'class' => array(),
			),
		);

		if ( $obj instanceof \WP_Post ) {
			// We're dealing with a post.
			$content = apply_filters( 'the_content', $obj->post_content );

			$shortlink = wp_get_shortlink( $obj->ID );
			if ( ! empty( $shortlink ) ) {
				$permalink = $shortlink;
			} else {
				$permalink = get_permalink( $obj );
			}

			if ( in_array( $obj->post_type, array( 'post', 'page' ), true ) ) {
				// Strip tags and shorten.
				$content = wp_trim_words( $content, 25, ' […]' ); // Also strips all HTML.

				// Prepend the title.
				$content = '<p><strong>' . get_the_title( $obj ) . '</strong></p><p>' . $content . '</p>';

				// Append a permalink.
				$content .= '<p>(<a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a>)</p>';
			} else {
				// Append only a permalink.
				$content .= '<p>(<a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a>)</p>';
			}
		} elseif ( $obj instanceof \WP_Comment ) {
			$content = apply_filters( 'comment_text', $obj->comment_content, $obj );

			// Append only a permalink.
			$permalink = get_comment_link( $obj );
			$content  .= '<p>(<a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a>)</p>';
		}

		$content = wp_kses( $content, $allowed_tags );

		// Strip whitespace, but ignore `pre` elements' contents.
		$content = preg_replace( '~<pre[^>]*>.*?</pre>(*SKIP)(*FAIL)|\r|\n|\t~s', '', $content );

		// Strip unnecessary whitespace.
		$content = HTMLMinify::minify( $content );

		return trim( $content );
	}
}

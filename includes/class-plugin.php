<?php
/**
 * @package Share_On_Mastodon\Notes
 */

namespace Share_On_Mastodon\Notes;

use Share_On_Mastodon\League\HTMLToMarkdown\Converter\HeaderConverter;
use Share_On_Mastodon\League\HTMLToMarkdown\HtmlConverter;

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * This class's single instance.
	 *
	 * @var Plugin $instance Plugin instance.
	 */
	private static $instance;

	/**
	 * @var \Share_On_Mastodon\League\HTMLToMarkdown\HtmlConverter $converter
	 */
	private $converter;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Plugin This class's single instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers callback functions.
	 */
	public function register() {
		if ( null === $this->converter ) {
			$this->converter = new HtmlConverter();

			// Omit the two spaces before a line break.
			$this->converter->getConfig()->setOption( 'hard_break', true );
			// Pound signs rather than underlines, for `h2` elements.
			$this->converter->getConfig()->setOption( 'header_style', HeaderConverter::STYLE_ATX );
			// PHP's `strip_tags` adds line breaks, as does WP's `wp_strip_all_tags()`.
			$this->converter->getConfig()->setOption( 'strip_tags', true );
		}

		add_filter( 'share_on_mastodon_toot_args', array( $this, 'filter_args' ), 100, 2 );

		ActivityPub::register();
	}

	/**
	 * Filters a status's arguments before it is sent to Mastodon.
	 *
	 * @param  array    $args Status arguments.
	 * @param  \WP_Post $post Post object.
	 * @return array          Altered arguments.
	 */
	public function filter_args( $args, $post ) {
		if ( 'post' === $post->post_type ) {
			// We keep articles "stock," but replace permalinks with a shortlink.
			$status = $args['status'];

			$shortlink = wp_get_shortlink( $post->ID );
			if ( ! empty( $shortlink ) ) {
				$status = str_replace( get_permalink( $post ), $shortlink, $status );
			}

			$args['status'] = $status;
		}

		if ( 'indieblocks_note' === $post->post_type ) {
			// For notes, replace status with post content.
			if ( class_exists( '\\Jetpack_Geo_Location' ) ) {
				// Prevent Jetpack from attaching location details.
				$jp_geo_loc = \Jetpack_Geo_Location::init();
				remove_filter( 'the_content', array( $jp_geo_loc, 'the_content_microformat' ) );
			}

			if ( class_exists( '\\Activitypub\\Hashtag' ) ) {
				$hashtag = remove_filter( 'the_content', array( \Activitypub\Hashtag::class, 'the_content' ) );
			}

			// Apply `the_content` filters so as to have smart quotes and whatnot.
			$status = apply_filters( 'the_content', $post->post_content );

			if ( ! empty( $jp_geo_loc ) ) {
				// Re-add the removed filter.
				add_filter( 'the_content', array( $jp_geo_loc, 'the_content_microformat' ) );
			}

			if ( ! empty( $hashtag ) ) {
				// Re-add the removed filter.
				add_filter( 'the_content', array( \Activitypub\Hashtag::class, 'the_content' ), 10, 2 );
			}

			// Next, attempt to correctly thread replies-to-self.
			$regex = str_replace( array( '.', '~' ), array( '\.', '\~' ), esc_url_raw( home_url( '/' ) ) );
			$regex = '~<div class="u-in-reply-to h-cite">.*?<a.+?href="(' . $regex . '.+?)".*?>.+?</a>.*?</div>~';

			if ( preg_match( $regex, $status, $matches ) ) {
				// Reply to a post of our own.
				$parent_id = url_to_postid( $matches[1] );

				if ( ! empty( $parent_id ) ) {
					// If we found a "parent" post, grab its corresponding Mastodon ID.
					$toot_id = basename( get_post_meta( $parent_id, '_share_on_mastodon_url', true ) );

					if ( ! empty( $toot_id ) ) {
						$args['in_reply_to_id'] = $toot_id;

						// Also, remove introductory line from toot.
						$status = trim( str_replace( $matches[0], '', $status ) );
					}
				} else {
					\Share_On_Mastodon\debug_log( '[Share On Mastodon] Could not convert URL to post ID.' );
				}
			}

			// We want to convert only a small number of HTML tags; anything
			// else (like images) can probably be stripped instead.
			$status = strip_tags( $status, '<p><br><a><em><strong><b><pre><code><blockquote><ul><ol><li><h1><h2><h3><h4><h5><h6>' );
			$status = preg_replace( '~<pre[^>]*"><code[^>]*>(.*?)</code></pre>~s', "<pre>$1</pre>", $status ); //phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired

			// *Now* we can convert to Markdown.
			$status = $this->converter->convert( $status );
			// The converter escapes existing "Markdown," and we occasionally use
			// *syntax* (and so on), so try to retain that.
			$status = str_replace( array( '\*', '\_' ), array( '*', '_' ), $status );
			$status = str_replace( array( '\[', '\]' ), array( '[', ']' ), $status );
			// Remove the `<` and `>` around auto-linked URLs (to prevent them from
			// being stripped).
			$status = preg_replace( '~<(https?://[^>]*)>~', "$1", $status ); // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
			// Replace footnote "fragment" links.
			$status = preg_replace( '~(\[\d+?\])\(#fn-\d+?\)~', "$1", $status ); // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
			$status = trim( $status );

			$hashtags = '';

			// Add tags as hashtags.
			$tags = get_the_tags( $post );

			if ( $tags && ! is_wp_error( $tags ) ) {
				foreach ( $tags as $tag ) {
					$tag_name = $tag->name;

					if ( preg_match( '/\s+/', $tag_name ) ) {
						// Try to "CamelCase" multi-word tags.
						$tag_name = preg_replace( '~(\s|-)+~', ' ', $tag_name );
						$tag_name = explode( ' ', $tag_name );
						$tag_name = implode( '', array_map( 'ucfirst', $tag_name ) );
					}

					$hashtags .= '#' . $tag_name . ' ';
				}
			}

			$hashtags = ! empty( $hashtags ) ? "\n\n" . trim( $hashtags ) : '';

			// Attach shortlink.
			$shortlink = wp_get_shortlink( $post->ID );

			if ( ! empty( $shortlink ) ) {
				$permalink = "\n\n(" . $shortlink . ')';
			} else {
				// Use a "regular" permalink instead.
				$permalink = "\n\n(" . get_permalink( $post ) . ')';
			}

			// Prevent double-encoded entities.
			$status = html_entity_decode( $status, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
			// Remove superfluous line breaks.
			$status = preg_replace( '~\R\R+~', "\n\n", $status );

			// *All* links are considered 23 characters in length. Also, 490
			// rather than 500 because there *might* be shorter links in the
			// body text and so on.
			$max_length  = 490 - mb_strlen( $hashtags, get_bloginfo( 'charset' ) ) - 27; // `27`, because of the parentheses and line breaks.
			$orig_length = mb_strlen( $status, get_bloginfo( 'charset' ) );
			$status      = mb_substr( $status, 0, $max_length, get_bloginfo( 'charset' ) );

			if ( $orig_length > $max_length ) {
				// Append an ellipsis only if status was shortened.
				$status .= '…';
			}

			$status .= $hashtags . $permalink;

			$args['status'] = $status;
		}

		return $args;
	}
}

<?php
/**
 * Plugin Name: Tweak Share on Mastodon for notes
 * Description: Convert (IndieBlocks) notes to Markdown before sharing them on Mastodon, and attempt to thread replies.
 * Author:      Jan Boddez
 * Author URI:  https://jan.boddez.net/
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: share-on-mastodon-notes
 * Version:     0.1.0
 *
 * @author  Jan Boddez <jan@janboddez.be>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package Share_On_Mastodon\Notes
 */

namespace Share_On_Mastodon\Notes;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/build/vendor/autoload.php';

Plugin::get_instance()
	->register();

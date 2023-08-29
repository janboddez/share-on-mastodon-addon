<?php
/**
 * Plugin Name: Share on Mastodon Add-On Plugin
 * Description: Bundles a number of Share on Mastodon "improvements."
 * Author:      Jan Boddez
 * Author URI:  https://jan.boddez.net/
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: share-on-mastodon-notes
 * Version:     0.1.1
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

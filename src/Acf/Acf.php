<?php
namespace WPGraphQLGutenberg\Acf;

use WPGraphQLGutenberg\Blocks\Block;
use WPGraphQLGutenberg\Schema\Types\BlockTypes;

final class ACF {

	public function __construct() {
		add_action( 'acf/init', array( $this, 'init' ) );
	}

	public function init() {
		/**
		 * If dependencies are missing, do not initialize the code
		 */
		if ( false === self::can_load_plugin() ) {
			// Show the admin notice
			add_action( 'admin_init', array( __CLASS__, 'show_admin_notice' ) );

			// Bail
			return;
		}

		if ( class_exists( 'WPGraphQL\ACF\Config' ) ) {
			// Old version wp-graphql-acf integration. https://github.com/wp-graphql/wp-graphql-acf/
			\WPGraphQLGutenberg\Acf\Config::instance();
		}
	}

	/**
	 * Show admin notice to admins if this plugin is active but dependencies are missing
	 * are not active
	 *
	 * @return bool
	 */
	public static function show_admin_notice() {

		/**
		 * For users with lower capabilities, don't show the notice
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		add_action(
			'admin_notices',
			function() {
				?>
				<div class="error notice">
					<p><?php esc_html_e( 'WPGraphQL, Advanced Custom Fields, WPGraphQL for Advanced Custom Fields and  WPGraphQL Gutenberg must be active for "wp-graphql-gutenberg-acf" to work.', 'wp-graphql-gutenberg-acf' ); ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Check whether ACF, WPGraphQL, WPGraphQlACF and WPGraphQLGutenberg are active
	 *
	 * @return bool
	 * @since 0.3
	 */
	public static function can_load_plugin() {
		// Is ACF active?
		if ( ! class_exists( 'ACF' ) ) {
			return false;
		}

		// Is WPGraphQL active?
		if ( ! class_exists( 'WPGraphQL' ) ) {
			return false;
		}

		// is not WPGraphQLGutenbergACF active?
		if ( class_exists( 'WPGraphQLGutenbergACF' ) ) {
			return false;
		}

		// WPGraphQLACF or WPGraphQL\ACF\ACF should be activated?
		if ( ! class_exists( 'WPGraphQLAcf' ) && ! class_exists( 'WPGraphQL\ACF\ACF' ) ) {
			return false;
		}

		return true;
	}
}

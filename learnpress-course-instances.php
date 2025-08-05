<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- File name is correct for the plugin.
/**
 * Plugin Name: LearnPress - Course Instances
 * Description: Adds course scheduling and cohort management to LearnPress
 * Version: 4.0.0
 * Author: Serge Basso
 * Author URI: https://github.com/sergebasso/learnpress-course-instances
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: learnpress
 * Text Domain: learnpress-course-instances
 * Domain Path: /languages/
 * Require_LP_Version: 4.2.6
 *
 * @package learnpress-course-instances
 */

// Prevent loading this file directly.
defined( 'ABSPATH' ) || exit;

const LP_ADDON_COURSE_INSTANCES_FILE = __FILE__;
define( 'LP_ADDON_COURSE_INSTANCES_DIR', plugin_dir_path( __FILE__ ) );
define( 'LP_ADDON_COURSE_INSTANCES_BASENAME', plugin_basename( LP_ADDON_COURSE_INSTANCES_FILE ) );

/**
 * Main plugin class.
 *
 * Handles the core functionality of the LearnPress Course Instances plugin.
 *
 * @since 4.0.0
 */
class LP_Addon_CourseInstances {
	/**
	 * Singleton instance of the main plugin class.
	 *
	 * @since 4.0.0
	 * @var LP_Addon_CourseInstances|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance of the main plugin class.
	 *
	 * Follows the singleton pattern to ensure only one instance
	 * of the plugin exists at any time.
	 *
	 * @since 4.0.0
	 * @return LP_Addon_CourseInstances The single instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor for the main plugin class.
	 *
	 * Registers necessary action hooks to initialize the plugin
	 * after all plugins have been loaded.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	private function __construct() {
		// Check if LearnPress is active before proceeding.
		if ( ! is_plugin_active( 'learnpress/learnpress.php' ) ) {
			add_action( 'admin_notices', array( $this, 'learnpress_required_notice' ) );
			deactivate_plugins( LP_ADDON_COURSE_INSTANCES_BASENAME );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is part of the plugin activation flow
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			return;
		}

		// Load the plugin components after LearnPress is ready.
		add_action( 'learn-press/ready', array( $this, 'load' ) );
	}

	/**
	 * Displays an admin notice when LearnPress is not active.
	 *
	 * This function outputs an error notice in the WordPress admin
	 * informing users that LearnPress plugin is required.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function learnpress_required_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'LearnPress Course Instances requires LearnPress to be installed and activated.', 'learnpress-course-instances' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Load the addon.
	 *
	 * This method checks if LearnPress is installed and active.
	 * If not, it displays a notice. Otherwise, it loads components
	 * and initializes hooks.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function load() {
		require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-database.php';
		// require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-enrollment-manager.php';
		// require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-course-instance-integration.php';
		// require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-course-instance.php';
		// require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-admin.php';
		// require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-frontend.php';

		LP_Addon_CourseInstances_Admin::get_instance();
		// LP_Addon_CourseInstances_Frontend::getInstance();
		// LP_Addon_CourseInstances_Integration::getInstance();
	}

	/**
	 * Activates the plugin and creates necessary database tables.
	 *
	 * This method is called when the plugin is activated and sets up
	 * the required database structure for course instances.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public static function activate() {
		require_once LP_ADDON_COURSE_INSTANCES_DIR . 'includes/class-database.php';
		LP_Addon_CourseInstances_Database::create_tables();
	}

	/**
	 * Deactivates the plugin.
	 *
	 * This method is called when the plugin is deactivated and performs
	 * any necessary cleanup operations.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public static function deactivate() {
		// Cleanup if needed.
	}
}

LP_Addon_CourseInstances::get_instance();

register_activation_hook( __FILE__, array( 'LP_Addon_CourseInstances', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LP_Addon_CourseInstances', 'deactivate' ) );

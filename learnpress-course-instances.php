<?php
/**
 * Plugin Name: LearnPress Course Instances
 * Description: Adds course scheduling and cohort management to LearnPress
 * Version: 1.0.0
 * Author: Serge Basso
 * Author URI: https://github.com/sergebasso/learnpress-course-instances
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: learnpress
 * Text Domain: learnpress-course-instances
 *
 * @package LearnPress_Course_Instances
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LEARNPRESS_COURSE_INSTANCES_VERSION', '1.0.0' );
define( 'LEARNPRESS_COURSE_INSTANCES_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEARNPRESS_COURSE_INSTANCES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * Handles the core functionality of the LearnPress Course Instances plugin.
 *
 * @since 1.0.0
 */
class LearnPress_Course_Instances {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var LearnPress_Course_Instances|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance of this class.
	 *
	 * Follows the singleton pattern to ensure only one instance
	 * of the plugin exists at any time.
	 *
	 * @since 1.0.0
	 * @return LearnPress_Course_Instances The single instance of this class
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
	 * @since 1.0.0
	 * @return void
	 */
	private function __construct() {
		/**
		 * Hook into WordPress 'plugins_loaded' action.
		 *
		 * This ensures that the plugin's initialization method (init) is called
		 * after all plugins have been loaded. Using 'plugins_loaded' hook is important
		 * for proper plugin dependency management, allowing this plugin to interact
		 * with LearnPress or other required plugins only after they are fully loaded.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * This method checks if LearnPress is installed and active.
	 * If not, it displays a notice. Otherwise, it loads components
	 * and initializes hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'LearnPress' ) ) {
			add_action( 'admin_notices', array( $this, 'learnpress_required_notice' ) );
			return;
		}

		// $this->load_includes();
		// $this->init_hooks();
	}

	/**
	 * Loads required plugin files and classes.
	 *
	 * This method includes all necessary component files needed for the plugin functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_includes() {
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-database.php';
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-enrollment-manager.php';
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-course-instance-integration.php';
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-course-instance.php';
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-admin.php';
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-frontend.php';
	}

	/**
	 * Initializes all hooks and components for the plugin.
	 *
	 * This method instantiates admin, frontend, and integration classes
	 * to register their respective hooks and functionalities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		LearnPress_Course_Instances_Admin::getInstance();
		LearnPress_Course_Instances_Frontend::getInstance();
		LearnPress_Course_Instance_Integration::getInstance();
	}

	/**
	 * Displays an admin notice when LearnPress is not active.
	 *
	 * This function outputs an error notice in the WordPress admin
	 * informing users that LearnPress plugin is required.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function learnpress_required_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'LearnPress Course Instances requires LearnPress to be installed and activated.', 'learnpress-course-instances' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Cleanup options and data on plugin deactivation
	 */
	public static function deactivate() {
		// Cleanup if needed.
	}

	/**
	 * Activates the plugin and creates necessary database tables.
	 *
	 * This method is called when the plugin is activated and sets up
	 * the required database structure for course instances.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		require_once LEARNPRESS_COURSE_INSTANCES_PATH . 'includes/class-database.php';
		LearnPress_Course_Instances_Database::create_tables();
	}
}

// Initialize the plugin.
LearnPress_Course_Instances::get_instance();

// Activation/Deactivation hooks.
register_activation_hook( __FILE__, array( 'LearnPress_Course_Instances', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LearnPress_Course_Instances', 'deactivate' ) );

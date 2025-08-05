<?php
/**
 * LearnPress integration for course instances
 */

class NMTO_LearnPress_Course_Instance_Integration {

	private static $instance = null;

	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		// Register course instance as a valid LearnPress item type
		add_filter( 'learn_press_course_item_types', array( $this, 'register_course_instance_type' ) );
		add_filter( 'learn_press_item_type_is_viewable', array( $this, 'make_instance_viewable' ), 10, 2 );

		// Handle course instance access
		add_filter( 'learn_press_user_can_view_item', array( $this, 'validate_instance_access' ), 10, 4 );
		add_filter( 'learn_press_user_can_start_item', array( $this, 'validate_instance_start' ), 10, 4 );

		// Progress tracking for instances
		add_action( 'learn_press_user_item_updated', array( $this, 'sync_instance_progress' ), 10, 3 );

		// Course instance custom post type support (optional)
		add_action( 'init', array( $this, 'maybe_register_instance_post_type' ) );
	}

	/**
	 * Register course instance as a valid item type
	 */
	public function register_course_instance_type( $types ) {
		$types['lp_course_instance'] = array(
			'class'    => 'NMTO_LearnPress_Course_Instance_Item',
			'name'     => __( 'Course Instance', 'learnpress-course-instances' ),
			'callback' => array( $this, 'get_course_instance_object' ),
		);
		return $types;
	}

	/**
	 * Make course instances viewable
	 */
	public function make_instance_viewable( $viewable, $item_type ) {
		if ( $item_type === 'lp_course_instance' ) {
			return true;
		}
		return $viewable;
	}

	/**
	 * Validate access to course instance items
	 */
	public function validate_instance_access( $can_view, $item_id, $course_id, $user_id ) {
		// If this is a course instance item, validate differently
		if ( $this->is_course_instance_context() ) {
			$instance_id = $this->get_current_instance_id();
			if ( $instance_id ) {
				return $this->user_can_access_instance( $user_id, $instance_id );
			}
		}
		return $can_view;
	}

	/**
	 * Validate starting course instance items
	 */
	public function validate_instance_start( $can_start, $item_id, $course_id, $user_id ) {
		if ( $this->is_course_instance_context() ) {
			$instance_id = $this->get_current_instance_id();
			if ( $instance_id ) {
				return $this->user_can_start_instance( $user_id, $instance_id );
			}
		}
		return $can_start;
	}

	/**
	 * Check if user can access a course instance
	 */
	public function user_can_access_instance( $user_id, $instance_id ) {
		if ( ! $user_id ) {
			return false;
		}

		// Check enrollment
		$enrollment = LP_Addon_CourseInstances_Database::get_user_instance_enrollment( $user_id, $instance_id );
		if ( ! $enrollment || $enrollment->status !== 'enrolled' ) {
			return false;
		}

		// Check if instance has started
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );
		if ( $instance ) {
			$now = current_time( 'mysql' );
			return $now >= $instance->start_date;
		}

		return false;
	}

	/**
	 * Check if user can start a course instance
	 */
	public function user_can_start_instance( $user_id, $instance_id ) {
		if ( ! $this->user_can_access_instance( $user_id, $instance_id ) ) {
			return false;
		}

		// Additional start conditions can be added here
		return true;
	}

	/**
	 * Sync progress between course and instance
	 */
	public function sync_instance_progress( $user_item_id, $data, $where ) {
		global $wpdb;

		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		// Get the updated item
		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $user_items_table WHERE user_item_id = %d",
				$user_item_id
			)
		);

		if ( $item && $item->item_type === 'lp_course_instance' ) {
			// Update instance student count if status changed
			if ( isset( $data['status'] ) ) {
				LP_Addon_CourseInstances_Database::update_student_count( $item->item_id );
			}
		}
	}

	/**
	 * Get course instance object for LearnPress
	 */
	public function get_course_instance_object( $instance_id ) {
		return new NMTO_LearnPress_Course_Instance_Item( $instance_id );
	}

	/**
	 * Check if we're in a course instance context
	 */
	private function is_course_instance_context() {
		// This can be enhanced to detect when we're viewing a course through an instance
		return isset( $_GET['instance_id'] ) || isset( $_POST['instance_id'] );
	}

	/**
	 * Get current instance ID from context
	 */
	private function get_current_instance_id() {
		return isset( $_GET['instance_id'] ) ? intval( $_GET['instance_id'] ) : ( isset( $_POST['instance_id'] ) ? intval( $_POST['instance_id'] ) : 0 );
	}

	/**
	 * Optionally register course instance as a custom post type
	 * This allows for more advanced LearnPress integration
	 */
	public function maybe_register_instance_post_type() {
		// Only register if needed for deeper LearnPress integration
		if ( apply_filters( 'nmto_register_instance_post_type', false ) ) {
			register_post_type(
				'lp_course_instance',
				array(
					'label'           => __( 'Course Instances', 'learnpress-course-instances' ),
					'public'          => false,
					'show_ui'         => false,
					'supports'        => array( 'title' ),
					'capability_type' => 'lp_course',
				)
			);
		}
	}
}

/**
 * Course Instance Item class for LearnPress compatibility
 */
class NMTO_LearnPress_Course_Instance_Item {

	private $instance_id;
	private $instance_data;

	public function __construct( $instance_id ) {
		$this->instance_id   = $instance_id;
		$this->instance_data = LP_Addon_CourseInstances_Database::get_instance( $instance_id );
	}

	public function get_id() {
		return $this->instance_id;
	}

	public function get_course_id() {
		return $this->instance_data ? $this->instance_data->course_id : 0;
	}

	public function get_title() {
		return $this->instance_data ? $this->instance_data->instance_name : '';
	}

	public function get_course() {
		if ( $this->instance_data ) {
			return learn_press_get_course( $this->instance_data->course_id );
		}
		return null;
	}

	public function is_viewable() {
		return true;
	}

	public function get_permalink() {
		if ( $this->instance_data ) {
			$course = $this->get_course();
			if ( $course ) {
				return add_query_arg( 'instance_id', $this->instance_id, $course->get_permalink() );
			}
		}
		return '';
	}
}

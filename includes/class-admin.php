<?php
/**
 * Admin class for the LearnPress Course Instances plugin.
 *
 * @since 4.0.0
 */
class LP_Addon_CourseInstances_Admin {
	/**
	 * Singleton instance of the class.
	 *
	 * @since 4.0.0
	 * @var LP_Addon_CourseInstances_Admin|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance of this class.
	 *
	 * Follows the singleton pattern to ensure only one instance
	 * of the plugin exists at any time.
	 *
	 * @since 4.0.0
	 * @return LP_Addon_CourseInstances_Admin The single instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor for the class.
	 *
	 * Initializes the admin functionality by adding necessary hooks and filters.
	 *
	 * @since 4.0.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		// add_action( 'add_meta_boxes', array( $this, 'add_course_meta_boxes' ) );
		// add_action( 'wp_ajax_create_course_instance', array( $this, 'ajax_create_instance' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		// add_action( 'admin_notices', array( $this, 'show_unlinked_enrollments_notice' ) );

		// // Prevent course deletion if instances exist
		// add_action( 'wp_trash_post', array( $this, 'prevent_course_deletion_with_instances' ) );
		// add_action( 'before_delete_post', array( $this, 'prevent_course_deletion_with_instances' ) );
		// add_filter( 'user_has_cap', array( $this, 'remove_delete_capability_for_courses_with_instances' ), 10, 4 );
		// add_action( 'admin_notices', array( $this, 'show_course_deletion_warning' ) );

		// // Handle forced course deletion (when instances are manually removed first)
		// add_action( 'delete_post', array( $this, 'cleanup_course_instances_on_deletion' ) );
	}

	/**
	 * Add Course Instances submenu to the LearnPress admin menu.
	 *
	 * Creates a submenu page for managing course instances under the main LearnPress menu.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'learn_press',
			__( 'Course Instances', 'learnpress-course-instances' ),
			__( 'Course Instances', 'learnpress-course-instances' ),
			'manage_options',
			'learnpress-course-instances',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Renders the admin page for course instances.
	 *
	 * Handles form submissions for creating and deleting instances,
	 * and displays the appropriate tab content based on the current view.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function admin_page() {
		// if ( isset( $_POST['action'] ) && $_POST['action'] === 'create_instance' ) {
		// $this->handle_create_instance();
		// }

		// // Handle instance deletion
		// if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_instance' && isset( $_GET['instance_id'] ) ) {
		// $this->handle_delete_instance();
		// }

		// $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'instances';

		// $courses = get_posts(
		// array(
		// 'post_type'   => 'lp_course',
		// 'numberposts' => -1,
		// 'post_status' => 'publish',
		// )
		// );

		// $instances = LP_Addon_CourseInstances_Database::get_course_instances();

		// // Get unlinked enrollments data if on that tab
		// $unlinked_courses = array();
		// if ( $active_tab === 'unlinked' ) {
		// $unlinked_courses = LP_Addon_CourseInstances_Database::get_courses_with_unlinked_enrollments();
		// }

		include LP_ADDON_COURSE_INSTANCES_DIR . 'templates/admin-page.php';
	}

	public function add_course_meta_boxes() {
		add_meta_box(
			'nmto-course-instances',
			__( 'Course Instances', 'learnpress-course-instances' ),
			array( $this, 'course_instances_meta_box' ),
			'lp_course',
			'normal',
			'default'
		);
	}

	private function handle_delete_instance() {
		$instance_id = intval( $_GET['instance_id'] );

		if ( ! $instance_id ) {
			wp_die( __( 'Invalid instance ID.', 'learnpress-course-instances' ) );
		}

		// Verify nonce
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_instance_' . $instance_id ) ) {
			wp_die( __( 'Security check failed.', 'learnpress-course-instances' ) );
		}

		// Get instance details for logging
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );

		if ( ! $instance ) {
			wp_die( __( 'Instance not found.', 'learnpress-course-instances' ) );
		}

		// Check if instance has enrollments
		if ( $instance->current_students > 0 ) {
			wp_die( __( 'Cannot delete instance with enrolled students. Please remove all students first.', 'learnpress-course-instances' ) );
		}

		// Delete the instance
		$success = LP_Addon_CourseInstances_Database::delete_course_instance( $instance_id );

		if ( $success ) {
			$message = sprintf(
				__( 'Course instance "%s" has been deleted successfully.', 'learnpress-course-instances' ),
				$instance->instance_name
			);

			// Redirect with success message
			wp_redirect( admin_url( 'admin.php?page=learnpress-course-instances&deleted=1&message=' . urlencode( $message ) ) );
			exit;
		} else {
			wp_die( __( 'Failed to delete course instance. Please try again.', 'learnpress-course-instances' ) );
		}
	}

	public function course_instances_meta_box( $post ) {
		$instances = LP_Addon_CourseInstances_Database::get_course_instances( $post->ID );
		include NMTO_LEARNPRESS_COURSE_INSTANCES_PATH . 'templates/course-instances-meta-box.php';
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'learnpress-course-instances' ) !== false || get_current_screen()->post_type === 'lp_course' ) {
			wp_enqueue_script(
				'learnpress-course-instances-admin',
				NMTO_LEARNPRESS_COURSE_INSTANCES_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				NMTO_LEARNPRESS_COURSE_INSTANCES_VERSION,
				true
			);

			wp_localize_script(
				'learnpress-course-instances-admin',
				'nmto_ajax',
				array(
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'admin_url' => admin_url(),
					'nonce'     => wp_create_nonce( 'nmto_learnpress_course_instances_nonce' ),
					'strings'   => array(
						'cannot_delete_with_instances' => __( 'This course cannot be deleted because it has active course instances. Please remove all course instances first.', 'learnpress-course-instances' ),
						'deletion_protected'           => __( 'Deletion Protected', 'learnpress-course-instances' ),
						'manage_instances'             => __( 'Manage Instances', 'learnpress-course-instances' ),
					),
				)
			);

			wp_enqueue_style(
				'learnpress-course-instances-admin',
				NMTO_LEARNPRESS_COURSE_INSTANCES_URL . 'assets/css/admin.css',
				array(),
				NMTO_LEARNPRESS_COURSE_INSTANCES_VERSION
			);
		}

		// Add inline CSS for course deletion protection warnings
		if ( get_current_screen()->post_type === 'lp_course' ) {
			wp_add_inline_style(
				'wp-admin',
				'
                .nmto-instances-table .button.disabled {
                    background: #f6f7f7 !important;
                    border-color: #dcdcde !important;
                    color: #a7aaad !important;
                    cursor: default !important;
                    text-decoration: none !important;
                }
                .status-available { color: #00a32a; font-weight: bold; }
                .status-upcoming { color: #0073aa; font-weight: bold; }
                .status-active { color: #d63638; font-weight: bold; }
                .status-completed { color: #646970; }
                .status-cancelled { color: #d63638; text-decoration: line-through; }
            '
			);
		}
	}

	public function ajax_create_instance() {
		check_ajax_referer( 'nmto_learnpress_course_instances_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'learnpress-course-instances' ) );
		}

		$data = array(
			'course_id'        => intval( $_POST['course_id'] ),
			'instance_name'    => sanitize_text_field( $_POST['instance_name'] ),
			'description'      => sanitize_textarea_field( $_POST['description'] ),
			'start_date'       => sanitize_text_field( $_POST['start_date'] ),
			'end_date'         => sanitize_text_field( $_POST['end_date'] ),
			'enrollment_start' => sanitize_text_field( $_POST['enrollment_start'] ),
			'enrollment_end'   => sanitize_text_field( $_POST['enrollment_end'] ),
			'max_students'     => intval( $_POST['max_students'] ),
			'instructor_id'    => intval( $_POST['instructor_id'] ),
		);

		$instance_id = LP_Addon_CourseInstances_Database::create_course_instance( $data );

		if ( $instance_id ) {
			wp_send_json_success(
				array(
					'message'     => __( 'Course instance created successfully!', 'learnpress-course-instances' ),
					'instance_id' => $instance_id,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to create course instance.', 'learnpress-course-instances' ),
				)
			);
		}
	}

	private function handle_create_instance() {
		if ( ! wp_verify_nonce( $_POST['nmto_nonce'], 'create_course_instance' ) ) {
			return;
		}

		$data = array(
			'course_id'        => intval( $_POST['course_id'] ),
			'instance_name'    => sanitize_text_field( $_POST['instance_name'] ),
			'description'      => sanitize_textarea_field( $_POST['description'] ),
			'start_date'       => sanitize_text_field( $_POST['start_date'] ),
			'end_date'         => sanitize_text_field( $_POST['end_date'] ),
			'enrollment_start' => sanitize_text_field( $_POST['enrollment_start'] ),
			'enrollment_end'   => sanitize_text_field( $_POST['enrollment_end'] ),
			'max_students'     => intval( $_POST['max_students'] ),
			'instructor_id'    => intval( $_POST['instructor_id'] ),
		);

		$instance_id = LP_Addon_CourseInstances_Database::create_course_instance( $data );

		if ( $instance_id ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success"><p>' . __( 'Course instance created successfully!', 'learnpress-course-instances' ) . '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . __( 'Failed to create course instance.', 'learnpress-course-instances' ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Show admin notices for courses with unlinked enrollments
	 */
	public function show_unlinked_enrollments_notice() {
		$screen = get_current_screen();

		// Only show on relevant admin pages
		if ( ! in_array( $screen->id, array( 'edit-lp_course', 'lp_course', 'learnpress_page_learnpress-course-instances' ) ) ) {
			return;
		}

		$total_unlinked = LP_Addon_CourseInstances_Database::get_total_unlinked_enrollments_count();

		if ( $total_unlinked > 0 ) {
			$message = sprintf(
				_n(
					'Warning: %1$d student enrollment is not linked to a course instance. <a href="%2$s">Review unlinked enrollments</a>',
					'Warning: %1$d student enrollments are not linked to course instances. <a href="%2$s">Review unlinked enrollments</a>',
					$total_unlinked,
					'learnpress-course-instances'
				),
				$total_unlinked,
				admin_url( 'admin.php?page=learnpress-course-instances&tab=unlinked' )
			);

			echo '<div class="notice notice-warning is-dismissible">
                <p>' . $message . '</p>
            </div>';
		}
	}

	/**
	 * Display warnings in course meta box for unlinked enrollments
	 */
	public function show_course_unlinked_warning( $course_id ) {
		$unlinked = LP_Addon_CourseInstances_Database::get_course_unlinked_enrollments( $course_id );

		if ( ! empty( $unlinked ) ) {
			$count = count( $unlinked );
			echo '<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px;">
                <p><strong>' . sprintf(
				_n(
					'Warning: %d student is enrolled in this course but not linked to any instance.',
					'Warning: %d students are enrolled in this course but not linked to any instance.',
					$count,
					'learnpress-course-instances'
				),
				$count
			) . '</strong></p>
                <details style="margin-top: 10px;">
                    <summary style="cursor: pointer; font-weight: bold;">View affected students</summary>
                    <ul style="margin: 10px 0 0 20px;">';

			foreach ( $unlinked as $enrollment ) {
				$user_info       = $enrollment->display_name ? $enrollment->display_name : $enrollment->user_email;
				$enrollment_date = date( 'M j, Y', strtotime( $enrollment->start_time ) );
				echo '<li>' . esc_html( $user_info ) . ' (enrolled: ' . $enrollment_date . ')</li>';
			}

			echo '</ul>
                </details>
                <p style="margin-top: 10px;"><em>These students may have limited access to course content. Consider creating course instances and linking their enrollments.</em></p>
            </div>';
		}
	}

	/**
	 * Prevent course deletion if course instances exist
	 */
	public function prevent_course_deletion_with_instances( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'lp_course' ) {
			return;
		}

		// Check if this course has any instances
		$instances = LP_Addon_CourseInstances_Database::get_course_instances( $post_id );

		if ( ! empty( $instances ) ) {
			// Store the error in a transient to show later
			set_transient(
				'nmto_course_deletion_error_' . get_current_user_id(),
				array(
					'course_id'      => $post_id,
					'course_title'   => $post->post_title,
					'instance_count' => count( $instances ),
				),
				30
			);

			// Prevent deletion by redirecting back
			$redirect_url = admin_url( 'edit.php?post_type=lp_course' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Remove delete capability for courses with instances
	 */
	public function remove_delete_capability_for_courses_with_instances( $allcaps, $caps, $args, $user ) {
		if ( empty( $args[0] ) || empty( $args[2] ) ) {
			return $allcaps;
		}

		$action  = $args[0];
		$post_id = $args[2];

		// Check if this is a delete action on an lp_course
		if ( in_array( $action, array( 'delete_post', 'delete_others_posts' ) ) && get_post_type( $post_id ) === 'lp_course' ) {
			$instances = LP_Addon_CourseInstances_Database::get_course_instances( $post_id );

			if ( ! empty( $instances ) ) {
				// Remove delete capability for this specific course
				$allcaps['delete_post']            = false;
				$allcaps['delete_others_posts']    = false;
				$allcaps['delete_published_posts'] = false;
			}
		}

		return $allcaps;
	}

	/**
	 * Show warning notice when course deletion is prevented
	 */
	public function show_course_deletion_warning() {
		$current_screen = get_current_screen();

		// Only show on course edit pages
		if ( ! $current_screen || $current_screen->post_type !== 'lp_course' ) {
			return;
		}

		$user_id    = get_current_user_id();
		$error_data = get_transient( 'nmto_course_deletion_error_' . $user_id );

		if ( $error_data ) {
			delete_transient( 'nmto_course_deletion_error_' . $user_id );

			echo '<div class="notice notice-error is-dismissible">
                <h3>' . __( 'Course Deletion Prevented', 'learnpress-course-instances' ) . '</h3>
                <p><strong>' . sprintf(
					__( 'Cannot delete course "%1$s" because it has %2$d active course instance(s).', 'learnpress-course-instances' ),
					esc_html( $error_data['course_title'] ),
					$error_data['instance_count']
				) . '</strong></p>
                <p>' . __( 'To delete this course, you must first:', 'learnpress-course-instances' ) . '</p>
                <ol style="margin-left: 20px;">
                    <li>' . __( 'Remove all students from course instances', 'learnpress-course-instances' ) . '</li>
                    <li>' . __( 'Delete all course instances', 'learnpress-course-instances' ) . '</li>
                    <li>' . __( 'Then you can safely delete the course', 'learnpress-course-instances' ) . '</li>
                </ol>
                <p>
                    <a href="' . admin_url( 'admin.php?page=learnpress-course-instances' ) . '" class="button button-primary">
                        ' . __( 'Manage Course Instances', 'learnpress-course-instances' ) . '
                    </a>
                </p>
            </div>';
		}

		// Also show warning on individual course edit pages if instances exist
		global $post;
		if ( $post && $post->post_type === 'lp_course' && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			$instances = LP_Addon_CourseInstances_Database::get_course_instances( $post->ID );

			if ( ! empty( $instances ) ) {
				$instance_count  = count( $instances );
				$has_enrollments = false;

				// Check if any instances have enrollments
				foreach ( $instances as $instance ) {
					if ( $instance->current_students > 0 ) {
						$has_enrollments = true;
						break;
					}
				}

				echo '<div class="notice notice-warning">
                    <h3>' . __( 'Course Deletion Protection Active', 'learnpress-course-instances' ) . '</h3>
                    <p>' . sprintf(
						__( 'This course has %d course instance(s) and cannot be deleted.', 'learnpress-course-instances' ),
						$instance_count
					) . '</p>';

				if ( $has_enrollments ) {
					echo '<p><strong>' . __( 'Warning: Some instances have enrolled students. Handle student enrollments before deleting instances.', 'learnpress-course-instances' ) . '</strong></p>';
				}

				echo '<p>
                        <a href="' . admin_url( 'admin.php?page=learnpress-course-instances' ) . '" class="button">
                            ' . __( 'Manage Course Instances', 'learnpress-course-instances' ) . '
                        </a>
                    </p>
                </div>';
			}
		}
	}

	/**
	 * Clean up course instances when a course is actually deleted
	 * This runs after prevention checks, so it only executes if course has no instances
	 * or if instances were manually removed first
	 */
	public function cleanup_course_instances_on_deletion( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'lp_course' ) {
			return;
		}

		// Clean up any remaining course instances and related data
		LP_Addon_CourseInstances_Database::delete_all_course_instances( $post_id );

		// Log the cleanup for debugging
		error_log( 'NMTO Course Instances: Cleaned up instances for deleted course ID: ' . $post_id );
	}
}

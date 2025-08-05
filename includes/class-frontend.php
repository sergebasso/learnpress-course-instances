<?php
/**
 * Frontend functionality for course scheduling
 */

class LearnPress_Course_Instances_Frontend {

	private static $instance = null;

	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'learn_press_after_single_course_summary', array( $this, 'display_course_instances' ), 15 );
		add_action( 'wp_ajax_enroll_course_instance', array( $this, 'ajax_enroll_instance' ) );
		add_action( 'wp_ajax_nopriv_enroll_course_instance', array( $this, 'ajax_enroll_instance' ) );
		add_shortcode( 'nmto_learnpress_course_instances', array( $this, 'course_instances_shortcode' ) );
		add_shortcode( 'nmto_my_enrollments', array( $this, 'my_enrollments_shortcode' ) );

		// Disable direct course enrollment
		$this->disable_direct_course_enrollment();
		$this->check_instance_enrollment_on_course_access();
	}

	public function enqueue_frontend_scripts() {
		if ( is_singular( 'lp_course' ) || is_page() ) {
			wp_enqueue_script(
				'learnpress-course-instances-frontend',
				NMTO_LEARNPRESS_COURSE_INSTANCES_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				NMTO_LEARNPRESS_COURSE_INSTANCES_VERSION,
				true
			);

			wp_localize_script(
				'learnpress-course-instances-frontend',
				'nmto_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'nmto_learnpress_course_instances_nonce' ),
				)
			);

			wp_enqueue_style(
				'learnpress-course-instances-frontend',
				NMTO_LEARNPRESS_COURSE_INSTANCES_URL . 'assets/css/frontend.css',
				array(),
				NMTO_LEARNPRESS_COURSE_INSTANCES_VERSION
			);
		}
	}

	public function display_course_instances() {
		global $post;

		if ( ! $post || $post->post_type !== 'lp_course' ) {
			return;
		}

		$instances = NMTO_LearnPress_Course_Instance::get_available_instances( $post->ID );
		$upcoming  = NMTO_LearnPress_Course_Instance::get_upcoming_instances( $post->ID );

		// Always show the instances section for courses with scheduling
		echo '<div class="nmto-course-instances-wrapper">';
		echo '<h3>' . __( 'Course Schedule & Enrollment', 'learnpress-course-instances' ) . '</h3>';
		echo '<p class="nmto-enrollment-notice">' . __( 'This course requires enrollment in a specific course instance. Please select from the available options below:', 'learnpress-course-instances' ) . '</p>';

		if ( empty( $instances ) && empty( $upcoming ) ) {
			echo '<div class="lp-notice lp-notice-warning">';
			echo '<p>' . __( 'No course instances are currently available for enrollment. Please check back later or contact the administrator.', 'learnpress-course-instances' ) . '</p>';
			echo '</div>';
		} else {
			include NMTO_LEARNPRESS_COURSE_INSTANCES_PATH . 'templates/course-instances.php';
		}

		echo '</div>';
	}

	public function ajax_enroll_instance() {
		check_ajax_referer( 'nmto_learnpress_course_instances_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You must be logged in to enroll.', 'learnpress-course-instances' ),
				)
			);
		}

		$instance_id   = intval( $_POST['instance_id'] );
		$enrollment_id = NMTO_LearnPress_Course_Instance::enroll_user( $instance_id );

		if ( $enrollment_id ) {
			wp_send_json_success(
				array(
					'message'       => __( 'Successfully enrolled in course instance!', 'learnpress-course-instances' ),
					'enrollment_id' => $enrollment_id,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to enroll. Please check if you meet the requirements.', 'learnpress-course-instances' ),
				)
			);
		}
	}

	public function course_instances_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id'      => 0,
				'show_upcoming'  => 'true',
				'show_available' => 'true',
			),
			$atts
		);

		$course_id = intval( $atts['course_id'] );
		if ( ! $course_id && is_singular( 'lp_course' ) ) {
			$course_id = get_the_ID();
		}

		if ( ! $course_id ) {
			return '<p>' . __( 'No course specified.', 'learnpress-course-instances' ) . '</p>';
		}

		ob_start();

		if ( $atts['show_available'] === 'true' ) {
			$instances = NMTO_LearnPress_Course_Instance::get_available_instances( $course_id );
			if ( ! empty( $instances ) ) {
				echo '<div class="nmto-available-instances">';
				echo '<h3>' . __( 'Available Enrollments', 'learnpress-course-instances' ) . '</h3>';
				include NMTO_LEARNPRESS_COURSE_INSTANCES_PATH . 'templates/instance-list.php';
				echo '</div>';
			}
		}

		if ( $atts['show_upcoming'] === 'true' ) {
			$instances = NMTO_LearnPress_Course_Instance::get_upcoming_instances( $course_id );
			if ( ! empty( $instances ) ) {
				echo '<div class="nmto-upcoming-instances">';
				echo '<h3>' . __( 'Upcoming Sessions', 'learnpress-course-instances' ) . '</h3>';
				include NMTO_LEARNPRESS_COURSE_INSTANCES_PATH . 'templates/instance-list.php';
				echo '</div>';
			}
		}

		return ob_get_clean();
	}

	public function my_enrollments_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'You must be logged in to view your enrollments.', 'learnpress-course-instances' ) . '</p>';
		}

		$user_id     = get_current_user_id();
		$enrollments = LP_Addon_CourseInstances_Database::get_user_enrollments( $user_id );

		if ( empty( $enrollments ) ) {
			return '<p>' . __( 'You are not enrolled in any course instances.', 'learnpress-course-instances' ) . '</p>';
		}

		ob_start();
		include NMTO_LEARNPRESS_COURSE_INSTANCES_PATH . 'templates/user-enrollments.php';
		return ob_get_clean();
	}

	private function disable_direct_course_enrollment() {
		// Remove LearnPress enrollment buttons and forms
		add_filter( 'learn-press/course-buttons', array( $this, 'remove_enrollment_buttons' ), 10, 2 );
		add_filter( 'learn_press_user_can_enroll_course', array( $this, 'prevent_direct_enrollment' ), 10, 3 );

		// Hide purchase/enroll buttons via CSS as backup
		add_action( 'wp_head', array( $this, 'hide_enrollment_elements' ) );

		// Redirect enrollment attempts to course instances
		add_action( 'learn_press_before_enroll_course', array( $this, 'redirect_to_instances' ) );
	}

	public function remove_enrollment_buttons( $buttons, $course_id ) {
		// Remove enroll, purchase, and external link buttons
		unset( $buttons['enroll'] );
		unset( $buttons['purchase'] );
		unset( $buttons['external'] );
		unset( $buttons['retake'] );

		return $buttons;
	}

	public function prevent_direct_enrollment( $can_enroll, $course_id, $user_id ) {
		// Always prevent direct enrollment - users must go through instances
		return false;
	}

	public function hide_enrollment_elements() {
		if ( is_singular( 'lp_course' ) ) {
			echo '<style>
                /* Hide all direct enrollment buttons and forms */
                .course-buttons .lp-button,
                .course-purchase-box,
                .learn-press-course-buttons,
                .course-payment-form,
                .lp-course-buttons,
                .lp-button.lp-button-enroll,
                .lp-button.lp-button-purchase,
                .lp-button.lp-button-external,
                .lp-button.lp-button-retake,
                .course-item-progress,
                .lp-course-progress,
                .course-curriculum .course-item .item-status,
                .course-curriculum .course-item .item-meta,
                .lp-course-progress-wrapper {
                    display: none !important;
                }

                /* Hide course start/continue buttons */
                .lp-button.lp-button-start,
                .lp-button.lp-button-continue,
                .course-continue,
                .course-start {
                    display: none !important;
                }

                /* Make instances section prominent */
                .nmto-course-instances-wrapper {
                    margin-top: 20px;
                    border: 2px solid #0073aa;
                    border-radius: 5px;
                    padding: 20px;
                    background: #f9f9f9;
                }

                .nmto-enrollment-notice {
                    font-weight: bold;
                    color: #0073aa;
                    margin-bottom: 15px;
                }
            </style>';
		}
	}

	public function redirect_to_instances() {
		global $post;

		if ( $post && $post->post_type === 'lp_course' ) {
			// Get course instances
			$instances = NMTO_LearnPress_Course_Instance::get_available_instances( $post->ID );

			if ( ! empty( $instances ) ) {
				wp_die(
					__( 'Direct enrollment is not available for this course. Please select a specific course instance below.', 'learnpress-course-instances' ),
					__( 'Enrollment via Course Instance Required', 'learnpress-course-instances' ),
					array( 'back_link' => true )
				);
			}
		}
	}

	public function check_instance_enrollment_on_course_access() {
		// Hook into course access to ensure user enrolled through an instance
		add_filter( 'learn_press_user_can_view_course', array( $this, 'validate_instance_enrollment' ), 10, 3 );
		add_action( 'learn_press_before_single_course_content', array( $this, 'show_instance_enrollment_notice' ) );

		// Prevent course progression without instance enrollment
		add_filter( 'learn_press_user_can_start_course', array( $this, 'validate_course_start' ), 10, 3 );
		add_filter( 'learn_press_user_can_finish_course', array( $this, 'validate_course_completion' ), 10, 3 );
		add_filter( 'learn_press_user_can_retake_course', array( $this, 'validate_course_retake' ), 10, 3 );

		// Also prevent access to course items (lessons, quizzes) without instance enrollment
		add_filter( 'learn_press_user_can_view_item', array( $this, 'validate_item_access' ), 10, 4 );
		add_filter( 'learn_press_user_can_start_item', array( $this, 'validate_item_start' ), 10, 4 );
		add_filter( 'learn_press_user_can_finish_item', array( $this, 'validate_item_completion' ), 10, 4 );

		// Block course enrollment API calls
		add_filter( 'learn_press_before_user_enroll_course', array( $this, 'block_direct_api_enrollment' ), 10, 3 );
	}

	public function validate_instance_enrollment( $can_view, $course_id, $user_id ) {
		if ( ! $user_id ) {
			return $can_view; // Not logged in, let LearnPress handle
		}

		// Check if user has any enrollment for this course
		$enrollment = LP_Addon_CourseInstances_Database::get_user_course_enrollment( $user_id, $course_id );

		if ( $enrollment ) {
			// Check if enrollment is linked to an instance
			$instance_link = learn_press_get_user_item_meta( $enrollment->user_item_id, '_nmto_instance_id', true );

			if ( ! $instance_link ) {
				// User enrolled directly, not through instance - prevent access
				return false;
			}
		}

		return $can_view;
	}

	public function validate_item_access( $can_view, $item_id, $course_id, $user_id ) {
		if ( ! $user_id || ! $course_id ) {
			return $can_view;
		}

		// Check if user has enrollment linked to an instance
		$enrollment = LP_Addon_CourseInstances_Database::get_user_course_enrollment( $user_id, $course_id );

		if ( $enrollment ) {
			$instance_link = learn_press_get_user_item_meta( $enrollment->user_item_id, '_nmto_instance_id', true );

			if ( ! $instance_link ) {
				// User enrolled directly, not through instance - prevent item access
				return false;
			}

			// Check if instance dates are valid (course has started)
			$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_link );
			if ( $instance ) {
				$now = current_time( 'mysql' );
				if ( $now < $instance->start_date ) {
					// Course hasn't started yet
					return false;
				}
			}
		}

		return $can_view;
	}

	public function show_instance_enrollment_notice() {
		global $post;

		if ( ! is_user_logged_in() || ! $post || $post->post_type !== 'lp_course' ) {
			return;
		}

		$user_id    = get_current_user_id();
		$enrollment = LP_Addon_CourseInstances_Database::get_user_course_enrollment( $user_id, $post->ID );

		if ( ! $enrollment ) {
			echo '<div class="lp-notice lp-notice-warning">
                <p>' . __( 'You must enroll in a specific course instance to access this course. Please select an available instance below.', 'learnpress-course-instances' ) . '</p>
            </div>';
		}
	}

	public function show_direct_enrollment_warning() {
		echo '<div class="lp-notice lp-notice-warning">
            <p>' . __( 'Warning: You are enrolled in this course but not linked to a specific course instance. Some features may not work correctly. Please contact the administrator.', 'learnpress-course-instances' ) . '</p>
        </div>';
	}

	/**
	 * Validate course start - only allow if enrolled via instance and instance has started
	 */
	public function validate_course_start( $can_start, $course_id, $user_id ) {
		if ( ! $user_id ) {
			return false;
		}

		$enrollment = LP_Addon_CourseInstances_Database::get_user_course_enrollment( $user_id, $course_id );

		if ( ! $enrollment ) {
			return false; // Not enrolled at all
		}

		$instance_link = learn_press_get_user_item_meta( $enrollment->user_item_id, '_nmto_instance_id', true );

		if ( ! $instance_link ) {
			return false; // Not linked to instance
		}

		// Check if instance has started
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_link );
		if ( $instance ) {
			$now = current_time( 'mysql' );
			return $now >= $instance->start_date;
		}

		return false;
	}

	/**
	 * Validate course completion
	 */
	public function validate_course_completion( $can_finish, $course_id, $user_id ) {
		return $this->validate_instance_access( $user_id, $course_id );
	}

	/**
	 * Validate course retake
	 */
	public function validate_course_retake( $can_retake, $course_id, $user_id ) {
		return $this->validate_instance_access( $user_id, $course_id );
	}

	/**
	 * Validate item start (lessons, quizzes)
	 */
	public function validate_item_start( $can_start, $item_id, $course_id, $user_id ) {
		return $this->validate_instance_access( $user_id, $course_id );
	}

	/**
	 * Validate item completion
	 */
	public function validate_item_completion( $can_finish, $item_id, $course_id, $user_id ) {
		return $this->validate_instance_access( $user_id, $course_id );
	}

	/**
	 * Helper method to validate instance access
	 */
	private function validate_instance_access( $user_id, $course_id ) {
		if ( ! $user_id || ! $course_id ) {
			return false;
		}

		$enrollment = LP_Addon_CourseInstances_Database::get_user_course_enrollment( $user_id, $course_id );

		if ( ! $enrollment ) {
			return false;
		}

		$instance_link = learn_press_get_user_item_meta( $enrollment->user_item_id, '_nmto_instance_id', true );

		if ( ! $instance_link ) {
			return false; // Not linked to instance
		}

		// Check if instance has started
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_link );
		if ( $instance ) {
			$now = current_time( 'mysql' );
			return $now >= $instance->start_date;
		}

		return false;
	}

	/**
	 * Block direct enrollment via API calls
	 */
	public function block_direct_api_enrollment( $course_id, $user_id, $force = false ) {
		// Always block direct enrollment unless it's coming from our plugin
		if ( ! defined( 'NMTO_INSTANCE_ENROLLMENT' ) || ! NMTO_INSTANCE_ENROLLMENT ) {
			wp_die(
				__( 'Direct course enrollment is not permitted. Please enroll through a course instance.', 'learnpress-course-instances' ),
				__( 'Enrollment Restricted', 'learnpress-course-instances' ),
				array( 'response' => 403 )
			);
		}

		return $course_id;
	}
}

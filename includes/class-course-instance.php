<?php
/**
 * Course Instance management
 */

class NMTO_LearnPress_Course_Instance {

	public static function can_enroll( $instance_id, $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );
		if ( ! $instance ) {
			return false;
		}

		$now = current_time( 'mysql' );

		// Check enrollment period
		if ( $now < $instance->enrollment_start || $now > $instance->enrollment_end ) {
			return false;
		}

		// Check capacity
		if ( $instance->max_students > 0 && $instance->current_students >= $instance->max_students ) {
			return false;
		}

		// Check if already enrolled in this specific instance
		$existing = NMTO_LearnPress_Course_Enrollment_Manager::get_user_instance_enrollment( $user_id, $instance_id );

		return ! $existing;
	}

	public static function get_available_instances( $course_id = null ) {
		$instances = LP_Addon_CourseInstances_Database::get_course_instances( $course_id, 'active' );
		$available = array();

		$now = current_time( 'mysql' );

		foreach ( $instances as $instance ) {
			// Only show instances that are currently accepting enrollments
			if ( $now >= $instance->enrollment_start && $now <= $instance->enrollment_end ) {
				$available[] = $instance;
			}
		}

		return $available;
	}

	public static function get_upcoming_instances( $course_id = null ) {
		$instances = LP_Addon_CourseInstances_Database::get_course_instances( $course_id, 'active' );
		$upcoming  = array();

		$now = current_time( 'mysql' );

		foreach ( $instances as $instance ) {
			// Only show instances that haven't started yet
			if ( $now < $instance->start_date ) {
				$upcoming[] = $instance;
			}
		}

		return $upcoming;
	}

	public static function enroll_user( $instance_id, $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! self::can_enroll( $instance_id, $user_id ) ) {
			return false;
		}

		$enrollment_id = NMTO_LearnPress_Course_Enrollment_Manager::enroll_user( $instance_id, $user_id );

		if ( $enrollment_id ) {
			// Trigger action for other plugins to hook into
			do_action( 'nmto_learnpress_course_instances_user_enrolled', $user_id, $instance_id, $enrollment_id );

			// Send enrollment email
			self::send_enrollment_email( $user_id, $instance_id );

			return $enrollment_id;
		}

		return false;
	}

	public static function send_enrollment_email( $user_id, $instance_id ) {
		$user     = get_user_by( 'id', $user_id );
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );

		if ( ! $user || ! $instance ) {
			return false;
		}

		$course = get_post( $instance->course_id );
		if ( ! $course ) {
			return false;
		}

		$subject = sprintf(
			__( 'Enrollment Confirmation: %1$s - %2$s', 'learnpress-course-instances' ),
			$course->post_title,
			$instance->instance_name
		);

		$message = sprintf(
			__( "Dear %1\$s,\n\nYou have successfully enrolled in:\n\nCourse: %2\$s\nInstance: %3\$s\nStart Date: %4\$s\nEnd Date: %5\$s\n\nWe look forward to seeing you in class!\n\nBest regards,\nNMTO Team", 'learnpress-course-instances' ),
			$user->display_name,
			$course->post_title,
			$instance->instance_name,
			date( 'F j, Y', strtotime( $instance->start_date ) ),
			date( 'F j, Y', strtotime( $instance->end_date ) )
		);

		return wp_mail( $user->user_email, $subject, $message );
	}

	public static function get_instance_status( $instance_id ) {
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );
		if ( ! $instance ) {
			return 'not_found';
		}

		$now = current_time( 'mysql' );

		if ( $now < $instance->enrollment_start ) {
			return 'enrollment_not_started';
		} elseif ( $now > $instance->enrollment_end ) {
			return 'enrollment_closed';
		} elseif ( $now < $instance->start_date ) {
			return 'enrollment_open';
		} elseif ( $now > $instance->end_date ) {
			return 'completed';
		} else {
			return 'in_progress';
		}
	}

	public static function format_status_label( $status ) {
		$labels = array(
			'enrollment_not_started' => __( 'Enrollment Opens Soon', 'learnpress-course-instances' ),
			'enrollment_open'        => __( 'Enrollment Open', 'learnpress-course-instances' ),
			'enrollment_closed'      => __( 'Enrollment Closed', 'learnpress-course-instances' ),
			'in_progress'            => __( 'In Progress', 'learnpress-course-instances' ),
			'completed'              => __( 'Completed', 'learnpress-course-instances' ),
			'not_found'              => __( 'Not Found', 'learnpress-course-instances' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}
}

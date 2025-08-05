<?php
/**
 * Enrollment compatibility layer
 */

class NMTO_LearnPress_Course_Enrollment_Manager {

	/**
	 * Enroll user in course instance using the appropriate method
	 */
	public static function enroll_user( $instance_id, $user_id ) {
		return LP_Addon_CourseInstances_Database::enroll_student( $instance_id, $user_id );
	}

	/**
	 * Get user enrollments using the appropriate method
	 */
	public static function get_user_enrollments( $user_id, $status = 'enrolled' ) {
		return LP_Addon_CourseInstances_Database::get_user_enrollments( $user_id, $status );
	}

	/**
	 * Get user enrollment for specific instance
	 */
	public static function get_user_instance_enrollment( $user_id, $instance_id ) {
		return LP_Addon_CourseInstances_Database::get_user_instance_enrollment( $user_id, $instance_id );
	}

	/**
	 * Update student count for instance
	 */
	public static function update_student_count( $instance_id ) {
		LP_Addon_CourseInstances_Database::update_student_count( $instance_id );
	}

	/**
	 * Check if user can access course content via instance enrollment
	 */
	public static function user_can_access_course( $user_id, $course_id, $instance_id = null ) {
		if ( ! $user_id ) {
			return false;
		}

		if ( $instance_id ) {
			$enrollment = LP_Addon_CourseInstances_Database::get_user_instance_enrollment( $user_id, $instance_id );
			if ( $enrollment && $enrollment->status === 'enrolled' ) {
				return self::instance_has_started( $instance_id );
			}
		}

		$enrollments = LP_Addon_CourseInstances_Database::get_user_enrollments( $user_id, 'enrolled' );
		foreach ( $enrollments as $enrollment ) {
			if ( $enrollment->course_id == $course_id ) {
				return self::instance_has_started( $enrollment->instance_id );
			}
		}

		return false;
	}

	/**
	 * Check if course instance has started
	 */
	private static function instance_has_started( $instance_id ) {
		$instance = LP_Addon_CourseInstances_Database::get_instance( $instance_id );
		if ( $instance ) {
			$now = current_time( 'mysql' );
			return $now >= $instance->start_date;
		}
		return false;
	}

	/**
	 * Get all enrollments for a specific course instance
	 */
	public static function get_instance_enrollments( $instance_id ) {
		global $wpdb;

		$user_items_table    = $wpdb->prefix . 'learnpress_user_items';
		$user_itemmeta_table = $wpdb->prefix . 'learnpress_user_itemmeta';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ui.*, u.display_name, u.user_email
                FROM $user_items_table ui
                JOIN $user_itemmeta_table uim ON ui.user_item_id = uim.learnpress_user_item_id
                LEFT JOIN {$wpdb->users} u ON ui.user_id = u.ID
                WHERE uim.meta_key = '_nmto_instance_id'
                AND uim.meta_value = %s
                AND ui.item_type = 'lp_course'
                ORDER BY ui.start_time DESC",
				$instance_id
			)
		);
	}

	/**
	 * Get enrollment statistics
	 */
	public static function get_enrollment_stats() {
		$stats = array(
			'total_instances'    => 0,
			'total_enrollments'  => 0,
			'active_enrollments' => 0,
		);

		// Get total instances
		$instances                = LP_Addon_CourseInstances_Database::get_course_instances();
		$stats['total_instances'] = count( $instances );

		global $wpdb;
		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		$stats['total_enrollments'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $user_items_table WHERE item_type = 'lp_course_instance'"
		);

		$stats['active_enrollments'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $user_items_table WHERE item_type = 'lp_course_instance' AND status = 'enrolled'"
		);

		return $stats;
	}
}

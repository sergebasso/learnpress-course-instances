<?php
/**
 * Database operations for course scheduling
 */

class LearnPress_Course_Instances_Database {

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Course instances table
		$table_instances = $wpdb->prefix . 'nmto_learnpress_course_instances';
		$sql_instances   = "CREATE TABLE $table_instances (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            course_id bigint(20) NOT NULL,
            instance_name varchar(255) NOT NULL,
            description text,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            enrollment_start datetime NOT NULL,
            enrollment_end datetime NOT NULL,
            max_students int(11) DEFAULT 0,
            current_students int(11) DEFAULT 0,
            instructor_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY course_id (course_id),
            KEY instructor_id (instructor_id),
            KEY status (status)
        ) $charset_collate;";

		// Create course instances table only
		// We'll use LearnPress's existing user_items table for enrollments
		// and add our instance_id as metadata

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_instances );

		// Add version option
		add_option( 'nmto_learnpress_course_instances_db_version', NMTO_LEARNPRESS_COURSE_INSTANCES_VERSION );
	}

	public static function get_course_instances( $course_id = null, $status = 'active' ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'nmto_learnpress_course_instances';
		$where  = array( 'status = %s' );
		$values = array( $status );

		if ( $course_id ) {
			$where[]  = 'course_id = %d';
			$values[] = $course_id;
		}

		$sql = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_date ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	public static function create_course_instance( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'nmto_learnpress_course_instances';

		$result = $wpdb->insert(
			$table,
			array(
				'course_id'        => $data['course_id'],
				'instance_name'    => $data['instance_name'],
				'description'      => $data['description'],
				'start_date'       => $data['start_date'],
				'end_date'         => $data['end_date'],
				'enrollment_start' => $data['enrollment_start'],
				'enrollment_end'   => $data['enrollment_end'],
				'max_students'     => $data['max_students'],
				'instructor_id'    => $data['instructor_id'],
				'status'           => 'active',
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	public static function enroll_student( $instance_id, $user_id ) {
		global $wpdb;

		$instance = self::get_instance( $instance_id );
		if ( ! $instance ) {
			return false;
		}

		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		// Check if already enrolled in this specific instance
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $user_items_table WHERE user_id = %d AND item_id = %d AND item_type = 'lp_course_instance'",
				$user_id,
				$instance_id
			)
		);

		if ( $existing ) {
			return $existing->user_item_id; // Already enrolled
		}

		// Create new instance enrollment
		$result = $wpdb->insert(
			$user_items_table,
			array(
				'user_id'        => $user_id,
				'item_id'        => $instance_id,
				'item_type'      => 'lp_course_instance',
				'status'         => 'enrolled',
				'start_time'     => current_time( 'mysql' ),
				'start_time_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'parent_id'      => $instance->course_id, // Link to original course
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( $result ) {
			self::update_student_count( $instance_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	public static function link_enrollment_to_instance( $user_item_id, $instance_id ) {
		// Store the instance ID in LearnPress user item meta
		return learn_press_update_user_item_meta( $user_item_id, '_nmto_instance_id', $instance_id );
	}

	public static function get_user_course_enrollment( $user_id, $course_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'learnpress_user_items';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND item_id = %d AND item_type = 'lp_course'",
				$user_id,
				$course_id
			)
		);
	}

	public static function get_instance( $instance_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'nmto_learnpress_course_instances';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$instance_id
			)
		);
	}

	public static function get_instance_enrollments( $instance_id ) {
		global $wpdb;

		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ui.*, u.display_name, u.user_email
             FROM $user_items_table ui
             LEFT JOIN {$wpdb->users} u ON ui.user_id = u.ID
             WHERE ui.item_id = %d AND ui.item_type = 'lp_course_instance'
             ORDER BY ui.start_time DESC",
				$instance_id
			)
		);
	}

	public static function update_student_count( $instance_id ) {
		global $wpdb;

		$instances_table  = $wpdb->prefix . 'nmto_learnpress_course_instances';
		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		// Count enrollments for this instance
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $user_items_table
             WHERE item_id = %d AND item_type = 'lp_course_instance' AND status IN ('enrolled', 'finished')",
				$instance_id
			)
		);

		$wpdb->update(
			$instances_table,
			array( 'current_students' => $count ),
			array( 'id' => $instance_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	public static function get_user_enrollments( $user_id, $status = 'enrolled' ) {
		global $wpdb;

		$user_items_table = $wpdb->prefix . 'learnpress_user_items';
		$instances_table  = $wpdb->prefix . 'nmto_learnpress_course_instances';

		$sql = "SELECT ui.*, i.instance_name, i.course_id, i.start_date, i.end_date, i.id as instance_id
                FROM $user_items_table ui
                JOIN $instances_table i ON ui.item_id = i.id
                WHERE ui.user_id = %d
                AND ui.item_type = 'lp_course_instance'
                AND ui.status = %s
                ORDER BY i.start_date ASC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $user_id, $status ) );
	}

	public static function get_user_instance_enrollment( $user_id, $instance_id ) {
		global $wpdb;

		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $user_items_table WHERE user_id = %d AND item_id = %d AND item_type = 'lp_course_instance'",
				$user_id,
				$instance_id
			)
		);
	}

	/**
	 * Get courses that have direct enrollments not linked to instances
	 */
	public static function get_courses_with_unlinked_enrollments() {
		global $wpdb;

		$user_items_table    = $wpdb->prefix . 'learnpress_user_items';
		$user_itemmeta_table = $wpdb->prefix . 'learnpress_user_itemmeta';

		$sql = "SELECT DISTINCT ui.item_id as course_id, COUNT(ui.user_item_id) as unlinked_count
                FROM $user_items_table ui
                LEFT JOIN $user_itemmeta_table uim ON ui.user_item_id = uim.learnpress_user_item_id
                    AND uim.meta_key = '_nmto_instance_id'
                WHERE ui.item_type = 'lp_course'
                AND ui.status IN ('enrolled', 'finished')
                AND uim.meta_value IS NULL
                GROUP BY ui.item_id
                ORDER BY unlinked_count DESC";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get unlinked enrollments for a specific course
	 */
	public static function get_course_unlinked_enrollments( $course_id ) {
		global $wpdb;

		$user_items_table    = $wpdb->prefix . 'learnpress_user_items';
		$user_itemmeta_table = $wpdb->prefix . 'learnpress_user_itemmeta';

		$sql = "SELECT ui.*, u.display_name, u.user_email
                FROM $user_items_table ui
                LEFT JOIN $user_itemmeta_table uim ON ui.user_item_id = uim.learnpress_user_item_id
                    AND uim.meta_key = '_nmto_instance_id'
                LEFT JOIN {$wpdb->users} u ON ui.user_id = u.ID
                WHERE ui.item_type = 'lp_course'
                AND ui.item_id = %d
                AND ui.status IN ('enrolled', 'finished')
                AND uim.meta_value IS NULL
                ORDER BY ui.start_time DESC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $course_id ) );
	}

	/**
	 * Get total count of unlinked enrollments across all courses
	 */
	public static function get_total_unlinked_enrollments_count() {
		global $wpdb;

		$user_items_table    = $wpdb->prefix . 'learnpress_user_items';
		$user_itemmeta_table = $wpdb->prefix . 'learnpress_user_itemmeta';

		$sql = "SELECT COUNT(ui.user_item_id)
                FROM $user_items_table ui
                LEFT JOIN $user_itemmeta_table uim ON ui.user_item_id = uim.learnpress_user_item_id
                    AND uim.meta_key = '_nmto_instance_id'
                WHERE ui.item_type = 'lp_course'
                AND ui.status IN ('enrolled', 'finished')
                AND uim.meta_value IS NULL";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Delete a course instance and all related enrollments
	 */
	public static function delete_course_instance( $instance_id ) {
		global $wpdb;

		$instances_table  = $wpdb->prefix . 'nmto_learnpress_course_instances';
		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		// First, delete all enrollments for this instance
		$wpdb->delete(
			$user_items_table,
			array(
				'item_id'   => $instance_id,
				'item_type' => 'lp_course_instance',
			),
			array( '%d', '%s' )
		);

		// Then delete the instance itself
		$result = $wpdb->delete(
			$instances_table,
			array( 'id' => $instance_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete all instances for a course (used when force deleting a course)
	 */
	public static function delete_all_course_instances( $course_id ) {
		global $wpdb;

		$instances_table  = $wpdb->prefix . 'nmto_learnpress_course_instances';
		$user_items_table = $wpdb->prefix . 'learnpress_user_items';

		// Get all instances for this course
		$instances = self::get_course_instances( $course_id );

		if ( empty( $instances ) ) {
			return true;
		}

		// Delete all enrollments for these instances
		foreach ( $instances as $instance ) {
			$wpdb->delete(
				$user_items_table,
				array(
					'item_id'   => $instance->id,
					'item_type' => 'lp_course_instance',
				),
				array( '%d', '%s' )
			);
		}

		// Delete all instances for this course
		$result = $wpdb->delete(
			$instances_table,
			array( 'course_id' => $course_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Check if a course has any instances with enrollments
	 */
	public static function course_has_instances_with_enrollments( $course_id ) {
		$instances = self::get_course_instances( $course_id );

		foreach ( $instances as $instance ) {
			if ( $instance->current_students > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get detailed information about course instances for deletion warning
	 */
	public static function get_course_instances_summary( $course_id ) {
		$instances = self::get_course_instances( $course_id );
		$summary   = array(
			'total_instances'            => count( $instances ),
			'total_enrollments'          => 0,
			'active_instances'           => 0,
			'instances_with_enrollments' => 0,
		);

		foreach ( $instances as $instance ) {
			$summary['total_enrollments'] += $instance->current_students;

			if ( $instance->current_students > 0 ) {
				++$summary['instances_with_enrollments'];
			}

			$now = current_time( 'mysql' );
			if ( $now >= $instance->enrollment_start && $now <= $instance->enrollment_end ) {
				++$summary['active_instances'];
			}
		}

		return $summary;
	}
}

<div class="wrap">
	<h1><?php esc_html_e( 'Course Instances', 'learnpress-course-instances' ); ?></h1>

	<?php
	// Show success/error messages.
	// if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == '1' && isset( $_GET['message'] ) ) {
	// echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( urldecode( $_GET['message'] ) ) . '</p></div>';
	// }

	// Sanitize the tab value from $_GET with nonce verification.
	$active_tab = 'instances'; // Default tab.
	if ( isset( $_GET['tab'] ) &&
		( ! isset( $_GET['_wpnonce'] ) ||
			wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'learnpress_course_instances_tab' ) ) ) {
		$active_tab = sanitize_key( $_GET['tab'] );
	}
	?>

	<nav class="nav-tab-wrapper">
		<a href="?page=learnpress-course-instances&tab=instances" class="nav-tab <?php echo 'instances' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Course Instances', 'learnpress-course-instances' ); ?>
		</a>
		<a href="?page=learnpress-course-instances&tab=unlinked" class="nav-tab <?php echo 'unlinked' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Unlinked Enrollments', 'learnpress-course-instances' ); ?>
			<?php
			$unlinked_count = LP_Addon_CourseInstances_Database::get_total_unlinked_enrollments_count();
			if ( $unlinked_count > 0 ) {
				echo '<span class="awaiting-mod count-' . esc_html( $unlinked_count ) . '"><span class="pending-count">(' . esc_html( $unlinked_count ) . ')</span></span>';
			}
			?>
		</a>
	</nav>

	<div class="nmto-course-instances-admin">
		<?php if ( $active_tab === 'instances' ) : ?>
			<div class="nmto-create-instance">
				<h2><?php _e( 'Create New Course Instance', 'learnpress-course-instances' ); ?></h2>

			<form method="post" action="">
				<?php wp_nonce_field( 'create_course_instance', 'nmto_nonce' ); ?>
				<input type="hidden" name="action" value="create_instance">

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="course_id"><?php _e( 'Course', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<select name="course_id" id="course_id" required>
								<option value=""><?php _e( 'Select a course', 'learnpress-course-instances' ); ?></option>
								<?php foreach ( $courses as $course ) : ?>
									<option value="<?php echo $course->ID; ?>"><?php echo esc_html( $course->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="instance_name"><?php _e( 'Instance Name', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="text" name="instance_name" id="instance_name" class="regular-text" required
									placeholder="<?php _e( 'e.g., Spring 2025 Cohort', 'learnpress-course-instances' ); ?>">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="enrollment_start"><?php _e( 'Enrollment Start', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="date" name="enrollment_start" id="enrollment_start" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="enrollment_end"><?php _e( 'Enrollment End', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="date" name="enrollment_end" id="enrollment_end" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="start_date"><?php _e( 'Course Start Date', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="date" name="start_date" id="start_date" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="end_date"><?php _e( 'Course End Date', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="date" name="end_date" id="end_date" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="max_students"><?php _e( 'Maximum Students', 'learnpress-course-instances' ); ?></label>
						</th>
						<td>
							<input type="number" name="max_students" id="max_students" min="0" value="0">
							<p class="description"><?php _e( '0 for unlimited', 'learnpress-course-instances' ); ?></p>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Create Course Instance', 'learnpress-course-instances' ) ); ?>
			</form>
		</div>

		<div class="nmto-instances-list">
			<h2><?php _e( 'Existing Course Instances', 'learnpress-course-instances' ); ?></h2>

			<?php if ( empty( $instances ) ) : ?>
				<p><?php _e( 'No course instances found.', 'learnpress-course-instances' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php _e( 'Course', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Instance Name', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Enrollment Period', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Course Period', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Students', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Status', 'learnpress-course-instances' ); ?></th>
							<th><?php _e( 'Actions', 'learnpress-course-instances' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $instances as $instance ) : ?>
							<?php
							$course = get_post( $instance->course_id );
							$status = NMTO_LearnPress_Course_Instance::get_instance_status( $instance->id );
							?>
							<tr>
								<td><?php echo $course ? esc_html( $course->post_title ) : __( 'Unknown Course', 'learnpress-course-instances' ); ?></td>
								<td>
									<strong><?php echo esc_html( $instance->instance_name ); ?></strong>
									<?php if ( $instance->description ) : ?>
										<br><small><?php echo esc_html( $instance->description ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<?php echo date( 'M j, Y', strtotime( $instance->enrollment_start ) ); ?><br>
									<small>to <?php echo date( 'M j, Y', strtotime( $instance->enrollment_end ) ); ?></small>
								</td>
								<td>
									<?php echo date( 'M j, Y', strtotime( $instance->start_date ) ); ?><br>
									<small>to <?php echo date( 'M j, Y', strtotime( $instance->end_date ) ); ?></small>
								</td>
								<td>
									<?php echo $instance->current_students; ?>
									<?php if ( $instance->max_students > 0 ) : ?>
										/ <?php echo $instance->max_students; ?>
									<?php endif; ?>
								</td>
								<td>
									<span class="status-<?php echo $status; ?>">
										<?php echo NMTO_LearnPress_Course_Instance::format_status_label( $status ); ?>
									</span>
								</td>
								<td>
									<?php if ( $instance->current_students > 0 ) : ?>
										<span class="button disabled" title="<?php _e( 'Cannot delete instance with enrolled students', 'learnpress-course-instances' ); ?>">
											<?php _e( 'Delete', 'learnpress-course-instances' ); ?>
										</span>
										<br><small style="color: #d63638;"><?php _e( 'Remove students first', 'learnpress-course-instances' ); ?></small>
									<?php else : ?>
										<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=learnpress-course-instances&action=delete_instance&instance_id=' . $instance->id ), 'delete_instance_' . $instance->id ); ?>"
											class="button button-secondary nmto-delete-instance"
											onclick="return confirm('<?php _e( 'Are you sure you want to delete this course instance? This action cannot be undone.', 'learnpress-course-instances' ); ?>')">
											<?php _e( 'Delete', 'learnpress-course-instances' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php elseif ( $active_tab === 'unlinked' ) : ?>
			<div class="nmto-unlinked-enrollments">
				<h2><?php _e( 'Unlinked Enrollments', 'learnpress-course-instances' ); ?></h2>
				<p><?php _e( 'These students are enrolled in courses but not linked to specific course instances. This may limit their access to course content.', 'learnpress-course-instances' ); ?></p>

				<?php if ( empty( $unlinked_courses ) ) : ?>
					<div class="notice notice-success inline">
						<p><?php _e( 'Great! All course enrollments are properly linked to course instances.', 'learnpress-course-instances' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $unlinked_courses as $course_data ) : ?>
						<?php
						$course               = get_post( $course_data->course_id );
						$unlinked_enrollments = LP_Addon_CourseInstances_Database::get_course_unlinked_enrollments( $course_data->course_id );
						?>

						<div class="nmto-unlinked-course" style="border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; background: #fff;">
							<h3>
								<?php echo $course ? esc_html( $course->post_title ) : __( 'Unknown Course', 'learnpress-course-instances' ); ?>
								<span class="count">(<?php echo $course_data->unlinked_count; ?> unlinked)</span>
								<?php if ( $course ) : ?>
									<a href="<?php echo get_edit_post_link( $course->ID ); ?>" class="button button-small" style="margin-left: 10px;">
										<?php _e( 'Edit Course', 'learnpress-course-instances' ); ?>
									</a>
								<?php endif; ?>
							</h3>

							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php _e( 'Student', 'learnpress-course-instances' ); ?></th>
										<th><?php _e( 'Email', 'learnpress-course-instances' ); ?></th>
										<th><?php _e( 'Enrollment Date', 'learnpress-course-instances' ); ?></th>
										<th><?php _e( 'Status', 'learnpress-course-instances' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $unlinked_enrollments as $enrollment ) : ?>
										<tr>
											<td><?php echo esc_html( $enrollment->display_name ?: 'Unknown User' ); ?></td>
											<td><?php echo esc_html( $enrollment->user_email ?: '—' ); ?></td>
											<td><?php echo $enrollment->start_time ? date( 'M j, Y g:i a', strtotime( $enrollment->start_time ) ) : '—'; ?></td>
											<td>
												<span class="status-<?php echo esc_attr( $enrollment->status ); ?>">
													<?php echo esc_html( ucfirst( $enrollment->status ) ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<p style="margin-top: 15px;">
								<strong><?php _e( 'Recommendations:', 'learnpress-course-instances' ); ?></strong>
							</p>
							<ul style="margin-left: 20px;">
								<li><?php _e( 'Create course instances for this course if none exist', 'learnpress-course-instances' ); ?></li>
								<li><?php _e( 'Contact these students to enroll them in a specific course instance', 'learnpress-course-instances' ); ?></li>
								<li><?php _e( 'Consider if these were test enrollments that should be removed', 'learnpress-course-instances' ); ?></li>
							</ul>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		</div>
	</div>
</div>

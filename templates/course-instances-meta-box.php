<div class="nmto-course-instances-meta">
	<h4><?php _e( 'Course Instances', 'learnpress-course-instances' ); ?></h4>

	<?php
	// Show warning for unlinked enrollments
	if ( isset( $post ) && $post->ID ) {
		LearnPress_Course_Instances_Admin::getInstance()->show_course_unlinked_warning( $post->ID );
	}
	?>

	<?php if ( empty( $instances ) ) : ?>
		<p><?php _e( 'No course instances created yet.', 'learnpress-course-instances' ); ?></p>
		<p>
			<a href="<?php echo admin_url( 'admin.php?page=learnpress-course-instances' ); ?>" class="button">
				<?php _e( 'Create Course Instance', 'learnpress-course-instances' ); ?>
			</a>
		</p>
	<?php else : ?>
		<div class="notice notice-warning inline" style="margin: 10px 0;">
			<p><strong><?php _e( 'Deletion Protection Active', 'learnpress-course-instances' ); ?></strong></p>
			<p><?php _e( 'This course cannot be deleted while course instances exist. Remove all instances first if you need to delete this course.', 'learnpress-course-instances' ); ?></p>
		</div>

		<table class="nmto-instances-table">
			<thead>
				<tr>
					<th><?php _e( 'Instance Name', 'learnpress-course-instances' ); ?></th>
					<th><?php _e( 'Enrollment Period', 'learnpress-course-instances' ); ?></th>
					<th><?php _e( 'Course Period', 'learnpress-course-instances' ); ?></th>
					<th><?php _e( 'Students', 'learnpress-course-instances' ); ?></th>
					<th><?php _e( 'Status', 'learnpress-course-instances' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $instances as $instance ) : ?>
					<?php $status = NMTO_LearnPress_Course_Instance::get_instance_status( $instance->id ); ?>
					<tr>
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
							<span class="status <?php echo $status; ?>">
								<?php echo NMTO_LearnPress_Course_Instance::format_status_label( $status ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="nmto-add-instance-button">
			<a href="<?php echo admin_url( 'admin.php?page=learnpress-course-instances' ); ?>" class="button">
				<?php _e( 'Add New Instance', 'learnpress-course-instances' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>

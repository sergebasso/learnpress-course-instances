<div class="nmto-user-enrollments">
	<h3><?php _e( 'My Course Enrollments', 'learnpress-course-instances' ); ?></h3>

	<div class="enrollments-grid">
		<?php foreach ( $enrollments as $enrollment ) : ?>
			<?php
			$course       = get_post( $enrollment->course_id );
			$status_class = strtolower( $enrollment->status );
			?>
			<div class="enrollment-card status-<?php echo $status_class; ?>">
				<div class="enrollment-header">
					<h4 class="enrollment-course-title"><?php echo $course ? esc_html( $course->post_title ) : __( 'Unknown Course', 'learnpress-course-instances' ); ?></h4>
					<p class="enrollment-instance-name"><?php echo esc_html( $enrollment->instance_name ); ?></p>
				</div>

				<div class="enrollment-dates">
					<strong><?php _e( 'Course Dates:', 'learnpress-course-instances' ); ?></strong>
					<?php echo date( 'M j, Y', strtotime( $enrollment->start_date ) ); ?> -
					<?php echo date( 'M j, Y', strtotime( $enrollment->end_date ) ); ?>
				</div>

				<div class="enrollment-progress">
					<div class="enrollment-progress-bar" style="width: <?php echo $enrollment->progress; ?>%;"></div>
				</div>

				<div class="enrollment-status <?php echo $status_class; ?>">
					<?php echo ucfirst( $enrollment->status ); ?>
					<?php if ( $enrollment->progress > 0 ) : ?>
						(<?php echo $enrollment->progress; ?>% complete)
					<?php endif; ?>
				</div>

				<?php if ( $course ) : ?>
					<div class="enrollment-actions">
						<a href="<?php echo get_permalink( $course ); ?>" class="button">
							<?php _e( 'View Course', 'learnpress-course-instances' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

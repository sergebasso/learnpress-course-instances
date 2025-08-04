<?php foreach ( $instances as $instance ) : ?>
	<?php
	$status     = NMTO_LearnPress_Course_Instance::get_instance_status( $instance->id );
	$can_enroll = NMTO_LearnPress_Course_Instance::can_enroll( $instance->id );
	?>
	<div class="instance-card status-<?php echo $status; ?>">
		<div class="instance-header">
			<h5><?php echo esc_html( $instance->instance_name ); ?></h5>
			<span class="status-badge"><?php echo NMTO_LearnPress_Course_Instance::format_status_label( $status ); ?></span>
		</div>

		<?php if ( $instance->description ) : ?>
			<p class="instance-description"><?php echo esc_html( $instance->description ); ?></p>
		<?php endif; ?>

		<div class="instance-details">
			<div class="detail-item">
				<strong><?php _e( 'Course Dates:', 'learnpress-course-instances' ); ?></strong>
				<?php echo date( 'M j, Y', strtotime( $instance->start_date ) ); ?> -
				<?php echo date( 'M j, Y', strtotime( $instance->end_date ) ); ?>
			</div>

			<?php if ( $status === 'enrollment_open' ) : ?>
				<div class="detail-item">
					<strong><?php _e( 'Enrollment Closes:', 'learnpress-course-instances' ); ?></strong>
					<?php echo date( 'M j, Y g:i A', strtotime( $instance->enrollment_end ) ); ?>
				</div>
			<?php elseif ( $status === 'enrollment_not_started' ) : ?>
				<div class="detail-item">
					<strong><?php _e( 'Enrollment Opens:', 'learnpress-course-instances' ); ?></strong>
					<?php echo date( 'M j, Y g:i A', strtotime( $instance->enrollment_start ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $instance->max_students > 0 ) : ?>
				<div class="detail-item">
					<strong><?php _e( 'Availability:', 'learnpress-course-instances' ); ?></strong>
					<?php echo ( $instance->max_students - $instance->current_students ); ?>
					<?php _e( 'spots remaining', 'learnpress-course-instances' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $status === 'enrollment_open' ) : ?>
			<div class="instance-actions">
				<?php if ( $can_enroll ) : ?>
					<button class="btn-enroll" data-instance-id="<?php echo $instance->id; ?>">
						<?php _e( 'Enroll Now', 'learnpress-course-instances' ); ?>
					</button>
				<?php elseif ( ! is_user_logged_in() ) : ?>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn-login">
						<?php _e( 'Login to Enroll', 'learnpress-course-instances' ); ?>
					</a>
				<?php else : ?>
					<button class="btn-disabled" disabled>
						<?php _e( 'Already Enrolled', 'learnpress-course-instances' ); ?>
					</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
<?php endforeach; ?>

<div class="nmto-course-instances">
	<h3><?php _e( 'Available Course Sessions', 'learnpress-course-instances' ); ?></h3>

	<?php if ( ! empty( $instances ) ) : ?>
		<div class="nmto-available-instances">
			<h4><?php _e( 'Enroll Now', 'learnpress-course-instances' ); ?></h4>
			<div class="instances-grid">
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

							<div class="detail-item">
								<strong><?php _e( 'Enrollment Closes:', 'learnpress-course-instances' ); ?></strong>
								<?php echo date( 'M j, Y g:i A', strtotime( $instance->enrollment_end ) ); ?>
							</div>

							<?php if ( $instance->max_students > 0 ) : ?>
								<div class="detail-item">
									<strong><?php _e( 'Availability:', 'learnpress-course-instances' ); ?></strong>
									<?php echo ( $instance->max_students - $instance->current_students ); ?>
									<?php _e( 'spots remaining', 'learnpress-course-instances' ); ?>
								</div>
							<?php endif; ?>
						</div>

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
									<?php _e( 'Cannot Enroll', 'learnpress-course-instances' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $upcoming ) ) : ?>
		<div class="nmto-upcoming-instances">
			<h4><?php _e( 'Upcoming Sessions', 'learnpress-course-instances' ); ?></h4>
			<div class="instances-grid">
				<?php foreach ( $upcoming as $instance ) : ?>
					<div class="instance-card upcoming">
						<div class="instance-header">
							<h5><?php echo esc_html( $instance->instance_name ); ?></h5>
							<span class="status-badge upcoming"><?php _e( 'Coming Soon', 'learnpress-course-instances' ); ?></span>
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

							<div class="detail-item">
								<strong><?php _e( 'Enrollment Opens:', 'learnpress-course-instances' ); ?></strong>
								<?php echo date( 'M j, Y g:i A', strtotime( $instance->enrollment_start ) ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
	$('.btn-enroll').on('click', function() {
		var button = $(this);
		var instanceId = button.data('instance-id');

		button.prop('disabled', true).text('<?php _e( 'Enrolling...', 'learnpress-course-instances' ); ?>');

		$.ajax({
			url: nmto_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'enroll_course_instance',
				instance_id: instanceId,
				nonce: nmto_ajax.nonce
			},
			success: function(response) {
				if (response.success) {
					button.removeClass('btn-enroll').addClass('btn-success')
							.text('<?php _e( 'Enrolled!', 'learnpress-course-instances' ); ?>');
					alert(response.data.message);
					location.reload();
				} else {
					button.prop('disabled', false).text('<?php _e( 'Enroll Now', 'learnpress-course-instances' ); ?>');
					alert(response.data.message);
				}
			},
			error: function() {
				button.prop('disabled', false).text('<?php _e( 'Enroll Now', 'learnpress-course-instances' ); ?>');
				alert('<?php _e( 'An error occurred. Please try again.', 'learnpress-course-instances' ); ?>');
			}
		});
	});
});
</script>

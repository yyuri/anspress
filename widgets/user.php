<?php
class AP_User_Widget extends WP_Widget {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		parent::__construct(
			'ap_user_widget',
			__( '(AnsPress) User menu and profile', 'anspress-question-answer' ),
			array( 'description' => __( 'Display current logged in users detail and menu.', 'anspress-question-answer' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		global $ap_user_query;

		echo '<div class="ap-widget-inner">';

		if ( is_user_logged_in() ) {
	        $ap_user_query = ap_has_users( array( 'ID' => ap_get_displayed_user_id() ) );

	        if ( $ap_user_query->has_users() ) {
	        	while ( ap_users() ) : ap_the_user();
					ap_get_template_part( 'widgets/user' );
				endwhile;
			}
		} else {
?>
    <div class="ap-please-login">
        <?php printf(__('Please %s or %s.', 'anspress-question-answer'), '<a data-action="ap_modal" data-toggle="#ap-login-modal" href="'.wp_login_url(get_permalink()).'">'.__('Login', 'anspress-question-answer').'</a>', '<a href="'.wp_registration_url().'">'.__('Sign up', 'anspress-question-answer').'</a>') ?>
        <?php do_action( 'wordpress_social_login' ); ?>
    </div>
    <div id="ap-login-modal" class="ap-modal">
        <div class="ap-modal-backdrop"></div>
        <div class="ap-modal-inner">
            <div class="ap-modal-header">
                <i class="ap-modal-close" data-action="ap_modal_close">×</i>
                <h3 class="ap-modal-title"><?php _e('Login', 'anspress-question-answer'); ?></h3>
            </div>
            <div class="ap-modal-body">
                <?php wp_login_form(); ?>
            </div>
        </div>
    </div><?php		}

		echo '</div>';

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'My profile', 'anspress-question-answer' );
		}
		?>
        <p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'anspress-question-answer' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

function ap_user_register_widgets() {
	register_widget( 'AP_User_Widget' );
}

add_action( 'widgets_init', 'ap_user_register_widgets' );

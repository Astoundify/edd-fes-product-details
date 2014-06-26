<?php
/**
 * Custom Widget for product details.
 *
 * @link http://codex.wordpress.org/Widgets_API#Developing_Widgets
 *
 * @package EDD_FPD
 * @since EDD FPD 1.0
 */

class EDD_FPD_Widget extends WP_Widget {

	var $multi_sep;

	/**
	 * Constructor.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @return Twenty_Fourteen_Ephemera_Widget
	 */
	public function __construct() {
		parent::__construct( 'widget_edd_fpd', __( 'Easy Digital Downloads - Frontend Product Details', 'edd-fpd' ), array(
			'classname'   => 'widget_edd_fpd',
			'description' => __( 'Use this widget to display specified product details.', 'edd-fpd' ),
		) );

		$this->multi_sep = apply_filters( 'edd_fpd_multi_sep', ', ' );

		add_action( 'save_post',    array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	/**
	 * Output the HTML for this widget.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param array $args An array of standard parameters for widgets in this theme.
	 * @param array $instance An array of settings for this widget instance.
	 * @return void Echoes its output.
	 */
	public function widget( $args, $instance ) {
		global $post;

		if ( ! class_exists( 'EDD_Front_End_Submissions' ) ) {
			return;
		}

		// If called directly, assign an unique index for caching.
		if ( -1 == $this->number ) {
			static $num = -1;
			$this->_set( --$num );
		}

		$content = get_transient( $this->id );
		$meta    = $this->get_product_details();

		if ( empty( $meta ) )
			return;

		ob_start();
		extract( $args, EXTR_SKIP );

		$title  = apply_filters( 'widget_title', empty( $instance[ 'title' ] ) ? '' : $instance[ 'title' ] );
		echo $before_widget;

		if ( '' != $title )
			echo $before_title . $title . $after_title;
		?>

		<table class="edd-fpd">
			<?php foreach ( $meta as $label => $value ) : if ( '' == $value ) continue; ?>
			<tr>
				<th><?php echo $label; ?></th>
				<td><?php echo $value; ?></td>
			</tr>
			<?php endforeach; ?>
		</table>

		<?php

		echo $after_widget;

		set_transient( $this->id, ob_get_flush() );
	}

	public function get_product_details() {
		global $post;

		$form_id = EDD_FES()->helper->get_option( 'fes-submission-form' );

		if ( ! $form_id ) {
			return;
		}

		$fields  = get_post_meta( $form_id, 'fes-form', true );
		$meta    = array();

		if ( ! $fields ) {
			return;
		}

		foreach ( $fields as $field ) {
			if ( ! isset( $field[ 'product_detail' ] ) )
				continue;

			$value = get_post_meta( $post->ID, $field[ 'name' ], true );

			switch ( $field[ 'input_type' ] ) {
				case 'image_upload' :
					if ( 'featured_image' == $field[ 'template' ] ) {
						$value = get_the_post_thumbnail( $post->ID, 'thumbnail' );
					}
				break;

				case 'file_upload' :
					$uploads = array();

					foreach ( $value as $attachment_id ) {
						$uploads[] = wp_get_attachment_link( $attachment_id, 'thumbnail', false, true );
					}

					$value = implode( '<br />', $uploads );
				break;

				case 'checkbox' :
				case 'multiselect' :
					if ( ! is_array( $value ) ) {
						$value = explode( '|', $value );
					} else {
						$value = array_map( 'trim', $value );
					}

					$value = implode( $this->multi_sep, $value );
				break;

				case 'taxonomy' :
					$terms = wp_get_post_terms( $post->ID, $field[ 'name' ] );

					if ( ! is_wp_error( $terms ) ) {
						switch ( $field[ 'type' ] ) {

							case 'checkbox' :
							case 'multiselect' :
							case 'text' :
								$_terms = array();

								foreach ( $terms as $term ) {
									$_terms[] = sprintf( '<a href="%s">%s</a>', get_term_link( $term, $field[ 'name' ] ), $term->name );
								}

								$value = implode( $this->multi_sep, $_terms );
							break;

							case 'select' :
								$value = sprintf( '<a href="%s">%s</a>', get_term_link( current( $terms ), $field[ 'name' ] ), current( $terms )->name );
							break;

						}
					}
				break;

				default :
					if ( 'no' != $field[ 'is_meta' ] ) {
						$value = get_post_meta( $post->ID, $field[ 'name' ], true );
					} else {
						$value = get_post_field( $field[ 'name' ], $post->ID );
					}
				break;
			}

			$label = apply_filters( 'edd_fpd_label', $field[ 'label' ], $field );
			$value = apply_filters( 'edd_fpd_value', $value, $field );

			if ( empty( $value ) )
				continue;

			$meta[ $label ] = $value;

		}

		return $meta;
	}

	/**
	 * Deal with the settings when they are saved by the admin. Here is where
	 * any validation should happen.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param array $new_instance
	 * @param array $instance
	 * @return array
	 */
	function update( $new_instance, $instance ) {
		$instance['title']  = strip_tags( $new_instance['title'] );

		$this->flush_widget_cache();

		return $instance;
	}

	/**
	 * Delete the transient.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @return void
	 */
	function flush_widget_cache() {
		delete_transient( $this->id );
	}

	/**
	 * Display the form for this widget on the Widgets page of the Admin area.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		$title  = empty( $instance['title'] ) ? '' : esc_attr( $instance['title'] );
		?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'edd-fpd' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<?php
	}
}
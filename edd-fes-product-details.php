<?php
/**
 * Plugin Name: Easy Digital Downloads - Frontend Submissions Product Details
 * Plugin URI:  https://github.com/astoundify/edd-fes-product-details
 * Description: Mark information collected during frontend submissions as "product details" to be displayed elsewhere.
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.0.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Astoundify_EDD_FPD {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Make sure only one instance is only running.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param void
	 * @return object $instance The one true class instance.
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start things up.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param void
	 * @return void
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set some smart defaults to class variables.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param void
	 * @return void
	 */
	private function setup_globals() {
		$this->file         = __FILE__;

		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );
	}

	/**
	 * Hooks and stuff.
	 *
	 * @since EDD FPD 1.0
	 *
	 * @param void
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'fes_add_field_to_common_form_element', array( $this, 'product_details' ), 100, 4 );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}

	public function widgets_init() {
		require $this->plugin_dir . 'widget.php';

		register_widget( 'EDD_FPD_Widget' );
	}

	public function product_details( $tpl, $input_name, $id, $values ){
		$field_name  = sprintf( $tpl, $input_name, $id, 'product_detail' );
		$field_value = $values && isset( $values[ 'product_detail' ]) ? esc_attr( $values[ 'product_detail' ] ) : '';
		?>
			<div class="fes-form-rows">
				<label><?php _e( 'Product Detail', 'edd-fpd' ); ?></label>

				<div class="fes-form-sub-fields">
					<label for="<?php esc_attr_e( $field_name ); ?>">
						<input type="checkbox" data-type="label" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="1" class="smallipopInput" <?php checked( $field_value, 1 ); ?>>
						<?php _e( 'Show this data separately on the product listing page.', 'edd-fpd' ); ?>
					</label>
				</div>
			</div><!-- .fes-form-rows -->
		<?php
	}

}
add_action( 'plugins_loaded', array( 'Astoundify_EDD_FPD', 'instance' ) );
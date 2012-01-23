<?php
/*
Plugin Name: JigoShop — Green-tax
Plugin URI: http://jigoshop.com
Description: Extends JigoShop providing a greentax option under product price management displayed in the product details.
Version: 1.0
Author: Benjamin Grelié
Author URI: http://www.gymnokidi.com
*/


/**
 * Check if Jigoshop is active
 **/
if ( in_array( 'jigoshop/jigoshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function jigoshop_green_tax() {
		global $post;

		$data = (array) maybe_unserialize( get_post_meta($post->ID, 'product_data', true) );
		$field = array( 'id' => 'greentax_status', 'label' => __('Greentaxable?', 'jigoshop') );
		$greentax_price = array( 'id' => 'greentax_price', 'label' => __('Greentax Price', 'jigoshop') . ' ('.get_jigoshop_currency_symbol().'):' );
		$option = '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" class="checkbox" name="'.$field['id'].'" id="'.$field['id'].'"';
		if (isset($data[$field['id']]) && $data[$field['id']]=='yes') $option .= 'checked="checked" ';
		$option .= ' /><input type="text" class="short" style="margin-left:5px" name="'.$greentax_price['id'].'" id="'.$greentax_price['id'].'" value="'.$data[$greentax_price['id']].'" placeholder="0.00" />';
		$option .= '</p>';
		?><script type="text/javascript">jQuery('div#pricing_product_data').append(<?php echo "'".$option."'"; ?>);</script>
		<?php
	}

	function process_greentax($post_id, $post) {
		$newdata = new jigoshop_sanitize( $_POST );
		$product_type = sanitize_title( $newdata->__get( 'product-type' ));

		$greentax_price = $newdata->__get( 'greentax_price' );
		$savedata['greentax_status'] = ($newdata->__get( 'greentax_status') ? 'yes' : 'no');
		$savedata['greentax_price'] = $greentax_price;
		$savedata = apply_filters( 'process_product_meta', $savedata, $post_id );
		$savedata = apply_filters( 'filter_product_meta_' . $product_type, $savedata, $post_id );
		
		
		if ( function_exists( 'process_product_meta_' . $product_type )) {
			$meta_errors = call_user_func( 'process_product_meta_' . $product_type, $savedata, $post_id );
			if ( is_array( $meta_errors )) {
				$jigoshop_errors = array_merge( $jigoshop_errors, $meta_errors );
			}
		}
		update_post_meta( $post_id, 'product_data', $savedata );
		update_option( 'jigoshop_errors', $jigoshop_errors );
	}

	function get_greentax_price()
	{
		global $post;

		$data = (array) maybe_unserialize( get_post_meta($post->ID, 'product_data', true) );
		$greentax_html = '';
		if ($data['greentax_status'] == 'yes' && $data['greentax_price'] > 0)
			$greentax_html = '<small style="font-size: 0.6em; display: block">'. __('Incl. ', 'jigoshop') . jigoshop_price($data['greentax_price']) . __(" of eco-participation", 'jigoshop') .'</small>';
		?><script type="text/javascript">jQuery('p.price').append(<?php echo "'".$greentax_html."'"; ?>)</script><?php
	}

	add_action('product_write_panels', 'jigoshop_green_tax');
	add_action('jigoshop_process_product_meta', 'process_greentax', 1, 2 );
	add_action('jigoshop_after_single_product_summary', 'get_greentax_price');
}
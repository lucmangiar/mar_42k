<?php
if ( !class_exists('Sigma_Products') ) :
/**
 * Sigma Products
 *
 * After registration and before payment is done,
 * the products related to an event is displayed.
 * Adds sigma products using a WordPress custom post type
 * and link them in the sigma checkout.
 *
 * @package     SigmaEvents
 * @subpackage  Core
 */
class Sigma_Products{
	/**
	 * Sigma Products Constructor
	 */
	function __construct(){
		// Register post type.
		add_action('init', array($this, 'register_sigma_products_post_type'));

		// Add price meta box, etc.
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// Save meta data.
		add_action('save_post', array($this, 'save_product_boxes'));

		// A redirect to serve product post types.
		add_action('template_redirect', array($this, 'redirect_products_template'));

		// On payment page show an excerpt of 100 words.
		add_filter( 'excerpt_length', array($this, 'product_excerpt_length'), 999 );
	}

	/**
	 * Register Sigma Products
	 *
	 * Add a custom post type 'products' to manage
	 * event related products.
	 */
	function register_sigma_products_post_type(){
		$labels = array(
			'name' => __('Products', 'se'),
			'singular_name' => __('Product', 'se'),
			'add_new' => __('Add Product', 'se'),
			'add_new_item' => __('Add New Product', 'se'),
			'edit_item' => __('Edit Product', 'se')
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array('title', 'editor', 'thumbnail'),
			'menu_icon' => SIGMA_URL . 'assets/sigma.png'
		);
		register_post_type( 'products', $args );
	}

	/**
	 * Add Products Meta Boxes
	 *
	 * Add a meta box to manage product price.
	 */
	function add_meta_boxes(){
		add_meta_box(
			'price_box',
			__('Product Price', 'se'),
			array($this, 'price_box_cb'),
			'products',
			'side',
			'default');

		add_meta_box(
			'email_template_box',
			__('Email Template', 'se'),
			array($this, 'email_template_box_cb'),
			'products',
			'normal',
			'default');
	}

	/**
	 * Price Box Callback
	 *
	 * Renders price meta box for sigma products.
	 */
	function price_box_cb($post){
		wp_nonce_field('sigma_product_meta_save', 'sigma_product_meta_data');

		$price = get_post_meta($post->ID, 'sigma_product_price', true);
			if($price == ''){
			$price['local'] = '';
			$price['rate'] = '5.2';
            $price['foreign'] = '';
        }

		$output = '<table><tr><td><label>' . __('Local (ARS)', 'se') . '</label></td>';
		$output .= "<td><input type='text' class='newtag' name='localp' value='" . $price['local'] . "' ></td></tr>";

		$output .= '<tr><td><label>' . __('Foreign (USD)', 'se') . '</label></td>';
		$output .= "<td><input type='text' class='newtag' name='foreignp' value='" . $price['foreign'] . "' ></td></tr></table>";

		echo $output;
	}

    /**
     * Email Template Meta Box for Products.
     *
     * Send an email to the user for each additional product purchased.
     */
	function email_template_box_cb($post){
		$email_template = get_post_meta($post->ID, 'sigma_email_template', true);
        $sigma_logo = '<img src="' . SIGMA_URL . 'assets/sigma-logo.png" alt="sigma-logo" >';
        if($email_template == ''){
        $email_template['subject'] = 'Product: {{pname}} | Event: {{ename}}';
        $email_template['attachment'] = '';
        $email_template['message_approved'] = '<h2>Hi, {{Fname}} </h2>
        <span style="color: #000080;">Additional Sigma Products:</span>.<br />
        <h1>{{pname}}</b></h1>
        <h3>Your payment has been approved</h3>
				<h4>Your Registration ID: <b>{{token}}</b>.</h4>
        <i>Sigma Ticketing - Sigma Secure Pay<i><br />
        <i><a href="http://sigmasecurepay.info" >
        Visit Sigma Secure Pay Website</a><i><br /><br />' . $sigma_logo;
			}

		// Email Subject.
		$output = '<table style="width: 100%; max-width: 100%;" ><tr><td><label>' . __('Email Subject', 'se') . '</label></td>';
		$output .= "<td><input type='text' class='regular-text' name='esubject' value='" . $email_template['subject'] . "' ></td></tr>";

        // Email Attachment Upload Button
        $output .= '<tr><td><label>' . __('Email Attachment', 'se') . '</label></td>';
        $output .= '<td><input id="eattachment" type="text" class="regular-text" name="eattachment"
            value="' .  esc_attr( $email_template['attachment'] ) . '" />';
        $editor_id = 'eattachment';
        $post = get_post();
        wp_enqueue_media( array('post' => $post) );
        $img = '<span class="wp-media-buttons-icon"></span> ';
        $output .= ' <a href="#" class="button insert-media add_media" data-editor="' . esc_attr( $editor_id ) .
            '" id="eattachment_uploader" title="' . esc_attr__( 'Upload Product Email Attachment' ) . '"> ' .
            $img . __( 'Upload Attachment' ) . '</a>';
        $output .= '</td></tr>';

        // Email Body Title.
        $output .= '<tr><td colspan="2" ><label><br />'
            . __('Product Email Body. See help for available email template tags.', 'se') . '</label></td></tr>';

        // Email Body Editor.
        $output .= "<tr><td colspan='2' ><div class='se-email-body'>";
        $msg = apply_filters('the_content', $email_template['message_approved']);
        ob_start();
            wp_editor($msg, 'emessageapproved', array('media_buttons' => false));
        $output .= ob_get_clean();
        $output .= "</div></td></tr>";

        $output .= "</table>";
        echo $output;
	}

	/**
	 * Save Product Boxes
	 *
	 * Saves product related meta data with the product.
	 */
	function save_product_boxes($post_id){
		// Return on autosave.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Nonce check.
		if(!isset($_POST['sigma_product_meta_data']) ||
			!wp_verify_nonce($_POST['sigma_product_meta_data'], 'sigma_product_meta_save'))
			return;

		// Permission check.
		if('products' != $_POST['post_type'] ||
			!current_user_can('edit_post', $post_id ))
			return;

		$price['local'] = (float) sanitize_text_field($_POST['localp']);
		$price['foreign'] = (float) sanitize_text_field($_POST['foreignp']);

		// Collect email template formatting information.
		$email_template['subject'] = sanitize_text_field($_POST['esubject']);
		$email_template['attachment'] = sanitize_text_field($_POST['eattachment']);
		$email_template['message_approved'] = wp_kses($_POST['emessageapproved'], wp_kses_allowed_html( 'post' ));;
		$email_template['message_not_approved'] = wp_kses($_POST['emessagenapproved'], wp_kses_allowed_html( 'post' ));;

		// Update product meta data.
		update_post_meta($post_id, 'sigma_product_price', $price);
		update_post_meta($post_id, 'sigma_email_template', $email_template);
	}

	/*
	 * Redirect Product Template
	 *
	 * Return to checkout link or button needs to be added to the
	 * products template. Without a redirection theme files needs to
	 * be altered. Better to redirect product post types here and
	 * serve Sigma Events specific templates.
	 */
	function redirect_products_template(){
		if('products' == get_post_type()) :
			add_action('wp_enqueue_scripts', array($this, 'enqueue'));
			include SIGMA_PATH . 'templates/sigma-products-template.php';
			die();
		endif;
	}

	/**
	 * Enqueue Styles and Scripts
	 *
	 * No need to check either for front end or for products post page,
	 * This calls after checking for products
	 * at template redirect
	 */
	function enqueue(){
        global $post_type;
        if('products' == $post_type && !is_admin()){
            // Products page stylesheet.
            wp_register_style('sigma-events-style', SIGMA_URL . 'css/sigma-events.css');
            wp_enqueue_style('sigma-events-style');

            // Products page javascripts.
            wp_register_script('sigma-events-script',
            SIGMA_URL . 'js/sigma-events.js', array('jquery'), '1.0', true);
            wp_enqueue_script('sigma-events-script');
        }
	}

	/*
	 * Product Excerpt Length.
	 *
	 * WordPress default is 55 words. We need 100.
	 * Hook to the filter and return 100 words.
	 */
	function product_excerpt_length($l3ngth){
        // Only for product post types.
		if('products' == get_post_type())
			return 10;

        return $l3ngth;
	}
}
endif;
?>

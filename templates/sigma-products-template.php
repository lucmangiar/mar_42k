<?php
if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}

$token = '';
// Can GET the token?
if(isset($_GET['sigma_token']) && $_GET['sigma_token'] != ''):
	$token = sanitize_text_field($_GET['sigma_token']);
endif;

/**
 * Sigma Products Template
 *
 * After redirected by custom rewrite rules specific to
 * Sigma Products, this template is used to serve the custom
 * Sigma Product Information in the front end.
 *
 * This template implements a button which allows the visitor
 * to BACK to the checkout page.
 */
// Get the site header.
get_header();
echo '<div class="se-wrapper" >';
if(have_posts()):
	while(have_posts()):
		the_post();
		$product_id = get_the_ID();

		// Product Title
		echo "<div class='se-product-header' >";
		the_title();
		echo "</div>";

		// Product Picture
		echo "<div class='se-product-thumbnail' >";
		the_post_thumbnail('full');
		echo "</div>";

		// Product Description
		echo "<div class='se-product-content' >";
		the_content();
		echo "</div>";

		// Show a BACK button, if a token is presented.
		if($token != ''):
		echo "<div class='se-submit'  >";
		echo '<a href="'. get_home_url() . '/sigma-events/payment/' .
			'?sigma_token=' . $token . '#se-order"
			id="se-product-button" >' .
			__("Back to checkout", 'se') . "</a>";
		echo "</div>";
		endif;

	endwhile;
endif;
echo '</div>';
// Get the site footer.
get_footer();
?>

<?php
/**
 * AnsPress category sortcode
 *
 * @package   AnsPress
 * @subpackage Categories for anspress
 * @author    Rahul Aryan <rah12@live.com>
 * @license   GPL-2.0+
 * @link      http://wp3.in
 * @copyright 2014 Rahul Aryan
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class AnsPress_Categories_Shortcode {

	/**
	 * Output for anspress_categories shortcode
	 * @param  $atts
	 * @param  string $content
	 */
	public static function anspress_categories($atts, $content = ''){
		global $question_categories;
		
		$paged = get_query_var('paged') ? get_query_var('paged') : 1;
		$per_page    	= ap_opt('categories_per_page');
		$total_terms 	= wp_count_terms('question_category'); 	
		$offset      	= $per_page * ( $paged - 1) ;

		$cat_args = array(
			'parent' 		=> 0,
			'number'		=> $per_page,
			'offset'       	=> $offset,
			'hide_empty'    => false,
			'orderby'       => 'count',
			'order'         => 'DESC',
		);

		/**
		 * FILTER: ap_categories_shortcode_args
		 * Filter applied before getting categories.
		 * @var array
		 * @since 1.0
		 */
		$cat_args = apply_filters('ap_categories_shortcode_args', $cat_args );

		$question_categories = get_terms( 'question_category' , $cat_args); 
		echo '<div class="anspress-container">';
			/**
			 * ACTION: ap_before
			 * Action is fired before loading AnsPress body.
			 */
			do_action('ap_before');
			
			// include theme file
			include ap_get_theme_location('categories.php', CATEGORIES_FOR_ANSPRESS_DIR);
		echo '</div>';

	}

	
}

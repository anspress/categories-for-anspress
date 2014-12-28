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

class AnsPress_Category_Shortcode {

	/**
	 * Output for anspress_category shortcode
	 * @param  $atts
	 * @param  string $content
	 */
	public static function anspress_category($atts, $content = ''){
		$category_id = get_query_var( 'question_category');
		
		if(empty( $category_id )){
			echo '<div class="anspress-container">';
				/**
				 * ACTION: ap_before
				 * Action is fired before loading AnsPress body.
				 */
				do_action('ap_before');
				
				// include theme file
				include ap_get_theme_location('no-category-found.php', CATEGORIES_FOR_ANSPRESS_DIR);
			echo '</div>';
			return;
		}

		global $question_category, $ap_max_num_pages, $ap_per_page, $questions;

		$question_args['tax_query'] = array(		
			array(
				'taxonomy' => 'question_category',
				'field' => 'id',
				'terms' => array( get_query_var( 'question_category') )
			)
		);

		/**
		 * FILTER: ap_category_shortcode_args
		 * Filter applied before getting question of current category.
		 * @var array
		 * @since 1.0
		 */
		$question_args = apply_filters('ap_category_shortcode_args', $question_args );

		$questions = new Question_Query( $question_args );
		$question_category = $questions->get_queried_object();
		echo '<div class="anspress-container">';
			/**
			 * ACTION: ap_before
			 * Action is fired before loading AnsPress body.
			 */
			do_action('ap_before');
			
			// include theme file
			include ap_get_theme_location('category.php', CATEGORIES_FOR_ANSPRESS_DIR);
		echo '</div>';
		wp_reset_postdata();
	}

	
}

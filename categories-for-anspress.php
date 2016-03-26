<?php
/**
 * Categories extension for AnsPress
 *
 * AnsPress - Question and answer plugin for WordPress
 *
 * @package   AnsPress/Categories_for_AnsPress
 * @author    Rahul Aryan <support@anspress.io>
 * @license   GPL-2.0+
 * @link      http://anspress.io
 * @copyright 2014 AnsPress & Rahul Aryan
 *
 * @wordpress-plugin
 * Plugin Name:       Categories for AnsPress
 * Plugin URI:        http://anspress.io/downloads/categories-for-anspress
 * Description:       Extension for AnsPress. Add categories in AnsPress.
 * Donate link: https://www.paypal.com/cgi-bin/webscr?business=rah12@live.com&cmd=_xclick&item_name=Donation%20to%20AnsPress%20development
 * Version:           2.0.0
 * Author:            Rahul Aryan
 * Author URI:        http://anspress.io
 * Text Domain:       categories-for-anspress
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Category extension for AnsPress
 */
class Categories_For_AnsPress
{

	/**
	 * Class instance
	 * @var object
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Get active object instance
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new Categories_For_AnsPress();
		}

		return self::$instance;
	}
	/**
	 * Initialize the class
	 * @since 2.0
	 */
	public function __construct() {

		if ( ! class_exists( 'AnsPress' ) ) {
			return; // AnsPress not installed.
		}
		if ( ! defined( 'CATEGORIES_FOR_ANSPRESS_DIR' ) ) {
			define( 'CATEGORIES_FOR_ANSPRESS_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'CATEGORIES_FOR_ANSPRESS_URL' ) ) {
			define( 'CATEGORIES_FOR_ANSPRESS_URL', plugin_dir_url( __FILE__ ) );
		}

		$this->includes();

		ap_register_page( ap_get_category_slug(), __( 'Category', 'categories-for-anspress' ), array( $this, 'category_page' ), false );

		ap_register_page( ap_get_categories_slug(), __( 'Categories', 'categories-for-anspress' ), array( $this, 'categories_page' ) );

		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'init', array( $this, 'register_question_categories' ), 1 );
		add_action( 'admin_init', array( $this, 'load_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'ap_admin_menu', array( $this, 'admin_category_menu' ) );
		add_action( 'ap_display_question_metas', array( $this, 'ap_display_question_metas' ), 10, 2 );
		add_action( 'ap_enqueue', array( $this, 'ap_enqueue' ) );
		add_filter( 'term_link', array( $this, 'term_link_filter' ), 10, 3 );
		add_action( 'ap_ask_form_fields', array( $this, 'ask_from_category_field' ), 10, 2 );
		add_action( 'ap_ask_fields_validation', array( $this, 'ap_ask_fields_validation' ) );
		add_action( 'ap_processed_new_question', array( $this, 'after_new_question' ), 0, 2 );
		add_action( 'ap_processed_update_question', array( $this, 'after_new_question' ), 0, 2 );
		add_filter( 'ap_page_title', array( $this, 'page_title' ) );
		add_filter( 'ap_breadcrumbs', array( $this, 'ap_breadcrumbs' ) );
		add_action( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_action( 'ap_list_head', array( $this, 'ap_list_head' ) );
		add_action( 'question_category_add_form_fields', array( $this, 'image_field_new' ) );
		add_action( 'question_category_edit_form_fields', array( $this, 'image_field_edit' ) );
		add_action( 'create_question_category', array( $this, 'save_image_field' ) );
		add_action( 'edited_question_category', array( $this, 'save_image_field' ) );
		add_action( 'ap_rewrite_rules', array( $this, 'rewrite_rules' ), 10, 3 );
		add_filter( 'ap_default_pages', array( $this, 'category_default_page' ) );
		add_filter( 'ap_default_page_slugs', array( $this, 'default_page_slugs' ) );
		add_filter( 'ap_subscribe_btn_type', array( $this, 'subscribe_type' ) );
		add_filter( 'ap_subscribe_btn_action_type', array( $this, 'subscribe_btn_action_type' ) );
	}

	/**
	 * Include required files
	 */
	public function includes() {
		require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'functions.php' );
		require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'categories-widget.php' );
	}

	/**
	 * Category page layout
	 */
	public function category_page() {

		global $questions, $question_category, $wp;

		$category_id = sanitize_title( get_query_var( 'q_cat' ) );

		$question_args = array(
			'tax_query' => array(
				array(
					'taxonomy' => 'question_category',
					'field' => is_numeric( $category_id ) ? 'id' : 'slug',
					'terms' => array( $category_id ),
				),
			),
		);

		$question_category = get_term_by( 'slug', $category_id, 'question_category' );

		if ( $question_category ) {
			$questions = ap_get_questions( $question_args );

			/**
			 * This action can be used to show custom message before category page.
			 * @param object $question_category Current question category.
			 * @since 1.4.2
			 */
			do_action( 'ap_before_category_page', $question_category );
			
			include( ap_get_theme_location( 'category.php', CATEGORIES_FOR_ANSPRESS_DIR ) );
		} else {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			include ap_get_theme_location( 'not-found.php' );
		}
	}

	/**
	 * Categories page layout
	 */
	public function categories_page() {
		global $question_categories, $ap_max_num_pages, $ap_per_page;

		$paged 				= max( 1, get_query_var( 'paged' ) );
		$per_page           = ap_opt( 'categories_per_page' );
		$total_terms        = wp_count_terms( 'question_category' );
		$offset             = $per_page * ( $paged - 1) ;
		$ap_max_num_pages   = $total_terms / $per_page ;

		$order = ap_opt( 'categories_page_order' ) == 'ASC' ? 'ASC' : 'DESC';

		$cat_args = array(
			'parent'        => 0,
			'number'        => $per_page,
			'offset'        => $offset,
			'hide_empty'    => false,
			'orderby'       => ap_opt( 'categories_page_orderby' ),
			'order'         => $order,
		);

		/**
		 * FILTER: ap_categories_shortcode_args
		 * Filter applied before getting categories.
		 * @var array
		 * @since 1.0
		 */
		$cat_args = apply_filters( 'ap_categories_shortcode_args', $cat_args );

		$question_categories = get_terms( 'question_category' , $cat_args );

		include ap_get_theme_location( 'categories.php', CATEGORIES_FOR_ANSPRESS_DIR );
	}

	/**
	 * Load plugin text domain
	 * @since 1.0
	 * @access public
	 */
	public static function textdomain() {

		// Set filter for plugin's languages directory.
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Load the translations.
		load_plugin_textdomain( 'categories-for-anspress', false, $lang_dir );
	}

	/**
	 * Register category taxonomy for question cpt
	 * @return void
	 * @since 2.0
	 */
	public function register_question_categories() {
		ap_register_menu( 'ANSPRESS_CATEGORIES_PAGE_URL', __( 'Categories', 'categories-for-anspress' ), ap_get_link_to( 'categories' ) );

		/**
		 * Labesl for category taxonomy
		 * @var array
		 */
		$categories_labels = array(
			'name' 				=> __( 'Question Categories', 'categories-for-anspress' ),
			'singular_name' 	=> _x( 'Category', 'categories-for-anspress' ),
			'all_items' 		=> __( 'All Categories', 'categories-for-anspress' ),
			'add_new_item' 		=> _x( 'Add New Category', 'categories-for-anspress' ),
			'edit_item' 		=> __( 'Edit Category', 'categories-for-anspress' ),
			'new_item' 			=> __( 'New Category', 'categories-for-anspress' ),
			'view_item' 		=> __( 'View Category', 'categories-for-anspress' ),
			'search_items' 		=> __( 'Search Category', 'categories-for-anspress' ),
			'not_found' 		=> __( 'Nothing Found', 'categories-for-anspress' ),
			'not_found_in_trash' => __( 'Nothing found in Trash', 'categories-for-anspress' ),
			'parent_item_colon' => '',
		);

		/**
		 * FILTER: ap_question_category_labels
		 * Filter ic called before registering question_category taxonomy
		 */
		$categories_labels = apply_filters( 'ap_question_category_labels',  $categories_labels );

		/**
		 * Arguments for category taxonomy
		 * @var array
		 * @since 2.0
		 */
		$category_args = array(
			'hierarchical' => true,
			'labels' => $categories_labels,
			'rewrite' => true,
		);

		/**
		 * FILTER: ap_question_category_args
		 * Filter ic called before registering question_category taxonomy
		 */
		$category_args = apply_filters( 'ap_question_category_args',  $category_args );

		/**
		 * Now let WordPress know about our taxonomy
		 */
		register_taxonomy( 'question_category', array( 'question' ), $category_args );

	}

	/**
	 * Register Categories options
	 */
	public function load_options() {
		$settings = ap_opt();
		ap_register_option_group( 'categories', __( 'Categories', 'categories-for-anspress' ), array(
			array(
				'name'              => 'anspress_opt[form_category_orderby]',
				'label'             => __( 'Ask form category order', 'categories-for-anspress' ),
				'description'       => __( 'Set how you want to order categories in form.', 'categories-for-anspress' ),
				'type'              => 'select',
				'options'			=> array(
					'ID' 			=> __( 'ID', 'categories-for-anspress' ),
					'name' 			=> __( 'Name', 'categories-for-anspress' ),
					'slug' 			=> __( 'Slug', 'categories-for-anspress' ),
					'count' 		=> __( 'Count', 'categories-for-anspress' ),
					'term_group' 	=> __( 'Group', 'categories-for-anspress' ),
					),
				'value'             => @$settings['form_category_orderby'],
			),

			array(
				'name'              => 'anspress_opt[categories_page_orderby]',
				'label'             => __( 'Categries page order by', 'categories-for-anspress' ),
				'description'       => __( 'Set how you want to order categories in categories page.', 'categories-for-anspress' ),
				'type'              => 'select',
				'options'			=> array(
					'ID' 			=> __( 'ID', 'categories-for-anspress' ),
					'name' 			=> __( 'Name', 'categories-for-anspress' ),
					'slug' 			=> __( 'Slug', 'categories-for-anspress' ),
					'count' 		=> __( 'Count', 'categories-for-anspress' ),
					'term_group' 	=> __( 'Group', 'categories-for-anspress' ),
					),
				'value'             => @$settings['categories_page_orderby'],
			),

			array(
				'name'              => 'anspress_opt[categories_page_order]',
				'label'             => __( 'Categries page order', 'categories-for-anspress' ),
				'description'       => __( 'Set how you want to order categories in categories page.', 'categories-for-anspress' ),
				'type'              => 'select',
				'options'			=> array(
					'ASC' 			=> __( 'Ascending', 'categories-for-anspress' ),
					'DESC' 			=> __( 'Descending', 'categories-for-anspress' ),
				),
				'value'             => @$settings['categories_page_order'],
			),

			array(
				'name' 		=> 'anspress_opt[categories_page_slug]',
				'label' 	=> __( 'Categories page slug', 'categories-for-anspress' ),
				'desc' 		=> __( 'Slug categories page', 'categories-for-anspress' ),
				'type' 		=> 'text',
				'value' 	=> @$settings['categories_page_slug'],
				'show_desc_tip' => false,
			),

			array(
				'name' 		=> 'anspress_opt[category_page_slug]',
				'label' 	=> __( 'Category page slug', 'categories-for-anspress' ),
				'desc' 		=> __( 'Slug for category page', 'categories-for-anspress' ),
				'type' 		=> 'text',
				'value' 	=> @$settings['category_page_slug'],
				'show_desc_tip' => false,
			),

			array(
				'name' 		=> 'anspress_opt[categories_page_title]',
				'label' 	=> __( 'Categories title', 'categories-for-anspress' ),
				'desc' 		=> __( 'Title of the categories page', 'categories-for-anspress' ),
				'type' 		=> 'text',
				'value' 	=> @$settings['categories_page_title'],
				'show_desc_tip' => false,
			),
			array(
				'name' 		=> 'anspress_opt[categories_per_page]',
				'label' 	=> __( 'Category per page', 'categories-for-anspress' ),
				'desc' 		=> __( 'Category to show per page', 'categories-for-anspress' ),
				'type' 		=> 'number',
				'value' 	=> @$settings['categories_per_page'],
				'show_desc_tip' => false,
			),
		));
	}

	/**
	 * Enqueue required script
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Append default options
	 * @param   array $defaults Default AnsPress option.
	 * @return  array
	 * @since   1.0
	 */
	public static function ap_default_options($defaults) {
		$defaults['form_category_orderby']  	= 'count';
		$defaults['categories_page_order']  	= 'DESC';
		$defaults['categories_page_orderby']  	= 'count';
		$defaults['categories_page_slug']  		= 'categories';
		$defaults['category_page_slug']  		= 'category';
		$defaults['categories_per_page']  		= 20;

		return $defaults;
	}

	/**
	 * Add category menu in wp-admin
	 * @return void
	 * @since 2.0
	 */
	public function admin_category_menu() {
		add_submenu_page( 'anspress', 'Questions Category', 'Category', 'manage_options', 'edit-tags.php?taxonomy=question_category' );
	}

	/**
	 * Append meta display
	 * @param  	array   $metas Display meta items.
	 * @param 	integer $question_id  Question id.
	 * @return 	array
	 * @since 	1.0
	 */
	public function ap_display_question_metas($metas, $question_id) {
		if ( ap_question_have_category( $question_id ) && ! is_singular( 'question' ) ) {
			$metas['categories'] = ap_question_categories_html( array( 'label' => ap_icon( 'category', true ) ) ); }

		return $metas;
	}

	/**
	 * Enqueue scripts
	 * @since 1.0
	 */
	public function ap_enqueue() {
		wp_enqueue_style( 'categories_for_anspress_css', ap_get_theme_url( 'css/categories.css', CATEGORIES_FOR_ANSPRESS_URL ) );

	}

	/**
	 * Filter category permalink.
	 * @param  string $url      Default taxonomy url.
	 * @param  object $term     WordPress term object.
	 * @param  string $taxonomy Current taxonomy slug.
	 * @return string
	 */
	public function term_link_filter( $url, $term, $taxonomy ) {
		if ( 'question_category' == $taxonomy ) {
			if ( get_option( 'permalink_structure' ) != '' ) {
				 return ap_get_link_to( array( 'ap_page' => ap_get_category_slug(), 'q_cat' => $term->slug ) );
			} else {
				return add_query_arg( array( 'ap_page' => ap_get_category_slug(), 'q_cat' => $term->term_id ), ap_base_page_link() );
			}
		}
		return $url;
	}

	/**
	 * Add category field in ask form
	 * @param  	array 	$args 		Ask form arguments.
	 * @param  	boolean $editing 	true if is edit form.
	 * @return 	array
	 * @since 	2.0
	 */
	public function ask_from_category_field($args, $editing) {
		if ( wp_count_terms( 'question_category' ) == 0 ) {
			return $args; }

		global $editing_post;

		if ( $editing ) {
			$category = get_the_terms( $editing_post->ID, 'question_category' );
			$catgeory = $category[0]->term_id;
		}

		$args['fields'][] = array(
			'name' 		=> 'category',
			'label' 	=> __( 'Category', 'categories-for-anspress' ),
			'type'  	=> 'taxonomy_select',
			'value' 	=> ( $editing ? $catgeory :  sanitize_text_field( wp_unslash( @$_POST['category'] ) ) ),
			'taxonomy' 	=> 'question_category',
			'orderby' 	=> ap_opt( 'form_category_orderby' ),
			'desc' 		=> __( 'Select a topic that best fits your question', 'categories-for-anspress' ),
			'order' 	=> 6,
		);

		return $args;
	}

	/**
	 * Add category in validation field
	 * @param  array $args validation args.
	 * @return array
	 * @since  1.0
	 */
	public function ap_ask_fields_validation($args) {

		if ( wp_count_terms( 'question_category' ) == 0 ) {
			return $args; }

		$args['category'] = array(
			'sanitize' => array( 'only_int' ),
			'validate' => array( 'required' ),
		);

		return $args;
	}

	/**
	 * Things to do after creating a question
	 * @param  	integer $post_id    Questions ID.
	 * @param  	object  $post       Question post object.
	 * @return 	void
	 * @since 	1.0
	 */
	public function after_new_question($post_id, $post) {
		global $validate;

		if ( empty( $validate ) ) {
			return; }

		$fields = $validate->get_sanitized_fields();

		if ( isset( $fields['category'] ) ) {
			$category = wp_set_post_terms( $post_id, $fields['category'], 'question_category' );
		}

	}

	/**
	 * Add category page title
	 * @param  string $title AnsPress page title.
	 * @return string
	 */
	public function page_title($title) {
		if ( is_question_categories() ) {
			$title = ap_opt( 'categories_page_title' );
		} elseif ( is_question_category() ) {
			$category_id = sanitize_title( get_query_var( 'q_cat' ) );
			$category = get_term_by( is_numeric( $category_id ) ? 'id' : 'slug', $category_id, 'question_category' );

			if ( $category ) {
				$title = $category->name;
			} else {
				$title = __( 'No matching category found', 'categories-for-anspress' );
			}
		}

		return $title;
	}

	/**
	 * Add category nav in AnsPress breadcrumbs.
	 * @param  array $navs Breadcrumbs nav array.
	 * @return array
	 */
	public function ap_breadcrumbs($navs) {
		if ( is_question_category() ) {
			$category_id = sanitize_text_field( get_query_var( 'q_cat' ) );
			$category = get_term_by( is_numeric( $category_id ) ? 'id' : 'slug', $category_id, 'question_category' );
			$navs['page'] = array( 'title' => __( 'Categories', 'categories-for-anspress' ), 'link' => ap_get_link_to( 'categories' ), 'order' => 8 );
			$navs['category'] = array( 'title' => $category->name, 'link' => get_term_link( $category, 'question_category' ), 'order' => 8 );
		} elseif ( is_question_categories() ) {
			$navs['page'] = array( 'title' => __( 'Categories', 'categories-for-anspress' ), 'link' => ap_get_link_to( 'categories' ), 'order' => 8 );

		}

		return $navs;
	}


	public function terms_clauses($pieces, $taxonomies, $args) {

		if ( ! in_array( 'question_category', $taxonomies ) || ! isset( $args['ap_query'] ) || $args['ap_query'] != 'subscription' ) {
			return $pieces; }

		global $wpdb;

		$pieces['join']     = $pieces['join'].' INNER JOIN '.$wpdb->prefix.'ap_meta apmeta ON t.term_id = apmeta.apmeta_actionid';
		$pieces['where']    = $pieces['where']." AND apmeta.apmeta_type='subscriber' AND apmeta.apmeta_param='category' AND apmeta.apmeta_userid='".$args['user_id']."'";

		return $pieces;
	}

	/**
	 * Add category sorting in list head
	 * @return void
	 */
	public function ap_list_head() {

		global $wp;

		if ( ! isset( $wp->query_vars['ap_sc_atts_categories'] ) ) {
			ap_category_sorting();
		}
	}

	/**
	 * Custom question category fields
	 * @param  array $term
	 * @return void
	 */
	public function image_field_new( $term ) {
		echo "<div class='form-field term-image-wrap'>";
		echo "<label for='ap_image'>".__( 'Image', 'categories-for-anspress' ).'</label>';
		echo '<a href="#" id="ap-category-upload" data-action="ap_media_uplaod" data-title="'.__( 'Upload image', 'categories-for-anspress' ).'" data-urlc="#ap_category_media_url" data-idc="#ap_category_media_id">'.__( 'Upload image', 'categories-for-anspress' ).'</a>';

		echo '<input id="ap_category_media_url" type="hidden" name="ap_category_image_url" value="">';
		echo '<input id="ap_category_media_id" type="hidden" name="ap_category_image_id" value="">';
		echo '<p class="description">'.__( 'Category image', 'categories-for-anspress' ).'</p>';
		echo '<div>';
		echo "<div class='form-field term-image-wrap'>";
		echo "<label for='ap_icon'>".__( 'Category icon class', 'categories-for-anspress' ).'</label>';
		echo '<input id="ap_icon" type="text" name="ap_icon" value="">';
		echo '<p class="description">'.__( 'Font icon class, if image not set', 'categories-for-anspress' ).'</p>';
		echo '<div>';
		echo "<div class='form-field term-image-wrap'>";
		echo "<label for='ap-category-color'>".__( 'Category icon color', 'categories-for-anspress' ).'</label>';
		echo '<input id="ap-category-color" type="text" name="ap_color" value="">';
		echo '<p class="description">'.__( 'Icon color', 'categories-for-anspress' ).'</p>';
		echo '<div>';
	}

	public function image_field_edit( $term ) {
		$termID         = $term->term_id;
		$option_name    = 'ap_cat_'.$term->term_id;
		$termMeta       = get_option( $option_name );
		$ap_image       = $termMeta['ap_image'];
		$ap_icon        = $termMeta['ap_icon'];
		$ap_color        = $termMeta['ap_color'];

		echo "<tr class='form-field form-required term-name-wrap'>";
		echo "<th scope='row'><label for='custom-field'>".__( 'Image', 'categories-for-anspress' ).'</label></th>';
		echo '<td>';
		echo '<a href="#" id="ap-category-upload" data-action="ap_media_uplaod" data-title="'.__( 'Upload image', 'categories-for-anspress' ).'" data-idc="#ap_category_media_id" data-urlc="#ap_category_media_url">'.__( 'Upload image', 'categories-for-anspress' ).'</a>';

		if ( isset( $ap_image['url'] ) && $ap_image['url'] != '' ) {
			echo '<img id="ap_category_media_preview" data-action="ap_media_value" src="'.$ap_image['url'].'" />'; }

		echo '<input id="ap_category_media_url" type="hidden" data-action="ap_media_value" name="ap_category_image_url" value="'.$ap_image['url'].'">';

		echo '<input id="ap_category_media_id" type="hidden" data-action="ap_media_value" name="ap_category_image_id" value="'.$ap_image['id'].'">';

		echo "<p class='description'>".__( 'Featured image for category', 'categories-for-anspress' ).'</p>';

		echo '<a href="#" id="ap-category-upload-remove" data-action="ap_media_remove">'.__( 'Remove image', 'categories-for-anspress' ).'</a>';
		echo '</td></tr>';

		echo "<tr class='form-field form-required term-name-wrap'>";
		echo "<th scope='row'><label for='custom-field'>".__( 'Category icon class', 'categories-for-anspress' ).'</label></th>';
		echo '<td>';
		echo '<input id="ap_icon" type="text" name="ap_icon" value="'.$ap_icon.'">';
		echo '<p class="description">'.__( 'Font icon class, if image not set', 'categories-for-anspress' ).'</p>';
		echo '</td></tr>';

		echo "<tr class='form-field form-required term-name-wrap'>";
		echo "<th scope='row'><label for='ap-category-color'>".__( 'Category icon color', 'categories-for-anspress' ).'</label></th>';
		echo '<td>';
		echo '<input id="ap-category-color" type="text" name="ap_color" value="'.$ap_color.'">';
		echo '<p class="description">'.__( 'Font icon class, if image not set', 'categories-for-anspress' ).'</p>';
		echo '</td></tr>';
	}

	public function save_image_field($termID) {

		if ( (isset( $_POST['ap_category_image_url'] ) && isset( $_POST['ap_category_image_id'] )) || isset( $_POST['ap_icon'] ) ) {

			// get options from database - if not a array create a new one
			$termMeta = get_option( 'ap_cat_'.$termID );

			if ( ! is_array( $termMeta ) ) {
				$termMeta = array();
			}

			if ( isset( $_POST['ap_category_image_url'] ) && isset( $_POST['ap_category_image_id'] ) ) {

				if ( ! is_array( $termMeta['ap_image'] ) ) {
					$termMeta['ap_image'] = array(); }

				// get value and save it into the database
				$termMeta['ap_image']['url'] = isset( $_POST['ap_category_image_url'] ) ? sanitize_text_field( $_POST['ap_category_image_url'] ) : '';

				$termMeta['ap_image']['id'] = isset( $_POST['ap_category_image_id'] ) ? (int) $_POST['ap_category_image_id'] : '';
			}

			if ( isset( $_POST['ap_icon'] ) ) {
				$termMeta['ap_icon'] = sanitize_text_field( $_POST['ap_icon'] ); }

			if ( isset( $_POST['ap_color'] ) ) {
				$termMeta['ap_color'] = sanitize_text_field( $_POST['ap_color'] ); }

			update_option( 'ap_cat_'.$termID, $termMeta );
		}
	}

	/**
	 * Add category pages rewrite rule
	 * @param  array $rules AnsPress rules.
	 * @return array
	 */
	public function rewrite_rules($rules, $slug, $base_page_id) {
		global $wp_rewrite;

		$cat_rules = array();

		$cat_rules[$slug. ap_get_categories_slug() . '/page/?([0-9]{1,})/?$'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_categories_slug() .'&paged='.$wp_rewrite->preg_index( 1 );

		$cat_rules[$slug. ap_get_category_slug() . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_category_slug() .'&q_cat='.$wp_rewrite->preg_index( 1 ).'&paged='.$wp_rewrite->preg_index( 2 );

		$cat_rules[$slug. ap_get_category_slug() .'/([^/]+)/?'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_category_slug() .'&q_cat='.$wp_rewrite->preg_index( 1 );

		$cat_rules[$slug. ap_get_categories_slug(). '/?'] = 'index.php?page_id='.$base_page_id.'&ap_page='.ap_get_categories_slug();

		return $cat_rules + $rules;
	}

	/**
	 * Add default categories page, so that categories page should work properly after
	 * Changing categories page slug.
	 * @param  array $default_pages AnsPress default pages.
	 * @return array
	 */
	public function category_default_page($default_pages) {
		$default_pages['categories'] = array();
		$default_pages['category'] = array();

		return $default_pages;
	}

	/**
	 * Add default page slug
	 * @param  array $default_slugs AnsPress pages slug.
	 * @return array
	 */
	public function default_page_slugs($default_slugs) {
		$default_slugs['categories'] 	= ap_get_categories_slug();
		$default_slugs['category'] 		= ap_get_category_slug();
		return $default_slugs;
	}

	public function subscribe_type($type) {
		if ( is_question_category() ) {
			$subscribe_type = 'category';
		} else { 			return $type; }
	}

	public function subscribe_btn_action_type($args) {

		if ( is_question_category() ) {
			global $question_category;

			$args['action_id'] 	= $question_category->term_id;
			$args['type'] 		= 'category';
		}

		return $args;
	}
}

/**
 * Get everything running
 * @since 1.0
 * @access private
 * @return void
 */
function categories_for_anspress() {
	if ( ! defined( 'AP_VERSION' ) || ! version_compare( AP_VERSION, '2.3', '>' ) ) {
		function ap_category_admin_error_notice() {
		    echo '<div class="update-nag error"> <p>'.sprintf( __( 'Category extension require AnsPress 2.4-RC or above. Download from Github %shttp://github.com/anspress/anspress%s', 'tags-for-anspress', 'categories-for-anspress' ), '<a target="_blank" href="http://github.com/anspress/anspress">', '</a>' ).'</p></div>';
		}
		add_action( 'admin_notices', 'ap_category_admin_error_notice' );
		return;
	}

	if( apply_filters( 'anspress_load_ext', true, 'categories-for-anspress' ) ){
		$categories = new Categories_For_AnsPress();
	}
}
add_action( 'plugins_loaded', 'categories_for_anspress' );

/**
 * Load extensions files before loading AnsPress
 * @return void
 * @since  1.0
 */
function anspress_loaded_categories_for_anspress() {	
	add_filter( 'ap_default_options', array( 'Categories_For_AnsPress', 'ap_default_options' ) );
}
add_action( 'before_loading_anspress', 'anspress_loaded_categories_for_anspress' );



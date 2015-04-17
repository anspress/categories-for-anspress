<?php
/**
 * Categories extension for AnsPress
 *
 * AnsPress - Question and answer plugin for WordPress
 *
 * @package   Categories for AnsPress
 * @author    Rahul Aryan <wp3@rahularyan.com>
 * @license   GPL-2.0+
 * @link      http://wp3.in/categories-for-anspress
 * @copyright 2014 WP3.in & Rahul Aryan
 *
 * @wordpress-plugin
 * Plugin Name:       Categories for AnsPress
 * Plugin URI:        http://wp3.in/categories-for-anspress
 * Description:       Extension for AnsPress. Add categories in AnsPress.
 * Donate link: https://www.paypal.com/cgi-bin/webscr?business=rah12@live.com&cmd=_xclick&item_name=Donation%20to%20AnsPress%20development
 * Version:           1.3.2
 * Author:            Rahul Aryan
 * Author URI:        http://wp3.in
 * Text Domain:       categories_for_anspress
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}


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

        if ( ! self::$instance )
            self::$instance = new Categories_For_AnsPress();

        return self::$instance;
    }
    /**
     * Initialize the class
     * @since 2.0
     */
    public function __construct()
    {
        if( ! class_exists( 'AnsPress' ) )
            return; // AnsPress not installed

        if (!defined('CATEGORIES_FOR_ANSPRESS_DIR'))    
            define('CATEGORIES_FOR_ANSPRESS_DIR', plugin_dir_path( __FILE__ ));

        if (!defined('CATEGORIES_FOR_ANSPRESS_URL'))   
                define('CATEGORIES_FOR_ANSPRESS_URL', plugin_dir_url( __FILE__ ));

        //$this->includes();

        ap_register_page('category', __('Category', 'categories_for_anspress'), array($this, 'category_page'), false);
        ap_register_page('categories', __('Categories', 'categories_for_anspress'), array($this, 'categories_page'));
        
        // internationalization
        add_action( 'init', array( $this, 'textdomain' ) );


        //Register question categories
        add_action('init', array($this, 'register_question_categories'), 1);
        add_action('ap_admin_menu', array($this, 'admin_category_menu'));
        add_filter('ap_default_options', array($this, 'ap_default_options') );
        add_action('ap_display_question_metas', array($this, 'ap_display_question_metas' ), 10, 2);
        //add_action('ap_before_question_title', array($this, 'ap_before_question_title' ));
        add_action('ap_enqueue', array( $this, 'ap_enqueue' ) );
        add_filter('term_link', array($this, 'term_link_filter'), 10, 3);
        add_action('ap_ask_form_fields', array($this, 'ask_from_category_field'), 10, 2);
        add_action('ap_ask_fields_validation', array($this, 'ap_ask_fields_validation'));
        add_action('ap_after_new_question', array($this, 'after_new_question'), 10, 2 );
        add_action('ap_after_update_question', array($this, 'after_new_question'), 10, 2 );
        add_filter('ap_page_title', array($this, 'page_title'));        
        add_filter('ap_option_group_layout', array($this, 'option'));        
    }

    public function includes(){
        require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'shortcode-categories.php' );
        require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'shortcode-category.php' );
    }

    public function category_page()
    {
        global $questions, $question_category;

        $category_id = sanitize_text_field( get_query_var( 'q_cat'));

        $question_args= array('tax_query' => array(        
            array(
                'taxonomy' => 'question_category',
                'field' => is_numeric($category_id) ? 'id' : 'slug',
                'terms' => array( $category_id )
            )
        ));
        
        $question_category = get_term_by( is_numeric($category_id) ? 'id' : 'slug', $category_id, 'question_category');
        $questions = ap_get_questions($question_args);

        if(ap_have_questions())
            include(ap_get_theme_location('category.php', CATEGORIES_FOR_ANSPRESS_DIR));
        else
            include(ap_get_theme_location('not-found.php'));
    }

    public function categories_page()
    {
        global $question_categories, $ap_max_num_pages, $ap_per_page;

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $per_page           = ap_opt('categories_per_page');
        $total_terms        = wp_count_terms('question_category');  
        $offset             = $per_page * ( $paged - 1) ;
        $ap_max_num_pages   = $total_terms / $per_page ;

        $cat_args = array(
            'parent'        => 0,
            'number'        => $per_page,
            'offset'        => $offset,
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
        
        include ap_get_theme_location('categories.php', CATEGORIES_FOR_ANSPRESS_DIR);
    }

    /**
     * Load plugin text domain
     *
     * @since 1.0
     *
     * @access public
     * @return void
     */
    public static function textdomain() {

        // Set filter for plugin's languages directory
        $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

        // Load the translations
        load_plugin_textdomain( 'categories_for_anspress', false, $lang_dir );
    }
    
    /**
     * Register category taxonomy for question cpt
     * @return void
     * @since 2.0
     */
    public function register_question_categories(){
        ap_register_menu('ANSPRESS_CATEGORIES_PAGE_URL', __('Categories', 'categories_for_anspress'), ap_get_link_to('categories'));

        /**
         * Labesl for category taxonomy
         * @var array
         */
        $categories_labels = array(
            'name' => __('Question Categories', 'categories_for_anspress'),
            'singular_name' => _x('Category', 'categories_for_anspress'),
            'all_items' => __('All Categories', 'categories_for_anspress'),
            'add_new_item' => _x('Add New Category', 'categories_for_anspress'),
            'edit_item' => __('Edit Category', 'categories_for_anspress'),
            'new_item' => __('New Category', 'categories_for_anspress'),
            'view_item' => __('View Category', 'categories_for_anspress'),
            'search_items' => __('Search Category', 'categories_for_anspress'),
            'not_found' => __('Nothing Found', 'categories_for_anspress'),
            'not_found_in_trash' => __('Nothing found in Trash', 'categories_for_anspress'),
            'parent_item_colon' => ''
        );

        /**
         * FILTER: ap_question_category_labels
         * Filter ic called before registering question_category taxonomy
         */
       $categories_labels = apply_filters( 'ap_question_category_labels',  $categories_labels);

        /**
         * Arguments for category taxonomy
         * @var array
         * @since 2.0
         */
        $category_args = array(
            'hierarchical' => true,
            'labels' => $categories_labels,
            'rewrite' => true
        );

        /**
         * FILTER: ap_question_category_args
         * Filter ic called before registering question_category taxonomy
         */
        $category_args = apply_filters( 'ap_question_category_args',  $category_args);

        /**
         * Now let WordPress know about our taxonomy
         */
        register_taxonomy('question_category', array('question'), $category_args);

    }

    /**
     * Apppend default options
     * @param   array $defaults
     * @return  array           
     * @since   1.0
     */             
    public function ap_default_options($defaults)
    {
        $defaults['categories_page_title']  = __('Question categories', 'categories_for_anspress');

        return $defaults;
    }

    /**
     * Add category menu in wp-admin
     * @return void
     * @since 2.0
     */
    public function admin_category_menu(){
            add_submenu_page('anspress', 'Questions Category', 'Category', 'manage_options', 'edit-tags.php?taxonomy=question_category');
    }

    /**
     * Append meta display
     * @param  array $metas
     * @param array $question_id        
     * @return array
     * @since 1.0
     */
    public function ap_display_question_metas($metas, $question_id)
    {   
        if(ap_question_have_category($question_id) && !is_singular('question'))
            $metas['categories'] = ap_question_categories_html(array('label' => __('Posted in ', 'categories_for_anspress')));

        return $metas;
    }

    /**
     * Append question category after question title
     * @param  object $post
     * @return string
     * @since 1.0
     */
    public function ap_before_question_title($post)
    {
        if(ap_question_have_category())
            echo '<div class="ap-posted-in">' . ap_question_categories_html(array('label' => __('Posted in ', 'categories_for_anspress'))) .'</div>';
    }
    /**
     * Enqueue scripts
     * @since 1.0
     */
    public function ap_enqueue()
    {
        wp_enqueue_style( 'categories_for_anspress_css', ap_get_theme_url('css/categories.css', CATEGORIES_FOR_ANSPRESS_URL));
        
    }

    public function term_link_filter( $url, $term, $taxonomy ) {
        if($taxonomy == 'question_category'){           
            if(get_option('permalink_structure') != '')
                 return ap_get_link_to(array('ap_page' => 'category', 'q_cat' => $term->slug));               
            else
                return ap_get_link_to(array('ap_page' => 'category', 'q_cat' => $term->term_id));
        }
        return $url;
       
    }

    /**
     * add category field in ask form
     * @param  array $validate
     * @return void
     * @since 2.0
     */
    public function ask_from_category_field($args, $editing){
        if(wp_count_terms('question_category') == 0)
            return $args;

        global $editing_post;

        if($editing){
            $category = get_the_terms( $editing_post->ID, 'question_category' );
            $catgeory = $category[0]->term_id;
        }

        $args['fields'][] = array(
            'name' => 'category',
            'label' => __('Category', 'categories_for_anspress'),
            'type'  => 'taxonomy_select',
            'value' => ( $editing ? $catgeory :  sanitize_text_field(@$_POST['category'] ))  ,
            'taxonomy' => 'question_category',
            'desc' => __('Select a topic that best fits your question', 'categories_for_anspress'),
            'order' => 6
        );

        return $args;
    }

    /**
     * add category in validation field
     * @param  array $fields
     * @return array
     * @since  1.0
     */
    public function ap_ask_fields_validation($args){

        if(wp_count_terms('question_category') == 0)
            return $args;

        $args['category'] = array(
            'sanitize' => array('only_int'),
            'validate' => array('required'),
        );

        return $args;
    }
    
    /**
     * Things to do after creating a question
     * @param  int $post_id
     * @param  object $post
     * @return void
     * @since 1.0
     */
    public function after_new_question($post_id, $post)
    {
        global $validate;

        if(empty($validate))
            return;

        $fields = $validate->get_sanitized_fields();

        if(isset($fields['category']))
            wp_set_post_terms( $post_id, $fields['category'], 'question_category' );
    }

    public function page_title($title){
        if(is_question_categories()){
            $title = ap_opt('categories_page_title');
        }
        elseif(is_question_category()){
            $category_id = sanitize_text_field( get_query_var( 'q_cat'));
            $category = get_term_by(is_numeric($category_id) ? 'id' : 'slug', $category_id, 'question_category');
            $title = sprintf(__('Question category: %s', 'ap'), $category->name);
        }

        return $title;
    }

    public function option($fields){
        $settings = ap_opt();
        
        $fields[] = array(
            'name' => 'anspress_opt[categories_page_title]',
            'label' => __('Categories title', 'ap') ,
            'desc' => __('Title of the categories page', 'ap') ,
            'type' => 'text',
            'value' => $settings['categories_page_title'],
            'show_desc_tip' => false,
        );

        return $fields;
    }


}

/**
 * Get everything running
 *
 * @since 1.0
 *
 * @access private
 * @return void
 */

function categories_for_anspress() {
    $discounts = new Categories_For_AnsPress();
}
add_action( 'plugins_loaded', 'categories_for_anspress' );

/**
 * Output question categories
 * @param  array  $args 
 * @return string
 */
function ap_question_categories_html($args = array()){

    $defaults  = array(
        'question_id'   => get_the_ID(),
        'list'           => false,
        'tag'           => 'span',
        'class'         => 'question-categories',
        'label'         => __('Categories', 'categories_for_anspress'),
        'echo'          => false
    );


    if(!is_array($args)){
        $defaults['question_id'] = $args;
        $args = $defaults;
    }else{
        $args = wp_parse_args( $args, $defaults );
    }
    
    $cats = get_the_terms( $args['question_id'], 'question_category' );
    
    if($cats){
        $o = '';
        if($args['list']){
            $o = '<ul class="'.$args['class'].'">';
            foreach($cats as $c){
                $o .= '<li><a href="'.esc_url( get_term_link($c)).'" title="'.$c->description.'">'. $c->name .'</a></li>';
            }
            $o .= '</ul>';
            
        }else{
            $o = $args['label'];
            $o .= '<'.$args['tag'].' class="'.$args['class'].'">';
            foreach($cats as $c){
                $o .= '<a href="'.esc_url( get_term_link($c)).'" title="'.$c->description.'">'. $c->name .'</a>';
            }
            $o .= '</'.$args['tag'].'>';
        }
        if($args['echo'])
            echo $o;

        return $o;
    }

}


function ap_category_details(){
        
    $var = get_query_var('question_category');

    $category = get_term_by('slug', $var, 'question_category');

    echo '<div class="clearfix">';
    echo '<h3><a href="'.get_category_link( $category ).'">'. $category->name .'</a></h3>';
    echo '<div class="ap-taxo-meta">';
    echo '<span class="count">'. $category->count .' '.__('Questions', 'categories_for_anspress').'</span>'; 
    echo '<a class="aicon-rss feed-link" href="' . get_term_feed_link($category->term_id, 'question_category') . '" title="Subscribe to '. $category->name .'" rel="nofollow"></a>';
    echo '</div>';
    echo '</div>';
    
    echo '<p class="desc clearfix">'. $category->description .'</p>';
    
    $child = get_terms( array('taxonomy' => 'question_category'), array( 'parent' => $category->term_id, 'hierarchical' => false, 'hide_empty' => false )); 
                   
    if($child) : 
        echo '<ul class="ap-child-list clearfix">';
            foreach($child as $key => $c) :
                echo '<li><a class="taxo-title" href="'.get_category_link( $c ).'">'.$c->name.'<span>'.$c->count.'</span></a>';
                echo '</li>';
            endforeach;
        echo'</ul>';
    endif;  
}
function ap_sub_category_list($parent){
    $categories = get_terms( array('taxonomy' => 'question_category'), array( 'parent' => $parent, 'hide_empty' => false ));
    
    if($categories){
        echo '<ul class="ap-sub-taxo ap-ul-inline clearfix">'; 
        foreach ($categories as $cat){
            echo '<li><a href="'.get_category_link( $cat ).'">' .$cat->name.'<span>'.$cat->count.'</span></a></li>';
        }
        echo '</ul>';
    }
}

function ap_question_have_category($post_id = false){
    if(!$post_id)
        $post_id = get_the_ID();

    
    $categories = wp_get_post_terms( $post_id, 'question_category');
    if(!empty($categories))
        return true;
    
    return false;
}

/**
 * Check if anspress categories page
 * @return boolean
 * @since  1.0
 */
function is_question_categories(){
    if('categories' == get_query_var( 'ap_page' ))
        return true;
        
    return false;
}

function is_question_category(){
    if('category' == get_query_var( 'ap_page' ))
        return true;
        
    return false;
}


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
 * Version:           1.1
 * Author:            Rahul Aryan
 * Author URI:        http://wp3.in
 * Text Domain:       ap
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
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

        $this->includes();

        // internationalization
        add_action( 'init', array( $this, 'textdomain' ) );

        //Register question categories
        add_action('init', array($this, 'register_question_categories'), 1);
        add_action('ap_admin_menu', array($this, 'admin_category_menu'));
        add_filter('ap_default_options', array($this, 'ap_default_options') );
        add_action('ap_option_navigation', array($this, 'option_navigation' ));
        add_action('ap_option_fields', array($this, 'option_fields' ));
        add_action('ap_display_question_metas', array($this, 'ap_display_question_metas' ), 10, 2);
        add_action('ap_before_question_title', array($this, 'ap_before_question_title' ));
        add_shortcode( 'anspress_question_categories', array( 'AnsPress_Categories_Shortcode', 'anspress_categories' ) );
        add_shortcode( 'anspress_question_category', array( 'AnsPress_Category_Shortcode', 'anspress_category' ) );
        add_action( 'ap_enqueue', array( $this, 'ap_enqueue' ) );
        add_filter('term_link', array($this, 'term_link_filter'), 10, 3);
        add_action('ap_ask_form_fields', array($this, 'ask_from_category_field'), 10, 2);
        add_action('ap_ask_fields_validation', array($this, 'ap_ask_fields_validation'));
        add_action( 'ap_after_new_question', array($this, 'after_new_question'), 10, 2 );
        add_action( 'ap_after_update_question', array($this, 'after_new_question'), 10, 2 );
    }

    public function includes(){
        require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'shortcode-categories.php' );
        require_once( CATEGORIES_FOR_ANSPRESS_DIR . 'shortcode-category.php' );
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
        if(ap_opt('enable_categories')){

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
                'rewrite' => false
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
    }

    /**
     * Apppend default options
     * @param   array $defaults
     * @return  array           
     * @since   1.0
     */             
    public function ap_default_options($defaults)
    {
        $defaults['enable_categories']  = true;

        return $defaults;
    }

    /**
     * Add category menu in wp-admin
     * @return void
     * @since 2.0
     */
    public function admin_category_menu(){
        if(ap_opt('enable_categories'))
            add_submenu_page('anspress', 'Questions Category', 'Category', 'manage_options', 'edit-tags.php?taxonomy=question_category');
    }

    /**
     * Register categories option tab in AnsPress options
     * @param  array $navs Default navigation array
     * @return array
     * @since 1.0
     */
    public function option_navigation($navs){
        $navs['categories'] =  __('Categories', 'categories_for_anspress');
        return $navs;
    }

    /**
     * Option fields
     * @param  array  $settings
     * @return string
     * @since 1.0
     */
    public function option_fields($settings){
        $active = (isset($_REQUEST['option_page'])) ? $_REQUEST['option_page'] : 'general' ;
        if ($active == 'categories') {
            ?>
                <div class="tab-pane" id="ap-categories">       
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="enable_categories"><?php _e('Enable categories', 'ap'); ?></label></th>
                            <td>
                                <input type="checkbox" id="enable_categories" name="anspress_opt[enable_categories]" value="1" <?php checked( true, $settings['enable_categories'] ); ?> />
                                <p class="description"><?php _e('Enable or disable categories system', 'ap'); ?></p>
                            </td>
                        </tr>
                        
                    </table>
                </div>
            <?php
        }
        
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
        if(ap_opt('enable_categories') &&  ap_question_have_category($question_id) && !is_singular('question'))
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
            $url = add_query_arg(array('question_category' => $term->term_id), get_permalink(ap_opt('question_category_page_id')));
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
        global $editing_post;

        if($editing){
            $category = get_the_terms( $editing_post->ID, 'question_category' );
            $catgeory = $category[0]->term_id;
        }

        $args['fields'][] = array(
            'name' => 'category',
            'label' => __('Category', 'ap'),
            'type'  => 'taxonomy_select',
            'value' => ( $editing ? $catgeory :  sanitize_text_field(@$_POST['category'] ))  ,
            'taxonomy' => 'question_category',
            'desc' => __('Select a topic that best fits your question', 'ap'),
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
        $fields = $validate->get_sanitized_fields();

        if(isset($fields['category']))
            wp_set_post_terms( $post_id, $fields['category'], 'question_category' );
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
 * Register activatin hook
 * @return void
 * @since  1.0
 */
function activate_categories_for_anspress(){
    // create and check for categories base page
    
    $page_to_create = array('question_categories' => __('Categories', 'categories_for_anspress'), 'question_category' => __('Category', 'categories_for_anspress'));

    foreach($page_to_create as $k => $page_title){
        // create page
        
        // check if page already exists
        $page_id = ap_opt("{$k}_page_id");
        
        $post = get_post($page_id);

        if(!$post){
            
            $args['post_type']          = "page";
            $args['post_content']       = "[anspress_{$k}]";
            $args['post_status']        = "publish";
            $args['post_title']         = $page_title;
            $args['comment_status']     = 'closed';
            $args['post_parent']        = ap_opt('questions_page_id');
            
            // now create post
            $new_page_id = wp_insert_post ($args);
        
            if($new_page_id){
                $page = get_post($new_page_id);
                ap_opt("{$k}_page_slug", $page->post_name);
                ap_opt("{$k}_page_id", $page->ID);
            }
        }
    }
}
register_activation_hook( __FILE__, 'activate_categories_for_anspress'  );

/**
 * Output question categories
 * @param  array  $args 
 * @return string
 */
function ap_question_categories_html($args = array()){
    
    if(!ap_opt('enable_categories'))
        return;

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
    if(!ap_opt('enable_categories'))
        return;
        
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
        
    if(!ap_opt('enable_categories'))
        return false;
    
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
    if(get_the_ID() == ap_opt('question_categories_page_id'))
        return true;
        
    return false;
}

function is_question_category(){
    if(get_the_ID() == ap_opt('question_category_page_id'))
        return true;
        
    return false;
}


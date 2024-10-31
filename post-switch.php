<?php
/*
Plugin Name: Post Switch
Plugin URI: http://chaozh.com
Description: Quickly switch edit post 
Version: 1.1
Author : chaozh
Author URI: http://chaozh.com
*/
define(POSTSWITCH_PLUGIN,'post_switch');
define(POSTSWITCH_VERSION,'1.1');
load_plugin_textdomain(POSTSWITCH_PLUGIN,PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) );

function get_plugin_url(){
    return trailingslashit(apply_filters('get_plugin_url', plugins_url('', __FILE__)));
}

function postswitch_add_custom_box(){
    wp_enqueue_script(POSTSWITCH_PLUGIN, get_plugin_url().'js/postswitch.js', array('jquery'), POSTSWITCH_VERSION);
	//wp_enqueue_style(POSTSWITCH_PLUGIN, get_plugin_url().'css/postswitch.css', array(), POSTSWITCH_VERSION, 'screen');       
    
    if ( function_exists( 'add_meta_box' ) ) {
        add_meta_box('postswitch_sidebar_meta_box',__('Post Switch',POSTSWITCH_PLUGIN),'postswitch_meta_box','post','side','high');
    }
}
add_action( 'add_meta_boxes', 'postswitch_add_custom_box' );

if (!function_exists('utf8Substr')) {
 function utf8Substr($str, $from, $len)
 {
     return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
          '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
          '$1',$str);
 }
}

//code from link-template.php:adjacent_post_link
function adjacent_edit_post_link($format, $link, $in_same_cat = false, $excluded_categories = '', $previous = true) {
	if ( $previous && is_attachment() )
		$post = & get_post($GLOBALS['post']->post_parent);
	else
		$post = get_adjacent_post($in_same_cat, $excluded_categories, $previous);

	if ( !$post )
		return;

	$title = $post->post_title;

	if ( empty($post->post_title) )
		$title = $previous ? __('Previous Post') : __('Next Post');

	$title = apply_filters('the_title', $title, $post->ID);
    $title_display = utf8Substr($title,0,8).'...';
	//$date = mysql2date(get_option('date_format'), $post->post_date);
	$rel = $previous ? 'prev' : 'next';

	$string = '<a href="'.get_edit_post_link( $post->ID, true ).'" rel="'.$rel.'" title="'.__('Edit Post:',POSTSWITCH_PLUGIN).$title.'">';
	$link = str_replace('%title', $title_display, $link);
	//$link = str_replace('%date', $date, $link);
	$link = $string . $link . '</a>';

	$format = str_replace('%link', $link, $format);

	$adjacent = $previous ? 'previous' : 'next';
	echo apply_filters( "{$adjacent}_edit_post_link", $format, $link );
}

function pre_next_edit_post_links($in_same_cat = false, $excluded_categories = '') {
    echo "<div class=\"misc-pub-section\">";
	adjacent_edit_post_link('&laquo; %link',$link='%title', $in_same_cat, $excluded_categories, true);
    echo "&nbsp;&nbsp;&nbsp;";
    adjacent_edit_post_link('%link &raquo;',$link='%title', $in_same_cat, $excluded_categories, false);
    echo "</div>";
}

//code from category-template.php:wp_list_categories
function get_the_category_parents( $id, &$visited = array()) {
	$parent = &get_category( $id );
    $parents = array();  
    $tmps = array();

	if ( is_wp_error( $parent ) )
		return $parents;
        
    $parents[$id] = $parent;
    
	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
        $visited[] = $parent->parent;
        $tmps = get_the_category_parents( $parent->parent, $visited );
        $parents = $parents + $tmps;
	}
    return $parents;
}

function categories_dropdown(){
    //code from post.php
    if(isset($_GET['post']))
        $post_id = (int) $_GET['post'];
        
    $post = $post_type = $post_type_object = null;    
    if($post_id) 
        $post = get_post( $post_id );
        
    if ( $post ) {
	   $post_type = $post->post_type;
	   $post_type_object = get_post_type_object( $post_type );
    }
    //code from category-template.php:wp_list_categories
    $categories = get_the_category( $post_id );
    if (!empty($categories)){
        $default = $categories[0]->term_id;
        //add surport to parent category
        $visited = array();
        $parents = array();
        $nodes = array();
        foreach($categories as $category){
            $visited[] = $category->term_id;
            $nodes[$category->term_id] = $category;
            if($category->parent && ( $category->parent != $category->term_id ) && !in_array( $category->parent, $visited )){
                $visited[] = $category->parent;
                $parents = get_the_category_parents($category->parent, $visited);
                $nodes = $nodes + $parents;
            }
        }            
        $categories = $nodes;
    }
    
    $tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";
    
    $cat_id = null;
    $output = "<div class=\"misc-pub-section category-add\"><label for=\"post-switch-categories\">";
    $output .= __('Categories:(click to switch)',POSTSWITCH_PLUGIN);
    $output .= "</label>\n";
    $output .= "<select name='categories' id='categories-dropdown' class='dropdown' $tab_index_attribute>\n";
    if (!empty($categories)){
        foreach($categories as $category){
            $cat_name = apply_filters('list_cats', $category->name, $category);
            $output .= "\t<option value=\"".$category->term_id."\"";
		    if ( $category->term_id == $default )
		       	$output .= ' selected="selected"';
            $output .= '>';
            $output .= $cat_name;
            $output .= "</option>\n";
        }
    }else{
        $output .= "\t<option value=\"--\">".__('Uncategorized')."</option>\n";
    }
    $output .= "</select>\n";
    $output .= "<select name='posts' id='posts-dropdown' class='dropdown' $tab_index_attribute>\n";
    $output .= "\t<option value=\"--\">".__('No matches found.')."</option>\n";
    $output .= "</select></div>\n";
    echo $output;
}

function get_posts_dropdown_list(){
	$cat_id = intval($_POST['cat_id']);
    $list = $node = array();
    if (!is_null($cat_id)){
        $args = array('cat'=>$cat_id,'nopaging'=>true);
        $query = new WP_Query( $args );
        if ( $query->have_posts() ) :
            while ($query->have_posts()) : $query->the_post();
                $node['id'] = get_the_ID();
                $node['title'] = utf8Substr(get_the_title(),0,20);
                $list[]=$node;
            endwhile;
        endif;
    }
	header('Content-type: text/javascript');
	//echo json_encode(compact('html'));
    echo json_encode($list);
	exit;
}
add_action('wp_ajax_get_posts_dropdown_list', 'get_posts_dropdown_list');

function postswitch_meta_box(){
    //get category and next postid firstly
    //for older post
    categories_dropdown();
    pre_next_edit_post_links();
}

?>
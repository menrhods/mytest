<?php 
/*
Plugin Name: Resources
Description: Plugin for resources
Author: Boylen
Version: 1.0
Author URI: https://www.boylen.com.au
*/
		
class Resources {
	function __construct()
	{
		// Hook into the 'init' action
		add_action( 'init', array($this, 'init'), 0 );
		add_action('wp_enqueue_scripts', array($this, 'register_script'), 0);

		// remove uneccessary meta boxes and editor
		add_action('admin_init', array($this, 'remove_metas'), 0);
		
		// create custom meta
		add_action('admin_init', array($this, 'create_metas'), 0);
		
		// save custom meta
		add_action('save_post', array($this, 'resource_widget_save'), 0);

		$post_type="tax_resources";
		add_filter("manage_${post_type}_posts_columns", array($this, 'tax_resources_edit_columns'), 10);
		add_action("manage_${post_type}_posts_custom_column", array($this, 'tax_resources_custom_columns'), 10, 2);
		
		add_shortcode( 'tax_resources', array($this, 'tax_resources_shortcode'));
	}
		
	public function init()
	{
		$labels = array(
			'name' => _x('Resources', 'tax_resources'),
			'singular_name' => _x('Resources', 'resource'),
			'add_new' => _x('Add New', 'resources'),
			'add_new_item' => __('Add New Resources'),
			'edit_item' => __('Edit  Item'),
			'new_item' => __('New Resources'),
			'view_item' => __('View Resources'),
			'search_items' => __('Search Resources'),
			'not_found' =>  __('Nothing found'),
			'not_found_in_trash' => __('Nothing found in Trash'),
			'parent_item_colon' => ''
		);
		register_post_type( 'tax_resources',
			array(
				'labels'                => $labels,
				'public'                => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'supports'              => array( 'title' ),
				'rewrite'               => array( 'slug' => 'tax_resources', 'with_front' => false ),
				'menu_position'					=> 100,
				'menu_icon'							=> 'dashicons-editor-table',
				'has_archive'           => true
			)
		);
		flush_rewrite_rules();
		$args = array(
			'hierarchical' => true, 
			'label' => 'Resources Category', 
			'public'                        => true,
			'hierarchical'                  => true,
			'show_ui'                       => true,
			'show_in_nav_menus'             => true,
			'args'                          => array( 'orderby' => 'term_order' ),
			'rewrite'                       => true,
			'query_var'                     => true
		);
		register_taxonomy( 'tax_resources', array('tax_resources'), $args );
	}
	
	function remove_metas()
  {
		remove_post_type_support('tax_resources', 'editor');
		remove_meta_box( 'commentstatusdiv' , 'tax_resources' , 'normal' );
		remove_meta_box( 'commentsdiv' , 'tax_resources' , 'normal' );
		remove_meta_box( 'authordiv' , 'tax_resources' , 'normal' );
		remove_meta_box( 'postimagediv','tax_resources','normal' );
	}
	
	public function create_metas()
	{
		add_meta_box("resources_meta_box", "Resource Number", array($this, 'resources_meta_box'), "tax_resources", "normal", "high");
	}
	
	public function resources_meta_box()
	{
		$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'];
		$resource_number = get_post_meta( $post_id, 'resource_number', true );
		$resource_last_updated = get_post_meta( $post_id, 'resource_last_updated', true );
		echo '<div class="form-field meta-title-wrap">';
		echo '<input type="text" name="resource_number" value="'.$resource_number.'" placeholder="Please enter resource number" />';
		echo '<div style="padding-top:10px;">Last Updated: ' . $resource_last_updated . '</div>';
		echo '<br /><br />';
		echo '<small>Resources can be included in any page by entering [tax_resources]</small>';
		echo '</div>';
	}
	
	function resource_widget_save() {
		$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'];
		if(isset($_POST['resource_number'])) {
			$resource_number = $_POST['resource_number'];
			$resource_last_updated = date('Y-m-d h:i:s');
			update_post_meta($post_id, 'resource_number', $resource_number);
			update_post_meta($post_id, 'resource_last_updated', $resource_last_updated);
		}
	}
	
	function tax_resources_edit_columns($columns){
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Title",
			"number" => "Number",
			"lastupdated" => "Last Updated",
		);
	 
		return $columns;
	}
	function tax_resources_custom_columns($column){
		global $post;
	 
		switch ($column) {
			case "number":
				$resource_number = get_post_meta( $post->ID, 'resource_number', true );
				echo($resource_number);
				break;
			case "lastupdated":
				$resource_last_updated = get_post_meta( $post->ID, 'resource_last_updated', true );
				echo($resource_last_updated);
				break;
		}
	}
	
	function tax_resources_shortcode($atts) {
		$terms = get_terms( 'tax_resources',array('hide_empty' => false));
		$count = count( $terms );
		
		$html='<table class="table resource-table table-bordered">';
			$html.='<tbody>';
				if($count>0) {
					foreach ( $terms as $term ) {
						$html.='<tr>';
							$html.='<th colspan="3" style="padding-bottom:0;"><h2 style="margin-bottom:0;">' . $term->name . '</h2></th>';
						$html.='</tr>';
						$html.='<tr>';
							$html.='<th>Number</th>';
							$html.='<th>Title</th>';
							$html.='<th>Last Updated</th>';
						$html.='</tr>';
						$args = array( 'post_type' => 'tax_resources', 'post_status' => 'publish',
						'tax_query' => [
						[
								'taxonomy' => 'tax_resources',
								'terms' =>  $term->term_id,
								'include_children' => true // Remove if you need posts from term 7 child terms
						]],); 
						$postslist = get_posts( $args );    
    				foreach ($postslist as $post) :  setup_postdata($post);
							$resource_number = get_post_meta( $post->ID, 'resource_number', true );
							$resource_last_updated = get_post_meta( $post->ID, 'resource_last_updated', true );

							$html.='<tr>';
								$html.='<td>' . $resource_number . '</td>';
								$html.='<td>' . get_the_title($post->ID) . '</td>';
								$html.='<td>' . date('d M Y', strtotime($resource_last_updated)) . '</td>';
							$html.='</tr>';
    				endforeach;
						wp_reset_postdata();
					}					
				}
			$html.='</tbody>';
		$html.='</table>';
		return $html;
	}
	
	function register_script() {
		wp_enqueue_style( 'resources-stylesheet', plugins_url('/css/style.css', __FILE__), false, '1.0.0', 'all');
	}
}

$resources = new Resources();
?>
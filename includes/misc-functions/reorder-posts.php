<?php
//The scripts on this page allow posts to be reordered in the admin area

//Admin scripts
function mp_core_admin_enqueue_scripts(){
	
	//Get current page
	$current_page = get_current_screen();
	
	//Only load if we are on an edit based page
	if ( $current_page->base == 'edit' ){
 	
		//Sortable Posts js	
		wp_enqueue_script( 'sortable', plugins_url( 'js/core/sortable.js', dirname(__FILE__) ),  array( 'jquery' ) );	
		
		//Allows posts to be reordered by dragging and dropping if the 'menu_order' column has been added to the post type
		wp_enqueue_script( 'mp-sortable-posts-js', plugins_url( 'js/core/mp-sortable-posts.js', dirname(__FILE__)),  array( 'jquery') );
		
		//Style the 'menu_order' column
		wp_enqueue_style( 'mp-sortable-posts-css', plugins_url( 'css/core/mp-core-sortable-posts.css', dirname(__FILE__) ) );
		
	}
	
}
add_action( 'admin_enqueue_scripts', 'mp_core_admin_enqueue_scripts' );

/**
* Save new menu order for each post
* When a post is reordered, this function fires to loop through each of the values in the GET variable with the prefix 'mp_order'
* It then updates the post in the database
*/
function mp_core_reorder_posts_on_submit(){
	//Only do this if the mp_submitted_order field has been submitted
	if ( isset( $_GET['mp_submitted_order'] ) ){
		//Loop through each value in the GET variable
		foreach ($_GET as $key => $value) { 
			//If this value starts with 'mp_order'
		 	if ( strpos($key, 'mp_order') !== false ){
				
				//Extract the post id 
				$post_id = explode( 'mp_order_', $key );
				$post_id = $post_id[1];
				
				//Set the new values for this post
				$this_post['ID'] = $post_id;
				$this_post['menu_order'] = $value;
				
				// Update the post into the database
				wp_update_post( $this_post );
			}
		}
	}
}
add_action('admin_init', 'mp_core_reorder_posts_on_submit' );

/**
* add order column to admin listing screen for header text
*/
function mp_core_add_new_post_column($columns) {
	
	$new_column = array('menu_order' => '' );
	
	return array_merge( $new_column, $columns );
					
}

/**
* show custom order column values
*/
function mp_core_show_order_column($name){
  global $post;

  switch ($name) {
    case 'menu_order':
      $order = $post->menu_order;
      echo '<input type="hidden" class="mp_menu_order" name="mp_order_' . get_the_ID() . '" value="' . $order . '">';
	  echo '<input type="hidden" name="mp_submitted_order" value="true">';
	  echo '<div class="menu-order-drag-button"><img src="' . plugins_url( 'images/grippy_large.png', dirname(__FILE__)) . '"/></div>';
      break;
   default:
      break;
   }
}

/**
* Make all hierarchical post types sortable
*/
function mp_core_make_all_hierarchical_posts_sortable(){
	$hierarchical_post_types = mp_core_get_all_hierarchical_post_types();
	
	foreach ( $hierarchical_post_types as $id => $post_type ){
		
		add_filter('manage_' . $id . '_posts_columns', 'mp_core_add_new_post_column');
		add_action('manage_' . $id . '_posts_custom_column','mp_core_show_order_column');
		
	}
}
add_action( 'init', 'mp_core_make_all_hierarchical_posts_sortable' );

/**
* To make any post type sortable, use the code below and sub in your posttype
*/
//add_filter('manage_CUSTOMPOSTTYPE_posts_columns', 'mp_core_add_new_post_column');
//add_action('manage_CUSTOMPOSTTYPE_posts_custom_column','mp_core_show_order_column');
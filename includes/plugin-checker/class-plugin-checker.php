<?php
/**
 * This file contains the MP_CORE_Plugin_Checker class and its activating function
 *
 * @link http://moveplugins.com/doc/plugin-checker-class/
 * @since 1.0.0
 *
 * @package    MP Core
 * @subpackage Classes
 *
 * @copyright  Copyright (c) 2013, Move Plugins
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @author     Philip Johnston
 */
 
/**
 * Plugin Checker Class for the MP Core Plugin by Move Plugins.
 * 
 * This Class is run at init by the 'mp_core_plugin_checker' function. 
 * It accepts a multidimentional array of plugins to check for existence.
 * Plugins can either be installed in a group, or singularly (1 at a time). If they are set to be 1 at a time
 * there will be a notification in the Dashboard that it needs to be installed/Activated. This is configured in the $args array.
 * Note: The actual check only happens on the admin side so resources are not being wasted on the front end
 *
 * @author     Philip Johnston
 * @link       http://moveplugins.com/doc/plugin-checker-class/
 * @since      1.0.0
 * @return     void
 */
if ( !class_exists( 'MP_CORE_Plugin_Checker' ) ){
	class MP_CORE_Plugin_Checker{
		
		/**
		 * Constructor
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_create_pages()
		 * @see      MP_CORE_Plugin_Checker::mp_core_plugin_check_notice()
		 * @see      wp_parse_args()
		 * @param    array $args {
		 *      This array holds information for multiple plugins. Visit link for details.
		 *		@type array {
		 *      	This array could be in here an unlimited number of times and holds Plugin information. Visit link for details.
		 *			@type string 'plugin_name' Name of plugin.
		 *			@type string 'plugin_message' Message which shows up in notification for plugin.
		 *			@type string 'plugin_filename' Name of plugin's main file
		 *			@type string 'plugin_download_link' Link to URL where this plugin's zip file can be downloaded
		 *			@type string 'plugin_info_link' Link to URL containing info for this plugin 
		 * 			@type bool   'plugin_required' Whether or not this plugin is required
		 *			@type bool   'plugin_group_install' Whether to install this plugin with "the group" or on it's own
		 *			@type bool   'plugin_wp_repo' Whether to look for this plugin on the WP Repo or not	
		 * 		}
		 * }
		 * @return   void
		 */
		public function __construct( $args ){
				
			//Set defaults for args		
			foreach ( $args as $key => $arg ){
				
				$defaults[$key] = array(
					'plugin_name' => NULL,  
					'plugin_message' => NULL, 
					'plugin_filename' => NULL,
					'plugin_download_link' => NULL,
					'plugin_info_link' => NULL,
					'plugin_required' => false,
					'plugin_group_install' => true,
					'plugin_wp_repo' => true
				);
				
				//Get and parse args
				$this->_args[$key] = wp_parse_args( $args[$key], $defaults[$key] );
				
			}
			
			//Set up install page/pages
			$this->mp_core_create_pages();
																			
			//Get the "page" URL variable
			$page = isset($_GET['page']) ? $_GET['page'] : NULL;
			
			//Make sure we are not on the "mp_core_install_plugins_page" page - where this message isn't necessary			
			if ( stripos( $page, 'mp_core_install_plugins_page' ) === false ){
				
				//Also ,ake sure we are not on the "mp_core_install_plugin_page" page (singular) - where this message also isn't necessary			
				if ( stripos( $page, 'mp_core_install_plugin_page' ) === false ){
					
					//Check for plugins in question
					add_action( 'admin_notices', array( $this, 'mp_core_plugin_check_notice') );
					
				}
			}
						
		}
		
		/**
		 * Loop through each passed-in plugin to see if it needs an install page
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_install_plugins_page()
		 * @see      plugins_api()
		 * @see      sanitize_title()
		 * @see      MP_CORE_Plugin_Installer
	 	 * @return   void
		 */
		public function mp_core_create_pages(){
			
			//Loop through each plugin that is supposed to be installed
			foreach ( $this->_args as $plugin_key => $plugin ){
				
				$plugin_name_slug = sanitize_title ( $plugin['plugin_name'] ); //EG move-plugins-core
					
				// Create update/install plugins page
				add_action('admin_menu', array( $this, 'mp_core_install_plugins_page') );
				
				//Stop looping - only one install page is needed
				//break;
				
				//If we should check the WP Repo
				if ( $plugin['plugin_wp_repo'] ){
					
					/** If plugins_api isn't available, load the file that holds the function */
					if ( !function_exists( 'plugins_api' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
					}
	
					//Check if this plugin exists in the WP Repo
					$args = array( 'slug' => $plugin_name_slug);
					$api = plugins_api( 'plugin_information', $args );					
					
					//If it does exist in the WP Repo
					if (!isset($api->errors)){ 
					
						//Set the plugin's download link to be the one from the WP Repo
						$this->_args[$plugin_key]['plugin_download_link'] = $api->download_link;
						
						//Set the same for the current loop's plugin_download_link
						$plugin['plugin_download_link'] = $api->download_link;
	
					}
					
				}
				
				//If this plugin is NOT supposed to be installed as part of the group, create its WordPress install page
				if ( !$plugin['plugin_group_install'] ){
					//Create Install Page for this plugin
					new MP_CORE_Plugin_Installer( $plugin );
				}
										
			}
		}
		
		/**
		 * Create page where plugins are installed called "mp_core_install_plugins_page"
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_install_check_callback()
		 * @see      get_plugin_page_hookname()
	 	 * @return   void
		 */
		public function mp_core_install_plugins_page()
		{
			// This WordPress variable is essential: it stores which admin pages are registered to WordPress
			global $_registered_pages;
		
			// Get the name of the hook for this plugin
			// We use "plugins.php" as the parent as we want our page to appear under "plugins.php?page=mp_core_install_plugins_page"
			$hookname = get_plugin_page_hookname('mp_core_install_plugins_page', 'plugins.php');
		
			// Add the callback via the action on $hookname, so the callback function is called when the page "plugins.php?page=mp_core_install_plugins_page" is loaded
			if (!empty($hookname)) {
				add_action($hookname, array( $this, 'mp_core_install_check_callback') );
			}
		
			// Add this page to the registered pages
			$_registered_pages[$hookname] = true;
					
		}
		
		/**
		 * This is the callback function for the "install plugin page" above. This creates the output for the install plugins page
		 * and uses the mp_core_check_plugins function with the $show_notices set to false. In doing so, it gets an array of each
		 * plugin that needs tp be installed so that we can run the MP_CORE_Plugin_Installer class for each plugin.
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_check_plugins()
		 * @see      screen_icon()
		 * @see      MP_CORE_Plugin_Installer
	 	 * @return   void
		 */
		public function mp_core_install_check_callback() {
								
			echo '<div class="wrap">';
			
			screen_icon();
						
			echo '<h2>' . __('Install Items', 'mp_core') . '</h2>';
						
			//Check plugins and store needed ones in $plugins
			$plugins = $this->mp_core_check_plugins( $this->_args, false );
			
			//Loop through each plugin that is supposed to be installed
			foreach ( $plugins as $plugin_key => $plugin ){
			
				//Install and activate this plugin - right here, right now
				new MP_CORE_Plugin_Installer( $plugin );
				
			}
			
			//Redirect when complete
			$custom_page_extension = NULL; //'about.php?updated'; Evenutally this will redirect to a page of some kind. For now it goes to the dashboard
			
			//Javascript for redirection
			echo '<script type="text/javascript">';
				echo "window.location = '" . self_admin_url( $custom_page_extension ) . "';";
			echo '</script>';
			
			echo '</div>';
									
		}
		
		/**
		 * This is run on the "admin_notices" WP Hook and calls the mp_core_check_plugins() function 
		 * with the $show_notices parameter set to true so that it echoes notices in the WP dashboard for installation of necessary plugins.
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_check_plugins()
	 	 * @return   void
		 */
		public function mp_core_plugin_check_notice() {
			
			//Check plugins and output notices
			$this->mp_core_check_plugins( $this->_args, true );
			
		}
		
		/**
		 * Check to see each plugin's status and either return them in an array if un-installed/un-activated
		 * Or output a notice that it needs to be installed/activated. This is dependant on the $show_notices parameter. 
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      MP_CORE_Plugin_Checker::mp_core_check_plugins()
		 * @see      MP_CORE_Plugin_Checker::mp_core_close_message()
		 * @see      MP_CORE_Plugin_Checker::mp_core_dismiss_message()
		 * @see      sanitize_title()
		 * @see      apply_filters()
		 * @see      get_option()
		 * @see      wp_nonce_url()
	 	 * @param    array $plugins This has the same format as the $args plugin in the construct function of this class
		 * @param    boolean $show_notices If true it will output notices and return nothing. If false it will return an array of plugins
		 * @return   array $plugins If $show_notices is set to false this will be returned and match the $plugins array 
		 */
		public function mp_core_check_plugins( $plugins, $show_notices = false ) {
			
			//Set plugins to install to be a blank array
			$plugins_to_install = array();
						
			//Loop through each plugin that is supposed to be installed
			foreach ( $plugins as $plugin_key => $plugin ){
								
				//Set plugin name slug by sanitizing the title. Plugin's title must match title in WP Repo
				$plugin_name_slug = sanitize_title ( $plugin['plugin_name'] ); //EG move-plugins-core
				
				//Get array of active plugins - duplicate_hook
				$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ));
				
				//Set default for $plugin_active
				$plugin_active = false;
				
				//Loop through each active plugin's string EG: (subdirectory/filename.php)
				foreach ($active_plugins as $active_plugin){
					//Check if the filename of the plugin in question exists in any of the plugin strings
					if (strpos($active_plugin, $plugin['plugin_filename'])){	
						
						//Plugin is active
						$plugin_active = true;
						
						//Stop looping
						break;
						
					}
				}
				
				
				//If this plugin is not active
				if (!$plugin_active){
				
					//If the user has just clicked "Dismiss", than add that to the options table
					$this->mp_core_close_message( $plugin );
										
					//Check to see if the user has ever dismissed this message
					if (get_option( 'mp_core_plugin_checker_' . $plugin_name_slug ) != "false"){
												
						//Take steps to see if the Plugin already exists or not
						 
						//Check if the plugin file exists in the plugin root
						$plugin_root_files = array_filter(glob('../wp-content/plugins/' . '*'), 'is_file');
						
						//Preset value for plugin_exists to false
						$plugin_exists = false;
						
						//Preset value for $plugin_directory
						$plugin_directory = NULL;
						
						//Check if the plugin file is directly in the plugin root
						if (in_array( '../wp-content/plugins/' . $plugin['plugin_filename'], $plugin_root_files ) ){
							
							//Set plugin_exists to true
							$plugin_exists = true;
							
						}
						//Check if plugin exists in a subfolder inside the plugin root
						else{	
											 
							//Find all directories in the plugins directory
							$plugin_dirs = array_filter(glob('../wp-content/plugins/' . '*'), 'is_dir');
																								
							//Loop through each plugin directory
							foreach ($plugin_dirs as $plugin_dir){
								
								//Scan all files in this plugin and store them in an array
								$plugins_files = scandir($plugin_dir);
								
								//If the plugin filename in question is in this plugin's array, than this plugin exists but it is not active
								if (in_array( $plugin['plugin_filename'], $plugins_files ) ){
									
									//Set plugin_exists to true
									$plugin_exists = true;
									
									//Set the plugin directory for later use
									$plugin_directory = explode('../wp-content/plugins/', $plugin_dir);
									$plugin_directory = !empty($plugin_directory[1]) ? $plugin_directory[1] . '/' : NULL;
									
									//Stop checking through plugins
									break;	
								}							
							}
						}
						
						//This plugin exists but is just not active
						if ($plugin_exists && $show_notices){
							
								echo '<div class="updated fade"><p>';
								
								echo $plugin['plugin_message'] . '</p>';					
								
								//Activate button
								echo '<a href="' . wp_nonce_url('plugins.php?action=activate&plugin=' . $plugin_directory . $plugin['plugin_filename'] . '&plugin_status=all&paged=1&s=', 'activate-plugin_' . $plugin_directory . $plugin['plugin_filename']) . '" title="' . esc_attr__('Activate this plugin') . '" class="button">' . __('Activate', 'mp_core') . ' "' . $plugin['plugin_name'] . '"</a>'; 
								//Dismiss button
								$this->mp_core_dismiss_button( $plugin );
								
								echo '</p></div>';
						
						//This plugin doesn't even exist on this server	 	
						}else{
																					
							//If this plugin should show notification by itself - not with a group of other plugins
							if ( $plugin['plugin_group_install'] == NULL || !$plugin['plugin_group_install'] ){
								
								//If we are using this function to output notices
								if ($show_notices){
									
									echo '<div class="updated fade"><p>';
							
									echo $plugin['plugin_message'] . '</p>';
								
									//Display a custom download button."; 
									printf( '<a class="button" href="%s" style="display:inline-block; margin-right:.7em;"> ' . __('Automatically Install', 'mp_core') . ' "' . $plugin['plugin_name'] . '"</a>', admin_url( sprintf( 'plugins.php?page=mp_core_install_plugin_page_' . $plugin_name_slug . '&action=install-plugin&_wpnonce=%s', wp_create_nonce( 'install-plugin' ) ) ) );	
									
									//Dismiss button
									$this->mp_core_dismiss_button( $plugin );
									
									echo '</p></div>';
								
								}
							
							}
							
							//If this plugin should install with a group of other plugins
							else{
								
								//Add this plugin to the list of plugins that need to actually be installed.
								$plugins_to_install[$plugin_key] = $plugin;
								
							}
							
						}//If this plugin doesn't exist on this server
					}//If the user has never dismissed this plugin
				}//If this plugin is not active
			}//Loop through each plugin passed in
			
			//If there are Multiple Plugins to install at once
			if ( !empty( $plugins_to_install ) ){
				
				//If we should show the notices in the WP Dashboard
				if ($show_notices){
					
					//Show "Install all items" button
					
					echo '<div class="updated fade"><p>';
										
					echo __( 'There are items that need to be installed.' , 'mp_core' ) . '</p>';
				
					printf( '<a class="button" href="%s" style="display:inline-block; margin-right:.7em;"> ' . __('Install All Items', 'mp_core')  . '</a>', admin_url( sprintf( 'plugins.php?page=mp_core_install_plugins_page&action=install-plugin&_wpnonce=%s', wp_create_nonce( 'install-plugin' ) ) ) );	
					
					echo '| <a href="#TB_inline?width=600&height=550&inlineId=mp-core-installer-details" class="thickbox">' . __( 'Details', 'mp_core' ) . "</a>";
					
					echo '</p></div>';
					
					//Add Thickbox
					add_thickbox();
					
					//Output Details
					echo '<div id="mp-core-installer-details" style="display:none;">';
						 echo '<h2>' . __( 'These items will be installed:', 'mp_core' ) . '</h2>';
							echo '<ol>'; 	
											 
							foreach( $plugins_to_install as $plugin_info ){
								
								echo '<li>';
									echo $plugin_info['plugin_name'] . ' - <a href="' . $plugin_info['plugin_info_link'] . '" target="_blank">' . $plugin_info['plugin_info_link'] . '</a>';
								echo '</li>';
									 
							}
														 
							echo '</ol>';							 
														
							printf( '<a class="button" href="%s" style="display:inline-block; margin-right:.7em;"> ' . __('Install All Items', 'mp_core')  . '</a>', admin_url( sprintf( 'plugins.php?page=mp_core_install_plugins_page&action=install-plugin&_wpnonce=%s', wp_create_nonce( 'install-plugin' ) ) ) );
							
							echo '</p>';	
							
					echo '</div>';
				
				}
				//If we shouldn't show any notices
				else{
					
					//Return the array of plugins that need to be installed.
					return $plugins_to_install;	
				}
			
			}
	
		}//End Function
		
		/**
		 * Function to display "Dismiss" message in notices
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      sanitize_title()
		 * @see      wp_nonce_field()
	 	 * @param    array $dismiss_args This array holds Plugin information matching the second array in the construct function
		 * @return   void
		 */
		 public function mp_core_dismiss_button( $dismiss_args ){
			 
			$plugin_name_slug = sanitize_title ( $dismiss_args['plugin_name'] ); //EG move-plugins-core
			 
			$dismiss_args['plugin_required'] = (!isset($dismiss_args['plugin_required']) ? true : $dismiss_args['plugin_required']);
			if ($dismiss_args['plugin_required'] == false){
				echo '<form id="mp_core_plugin_checker_close_notice" method="post" style="display:inline-block; margin-left:.7em;">
							<input type="hidden" name="mp_core_plugin_checker_' . $plugin_name_slug . '" value="false"/>
							' . wp_nonce_field('mp_core_plugin_checker_' . $plugin_name_slug . '_nonce','mp_core_plugin_checker_' . $plugin_name_slug . '_nonce_field') . '
							<input type="submit" id="mp_core_plugin_checker_dismiss" class="button" value="Dismiss" /> 
					   </form>'; 
			}
		 }
		
		/**
		 * Function to fire if the Close button has been clicked
		 *
		 * @access   public
		 * @since    1.0.0
		 * @see      sanitize_title()
		 * @see      wp_verify_nonce()
		 * @see      update_option()
	 	 * @param    array $close_args This array holds Plugin information matching the second array in the construct function
		 * @return   void
		 */
		 public function mp_core_close_message( $close_args ){
			 
			$plugin_name_slug = sanitize_title ( $close_args['plugin_name'] ); //EG move-plugins-core
			 
			if (isset($_POST['mp_core_plugin_checker_' . $plugin_name_slug])){
				//verify nonce
				if (wp_verify_nonce($_POST['mp_core_plugin_checker_' . $plugin_name_slug . '_nonce_field'],'mp_core_plugin_checker_' . $plugin_name_slug . '_nonce') ){
					//update option to not show this message
					update_option( 'mp_core_plugin_checker_' . $plugin_name_slug, "false" );
				}
			}
		 }
	}
}

/**
 * Get the Plugin Checker Started
 *
 * @since    1.0.0
 * @link     http://moveplugins.com/doc/plugin-checker-class/
 * @author   Philip Johnston
 * @see      MP_CORE_Plugin_Checker
 * @see      apply_filters()
 * @see      add_action()
 * @return   void
 */
if ( !function_exists( 'mp_core_plugin_checker' ) ){
	function mp_core_plugin_checker(){
		
		//Set default for $mp_core_plugins_to_check
		$mp_core_plugins_to_check = array();
		
		/**
		 * Filter Hook for adding plugins to check.
		 *
		 * @since 1.0.0
		 *
		 * @param string 'mp_core_check_plugins' The name of this filter hook.
		 * @param array $mp_core_plugins_to_check {
		 *      This array holds information for multiple plugins. Visit link for details.
		 *		@type array {
		 *      	This array could be in here an unlimited number of times and holds Plugin information. Visit link for details.
		 *			@type string 'plugin_name' Name of plugin.
		 *			@type string 'plugin_message' Message which shows up in notification for plugin.
		 *			@type string 'plugin_filename' Name of plugin's main file
		 *			@type string 'plugin_download_link' Link to URL where this plugin's zip file can be downloaded
		 *			@type string 'plugin_info_link' Link to URL containing info for this plugin 
		 * 			@type bool   'plugin_required' Whether or not this plugin is required
		 *			@type bool   'plugin_group_install' Whether to install this plugin with "the group" or on it's own
		 *			@type bool   'plugin_wp_repo' Whether to look for this plugin on the WP Repo or not	
		 * 		}
		 * }
		 */
		$mp_core_plugins_to_check = apply_filters('mp_core_check_plugins', $mp_core_plugins_to_check );
			
		//Remove duplicate plugins
		$mp_core_plugins_to_check = array_unique($mp_core_plugins_to_check, SORT_REGULAR);
				
		//If nothing to install, quit
		if ( empty( $mp_core_plugins_to_check ) ){
						
			return;	
		}
		
		//Start checking plugins
		new MP_CORE_Plugin_Checker( $mp_core_plugins_to_check );
	}
	add_action( '_admin_menu', 'mp_core_plugin_checker' );
}
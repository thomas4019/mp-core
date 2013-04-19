<?php
/**
 * Plugin Checker Class for the wp_core Plugin by Move Plugins
 * http://moveplugins.com/plugin-checker-class/
 */
		
if ( !class_exists( 'MP_CORE_Theme_Updater' ) ){
	class MP_CORE_Theme_Updater{
		
		public function __construct($args){
				
			//Get args
			$this->_args = $args;
			
			//Set the "Green Light" Notification option for this license		
			add_action( 'admin_init', array( &$this, 'set_license_green_light' ) ); 
			
			//Theme Update Function	
			add_action( 'admin_init', array( &$this, 'mp_core_update_theme' ) ); 	
			
			//Create Option page for updates
			add_action( 'admin_menu', array( &$this, 'updates_menu' ) );
			
			//Show Option Page on Themes page as well
			add_action( 'load-themes.php', array( $this, 'themes_page') ); 
						
		}
					
		/***********************************************
		* This is our updater
		***********************************************/
		function mp_core_update_theme(){
			
			//Get license		
			$license = isset( $_POST[ $this->_args['software_slug'] . '_license_key' ] ) ? 
			//Get from $_POST if the license exists there
			trim( $_POST[ $this->_args['software_slug'] . '_license_key' ] ) : 
			//Otherwise, get from Database saved setting
			trim( get_option( $this->_args['software_slug'] . '_license_key' ) );
									
			//EDD Length: If the length of the key matches the length of normal EDD licenses, do an EDD update
			if ( strlen( $license ) == 32 ){
				
				//Do EDD Update
				if ( !class_exists( 'EDD_SL_Theme_Updater' ) ) {
					// Load our custom theme updater
					include( dirname( __FILE__ ) . '/EDD_SL_Theme_Updater.php' );
				}
								
				//Get theme info
				$theme = wp_get_theme($this->_args['software_slug']); // $theme->Name
												
				//Get current theme version
				$theme_current_version = $theme->Version;
				
				//Call the EDD_Theme Updater Class
				$edd_updater = new EDD_SL_Theme_Updater( array( 
						'remote_api_url' 	=> $this->_args['software_api_url'], 	// Our store URL that is running EDD
						'version' 			=> $theme_current_version, 				// The current theme version we are running
						'license' 			=> $license, 		// The license key (used get_option above to retrieve from DB)
						'item_name' 		=> $this->_args['software_name'],	// The name of this theme
						'author'			=> ''	// The author's name
					)
				);
				
			}
			//Envato Length: If the length of the key matches the length of normal envato licenses, do an envato update
			elseif ( strlen( $license ) == 36){
				
				//Do EDD Update
				if ( !class_exists( 'MP_CORE_MP_REPO_Theme_Updater' ) ) {
					// Load our custom theme updater
					include( dirname( __FILE__ ) . '/class-mp-repo-theme-updater.php' );
				}
												
				//If there is a license entered, call the EDD_Theme Updater Class
				if ( !empty( $license ) ) {
					$edd_updater = new MP_CORE_MP_REPO_Theme_Updater( array( 
							'software_api_url' 	=> $this->_args['software_api_url'], 	// Our store URL that is running EDD
							'software_license' 	=> $license, // The license key (used get_option above to retrieve from DB)
							'software_slug' 	=> $this->_args['software_slug'],	// The slug of this theme
						)
					);
				}
			}
		}
		
		/**
		 * Function which sets the green light variable to let the user know their license is active
		 */
		function set_license_green_light(){
			// listen for our activate button to be clicked
			if( isset( $_POST[ $this->_args['software_slug'] . '_license_key' ] ) ) {
				
				//Check nonce
				if( ! check_admin_referer( $this->_args['software_slug'] . '_nonce', $this->_args['software_slug'] . '_nonce' ) ) 	
					return; // get out if we didn't click the Activate button
				
				// retrieve the license from the $_POST
				$license = trim( $_POST[ $this->_args['software_slug'] . '_license_key' ] );
				
				//Sanitize and update license
				update_option( $this->_args['software_slug'] . '_license_key', wp_kses(htmlentities($license, ENT_QUOTES), '' ) );	 
								
				//If the length of the key matches the length of normal EDD licenses, do an EDD update
				if ( strlen( $license ) == 32 ){
					
					//Set args for EDD Licence check function
					$args = array(
						'software_api_url' => $this->_args['software_api_url'],
						'software_slug'    => $this->_args['software_slug'],
						'software_name'    => $this->_args['software_name'],
						'software_license' => $license,
					);
						
					//Check and update EDD Licence. The mp_core_edd_license_check function in in the mp_core
					update_option( $this->_args['software_slug'] . '_license_status_valid', mp_core_edd_license_check($args) );	
				}
				
				//If the length of the key matches the length of normal ENVATO licenses, do an ENVATO update
				elseif(strlen( $license ) == 36){
					
					//Check the response from the repo if this license is valid					
					$envato_response = wp_remote_post( $this->_args['software_api_url']  . '/repo/' . $this->_args['software_slug'] . '/?envato-check&license=' . $license );
								
					//Check and Update Envato Licence
					update_option( $this->_args['software_slug'] . '_license_status_valid', $envato_response );	
					
				}
				
				//This license length doesn't match any we are checking for and therefore, this license is not valid
				else{
					update_option( $this->_args['software_slug'] . '_license_status_valid', false );
				}
					
			}
		}
		/***********************************************
		* Add our menu item
		***********************************************/
		
		function updates_menu() {
			add_theme_page( 'Theme License', 'Theme License', 'manage_options',  $this->_args['software_slug'] . '-updates', array( &$this, 'updates_page' ) );
		}	
				
		/***********************************************
		* Updates Settings Page
		***********************************************/
		
		function updates_page() {
			
			$license 	= get_option( $this->_args['software_slug'] . '_license_key' );
			$status 	= get_option( $this->_args['software_slug'] . '_license_status_valid' );
			?>
			<div id="mp-core-theme-license-wrap" class="wrap">
				<h2><?php _e('Theme License Options'); ?></h2>
				<form method="post">
									
					<table class="form-table">
						<tbody>
							<tr valign="top">	
								<th scope="row" valign="top">
									<?php _e('License Key'); ?>
								</th>
								<td>
									<input id="edd_sample_theme_license" name="<?php echo $this->_args['software_slug']; ?>_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
									<label class="description" for="<?php echo $this->_args['software_slug']; ?>_license_key"><?php _e('Enter your license key'); ?></label>
                                    
                                    <?php mp_core_true_false_light( array( 'value' => $status, 'description' => $status == true ? 'Your license is valid' : 'This license is not valid!' ) ); ?>
                                    
                                    <?php wp_nonce_field( $this->_args['software_slug'] . '_nonce', $this->_args['software_slug'] . '_nonce' ); ?>
								</td>
							</tr>
						</tbody>
					</table>	
					<?php submit_button(); ?>
				
				</form>
			</div>
			<?php
		}
		
		/***********************************************
		* This function is called on the themes page only
		***********************************************/
		
		function themes_page() {
			
			//Enqueue Jquery on Theme page to place license in correct spot
			function enqueue_themes_scripts() {
				
				wp_enqueue_script( 'mp-core-themes-placement', plugins_url( 'js/themes-page.js', dirname(__FILE__) ),  array( 'jquery' ) );		
			}		
			add_action( 'admin_enqueue_scripts', 'enqueue_themes_scripts' );
			
			//Decalre slug variable
			$software_slug = $this->_args['software_slug'];
			
			//Dispplay the license on the themes page
			$display_license = function () use ($software_slug){
				$license 	= get_option( $this->_args['software_slug'] . '_license_key' );
				$status 	= get_option( $this->_args['software_slug'] . '_license_status_valid' );
				?>
				<div id="mp-core-theme-license-wrap" class="wrap">
					
                    <strong><?php echo __('Updates', 'mp_core'); ?></strong><br />
                    <p class="theme-description"><?php echo __('Enter your license key to enable automatic updates'); ?></p>
                    
					<form method="post">
										
						<input style="float:left; margin-right:10px;" id="edd_sample_theme_license" name="<?php echo $this->_args['software_slug']; ?>_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />						
						<?php mp_core_true_false_light( array( 'value' => $status, 'description' => $status == true ? __('License is valid', 'mp_core') : __('This license is not valid!', 'mp_core') ) ); ?>
						
						<?php wp_nonce_field( $this->_args['software_slug'] . '_nonce', $this->_args['software_slug'] . '_nonce' ); ?>
									
						<?php submit_button(__('Submit License', 'mp_core') ); ?>
					
					</form>
				</div>
				<?php
			};
			add_action( 'admin_notices', $display_license ); 
		}
	}
}
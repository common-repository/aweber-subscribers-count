<?php
/*
Plugin Name: Aweber Subscriber Count
Plugin URI: http://wp-themes-pro.com
Description: This plugin adds a shortcode ability to your list counts.
Author: Alex from WP Themes Pro
Author URI: http://wp-themes-pro.com
Version: 1.6.0
License: GNU General Public License v3.0
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/*  Copyright 2012  Alexandre Bortolotti  (email : alex@wp-themes-pro.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
*/

if ( ! class_exists( 'AWeberAPI' ) )
	require_once( plugin_dir_path( __FILE__ ) . 'lib/aweber_api/aweber_api.php' );
	
if ( ! class_exists( 'TGM_Aweber_Count_Optin' ) ) {
	/**
 	 * Count form optin class for Aweber.
 	 *
 	 * Creates a way for users to add a checkbox to their count forms so
 	 * users can optin to email lists.
 	 *
 	 * @since 1.0.0
 	 *
 	 * @package TGM_Aweber_Count_Optin
 	 */
	class TGM_Aweber_Count_Optin {
		
		/**
	 	 * The name of the plugin options group.
	 	 *
	 	 * @since 1.0.0
	 	 *
	 	 * @var string
	 	 */
		public $option = 'tgm_aweber_Count_settings';
	
		/**
		 * Adds a reference of this object to $instance and hooks in the 
		 * interactions to init.
		 *
		 * Sets the default options for the class.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 */
		public function __construct() {
			
			/** Register and update our options */
			global $tgm_aw_options;
			
			//delete_option( $this->option );
			
			$tgm_aw_options = get_option( $this->option );
			if ( false === $tgm_aw_options )
				$tgm_aw_options = $this->default_options();
			
			update_option( $this->option, $tgm_aw_options );
			
			/** Start the class once the rest of WordPress has loaded */
			add_action( 'init', array( &$this, 'init' ), 11 );
		
		}
		
		/**
		 * Initialize the interactions between this class and WordPress.
		 *
		 * @since 1.0.0
		 */
		public function init() {
		
			/** Admin facing actions */
			add_action( 'admin_init', array( &$this, 'handle_api_request' ), 5 );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'get_ui' ) );
			
			require_once('aweber-count-shortcode.php');
			
			/** Non-admin facing actions */
			add_action( 'Count_form', array( &$this, 'Count_form' ) );
			add_action( 'Count_post', array( &$this, 'populate_list' ) );
			//add_filter( 'preprocess_Count', array( &$this, 'save_checkbox_state' ), 1 );
		
		}
		
		/**
		 * Set default options for the class.
		 *
		 * @since 1.0.0
		 */
		public function default_options() {
		
			$defaults = array(
				'auth_key' 				=> '',
				'auth_token' 			=> '',
				'req_key' 				=> '',
				'req_token' 			=> '',
				'oauth' 				=> '',
				'user_token' 			=> '',
				'user_token_secret' 	=> '',
				'user_id' 				=> '',
				'lists' 				=> array(),
				'current_list_id' 		=> '',
				'current_list_web_id' 	=> '',
				'current_list_name' 	=> '',
				'count' 				=> '',
				'show' 					=> 1,
				'inc_unsubscribed'		=> '',
				'cache_validity'		=> '',
				'check_text' 			=> 'Subscribe me to your mailing list',
				'subscribed_text' 		=> 'You are currently subscribed to our mailing list',
				'pending_text' 			=> 'Your subscription to our mailing list is pending - please check your email to confirm your subscription',
				'admin_text' 			=> 'You are the administrator - no need to subscribe you to the mailing list',
				'clear' 				=> 1,
				'check' 				=> 1
			);
			
			return $defaults;
		
		}

		/**
		 * Handles pinging of Aweber API to get user credentials
		 * for logging in and using their lists. Also handles the 
		 * logout process.
		 *
		 * Stores the data received from the Aweber API to our option.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 * @return null Redirect if successful
		 */
		public function handle_api_request() {
		
			global $tgm_aw_options;
			
			/** Handle the API key check and request */
			if ( isset( $_POST[sanitize_key( 'tgm_aw_action' )] ) && 'get_aw_auth_token' == $_POST[sanitize_key( 'tgm_aw_action' )] ) {
				check_admin_referer( 'tgm_aw_auth_ping' );
				
				if ( empty( $_POST[sanitize_key( 'tgm_aw_auth_token' )] ) ) {
					add_settings_error( 'tgm-aw-optin', 'no-data', __( 'You forgot to paste in your authorization token. Try again!', 'tgm-aw-optin' ), 'error' );
					return;
				}
				
				/** Ping the Aweber API and setup the OAuth structure */
				$auth_tokens = strip_tags( stripslashes( $_POST[sanitize_key( 'tgm_aw_auth_token' )] ) );
				list( $auth_key, $auth_token, $req_key, $req_token, $oauth ) = explode( '|', $auth_tokens );
				
				/** Connect to the Aweber API */
				$aweber = new AWeberAPI( $auth_key, $auth_token );
				$aweber->user->requestToken = $req_key;
				$aweber->user->tokenSecret = $req_token;
				$aweber->user->verifier = $oauth;
				
				$tgm_aw_options['auth_key'] = $auth_key;
				$tgm_aw_options['auth_token'] = $auth_token;
				$tgm_aw_options['req_key'] = $req_key;
				$tgm_aw_options['req_token'] = $req_token;
				$tgm_aw_options['oauth'] = $oauth;
				
				/** Set flags to determine whether or not to update options based on Aweber responses and store error string */
				$oauth_success = true;
				$account_success = true;
				$message = '';
				
				/** Attempt to get authorization tokens or catch the error and return it */
				try {
					list( $access_token, $access_token_secret ) = $aweber->getAccessToken();
				} catch ( AWeberException $e ) {
					/** Looks like there was an error getting OAuth tokens */
					$message .= sprintf( __( 'Sorry, but Aweber was unable to verify your authorization token. Aweber gave this response: <em>%s</em>. Please try entering your authorization token again. <br><br>', 'tgm-aw-optin' ), $e->getMessage() );
					$oauth_success = false; // Don't update auth tokens that return an error
				}
				
				if ( $oauth_success ) {
					$tgm_aw_options['user_token'] = $access_token;
					$tgm_aw_options['user_token_secret'] = $access_token_secret;
				}
				else {
					$tgm_aw_options['user_token'] = '';
					$tgm_aw_options['user_token_secret'] = '';
				}
				
				/** Get necessary data and store it into our options field */
				try {
					$user_account = $aweber->getAccount();
				} catch ( AWeberException $e ) {
					/** Looks like there was an error getting account information */
					$message .= sprintf( __( 'Sorry, but Aweber was unable to grant access to your account data. Aweber gave this response: <em>%s</em>. Please try entering your authorization token again.', 'tgm-aw-optin' ), $e->getMessage() );
					$account_success = false; // Don't update auth tokens that return an error
				}
				
				if ( $account_success )
					$tgm_aw_options['user_id'] = $user_account->data['id'];
				else
					$tgm_aw_options['user_id'] = '';
				
				/** Iterate through each list and get the important data */
				$i = 0;
				foreach ( $user_account->lists->data['entries'] as $list ) {
					$tgm_aw_options['lists'][$i]['id'] = $list['id'];
					$tgm_aw_options['lists'][$i]['name'] = $list['name'];
					$tgm_aw_options['lists'][$i]['real_subscribers']=(int)$list['total_subscribers'];
					$tgm_aw_options['lists'][$i]['total_subscribers']=(int)$list['total_subscribed_subscribers'];
					$tgm_aw_options['lists'][$i]['last_updated']=time();
					$i++;
				}
				
				if ( ! empty( $message ) )	
					add_settings_error( 'tgm-aw-optin', 'auth-fail', strip_tags( $message, '<br><em>' ), 'error' );
				
				/** Save all of our new data */
				update_option( $this->option, $tgm_aw_options );
				
				wp_redirect( add_query_arg( array( 'page' => 'tgm-aweber-count-settings' ), admin_url( 'options-general.php' ) ) );
				exit;
			}
			
			/** Handle the logout request */
			if ( isset( $_GET[sanitize_key( 'tgm_aw_action' )] ) && 'logout' == $_GET[sanitize_key( 'tgm_aw_action' )] ) {
				check_admin_referer( 'tgm_aw_logout', 'tgm_aw_logout_nonce' );
				
				/** Empty out the options and set them back to default */
				update_option( $this->option, $this->default_options() );
				
				wp_redirect( add_query_arg( array( 'page' => 'tgm-aweber-count-settings' ), admin_url( 'options-general.php' ) ) );
				exit;
			}
		
		}
		
		/**
		 * Register our plugin option group, name and sanitization method.
		 *
		 * @since 1.0.0
		 */
		public function admin_init() {
			
			register_setting( $this->option, $this->option, array( &$this, 'sanitize_options' ) );
		
		}
		
		/**
		 * Creates the plugin settings page.
		 *
		 * @since 1.0.0
		 */
		public function admin_menu() {
		
			add_options_page( __( 'Aweber Subscriber Count Settings', 'tgm-aw-optin' ), __( 'AW Sub Count', 'tgm-aw-optin' ), 'manage_options', 'tgm-aweber-count-settings', array( &$this, 'settings_page' ) );
		
		}
		
		/**
		 * Outputs the plugin settings page.
		 *
		 * @since 1.0.0
		 */
		public function settings_page() {
		
			global $tgm_aw_options;
			
			?>
			<div class="tgm-aw-settings wrap">
				<?php screen_icon( 'options-general' ); ?>
				<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
				
				<div class="tgm-aw-content">
					<?php if ( empty( $tgm_aw_options['user_token'] ) && empty( $tgm_aw_options['user_token_secret'] ) ) { ?>
						<form id="tgm-aw-login" action="" method="post">
							<input type="hidden" name="tgm_aw_action" value="get_aw_auth_token" />
							<?php $this->get_api_creds(); ?>
							<?php wp_nonce_field( 'tgm_aw_auth_ping' ); ?>
						</form>
					<?php } else { ?>
						<form id="tgm-aw-options" action="options.php" method="post">
							<?php
								/*echo "<pre>";
								print_r($tgm_aw_options);
								echo "</pre>";*/
							 settings_fields( $this->option ); ?>
							<?php $this->get_form_options(); ?>
						</form>
					<?php } ?>
				</div>
			</div>
			<?php
		
		}
		
		/**
		 * If the users API key is not set, we output this form in order to get it
		 * and move forward.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 */
		public function get_api_creds() {
		
			global $tgm_aw_options;
			
			?>
			<div class="tgm-aw-header">
				<div class="content">
					<p><?php _e( 'In order to allow the plugin to access your Aweber information, you need to grant it access. Once you have logged in and granted Aweber access, Aweber will give you an authorization token. Copy the authorization token into the field below and click the Login button to grant the plugin access to your Aweber account.', 'tgm-aw-optin' ); ?></p>
					<p><a onclick="window.open( this.href, '', 'resizable=yes,location=no,width=750,height=525,top=0,left=0' ); return false;" href="https://auth.aweber.com/1.0/oauth/authorize_app/0a7a1ce8" class="button-secondary get-auth"><?php _e( 'Click Here to Get Your Authorization Token', 'tgm-aw-optin' ); ?></a></p>
				</div>
			</div>
			<div class="tgm-aw-table">
				<div class="content">
					<table class="form-table">
						<tbody>
							<tr valign="middle">
								<th scope="row">
									<label><?php echo esc_attr__( 'Aweber Authorization Token', 'tgm-aw-optin' ); ?></label>
								</th>
								<td>
									<input id="tgm-aw-apikey" type="text" name="tgm_aw_auth_token" size="50" value="" />
								</td>
							</tr>
						</tbody>
					</table>
					<?php 
						submit_button(
							__( 'Login to Your Aweber Account', 'tgm-aw-optin' ), // Button text
							'secondary', // Button class
							'tgm_aw_authorize_app', // Input name
							true // Wrap in <p> tags
						);
					?>
				</div>
			</div>
			<?php
		
		}
		
		/**
		 * If the users API key is set, we can output all of our options
		 * for the plugin to be customized.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 */
		public function get_form_options() {
		
			global $tgm_aw_options;
			
			/** Quick check to make sure that our user has been verified by OAuth */
			if ( empty( $tgm_aw_options['user_token'] ) && empty( $tgm_aw_options['user_token_secret'] ) )
				return;
				
			?>
			<div class="tgm-aw-header authenticated">
				<div class="content">
					<?php printf( __( '<p>%1$s <strong>%2$s</strong> <a href="%3$s" class="tgm-aw-logout button-secondary">Logout</a></p>', 'tgm-aw-optin' ), 'You are currently logged in as Aweber ID: ', $tgm_aw_options['user_id'], add_query_arg( array( 'page' => 'tgm-aweber-count-settings', 'tgm_aw_action' => 'logout', 'tgm_aw_logout_nonce' => wp_create_nonce( 'tgm_aw_logout' ) ), admin_url( 'options-general.php' ) ) ); ?>
				</div>
			</div>
			<div class="tgm-aw-table">
				<div class="content">
					<table class="form-table">
						<tbody>
							<tr valign="top" >
								<th scope="row">
									<label><?php _e( 'How to use Aweber Subscriber Count', 'tgm-aw-optin' ); ?></label>
								</th>
								<td>
									<p><strong>Paste the shortcode with your list name in any post, page or text widget:</strong><br/>[displaycount list="your_list_name"]</p>

									<p><strong>You can combine lists using this shortcode :</strong><br/>[displaycount list="your_list_name_1,your_list_name_2"]</p>
									
									<p><strong>If you want to display a list count anywhere in your theme, use :</strong><br/>echo do_shortcode('[displaycount list="your_list_name"]');</p>
									
								</td>
							</tr>
							
							<tr valign="middle" >
								
							</tr>
							
                            <tr valign="middle" >
								<th scope="row">
									<label for="<?php echo $this->option; ?>[inc_unsubscribed]"><?php _e( 'Include Un-subscribed members in count?', 'tgm-aw-optin' ); ?></label>
								</th>
								<td>
									<input id="tgm-aw-inc_unsubscribed" type="checkbox" name="<?php echo $this->option; ?>[inc_unsubscribed]" value="<?php echo $tgm_aw_options['inc_unsubscribed']; ?>" <?php checked( $tgm_aw_options['inc_unsubscribed'], 1 ); ?> /> <?php _e( 'Tick in order to display your real subscriber count.<br/>Untick to display the Grand Total count.', 'tgm-aw-optin' ); ?>
								</td>
							</tr>
							<tr valign="middle" >
								<th scope="row">
									<label for="<?php echo $this->option; ?>[cache_validity]"><?php _e( 'Refresh cached count data validity period ', 'tgm-aw-optin' ); ?></label>
								</th>
								<td>
									<input id="tgm-aw-cache_validity" type="text" name="<?php echo $this->option; ?>[cache_validity]" value="<?php echo $tgm_aw_options['cache_validity']; ?>"  /> <?php _e( 'Enter number of seconds for which the cache is valid for particular list count.', 'tgm-aw-optin' ); ?>
								</td>
							</tr>
							<tr valign="middle" style="display:none;">
								<th scope="row">
									<label for="<?php echo $this->option; ?>[current_list_name]"><?php _e( 'Select the list that you want to show count', 'tgm-aw-optin' ); ?></label>
								</th>
								<td>
									<?php
										$values = array();
										
										foreach ( $tgm_aw_options['lists'] as $lists )
											$values[] = implode( ',', $lists );
											
										echo '<select id="tgm-aw-lists" name="' . $this->option . '[current_list_name]">';
											echo '<option value=",,,"></option>';
											foreach ( $values as $set ) {
												$data = explode( ',', $set );
												$selected = ( $data[1] == $tgm_aw_options['current_list_name'] ) ? 'selected="selected"' : '';
												echo '<option value="' . $set . '"' . $selected . '>' . $data[1] . '</option>';
											}
										echo '</select>';
									?>
								</td>
							</tr>
							
						</tbody>
					</table>
					<?php 
						submit_button(
							__( 'Save Changes', 'tgm-aw-optin' ), // Button text
							'secondary', // Button class
							'tgm_aw_save_options', // Input name
							true // Wrap in <p> tags
						);
					?>
					<div id="asc_credits"><?php _e( 'Thanks for using my plugin, I hope it helps you as much as me :)<br/>If you\'re looking for great WP Themes, check out my blog : http://wp-themes-pro.com'); ?></div>
				</div>
				
			</div>
			<?php 
		
		}
		 
		/**  
		 * Enqueue styles for the plugin page (including thickbox elements).
		 *
		 * @since 1.0.0
		 *
		 * @global object $current_screen Data associated with the current page
		 */
		public function get_ui() {
		
			global $current_screen;
			
			if ( 'settings_page_tgm-aweber-count-settings' == $current_screen->id ) {
				wp_register_style( 'tgm-aw-style', plugin_dir_url( __FILE__ ) . 'lib/css/admin.css', array(), '1.0.0' );
				wp_enqueue_style( 'tgm-aw-style' );
				add_thickbox();
			}
		
		}
		
		/**
		 * Sanitizes $_POST inputs from the user before being stored 
		 * in the database.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 * @param array $input The array of $_POST inputs
		 * @return array $tgm_aw_options Amended array of plugin options
		 */
		public function sanitize_options( $input ) {
			
			global $tgm_aw_options;
			
			$current_list_data = explode( ',', $input['current_list_name'] );
			$tgm_aw_options['current_list_id'] = esc_attr( $current_list_data[0] );
			$tgm_aw_options['current_list_name'] = esc_attr( $current_list_data[1] );
			
			$tgm_aw_options['show'] = isset( $input['show'] ) ? (int) 1 : (int) 0;
			$tgm_aw_options['inc_unsubscribed'] = isset( $input['inc_unsubscribed'] ) ? (int) 1 : (int) 0;
			$tgm_aw_options['cache_validity'] = isset( $input['cache_validity'] ) ? (int) esc_attr( strip_tags( $input['cache_validity'] ) ) : (int) 7200;
			$tgm_aw_options['check_text'] = esc_attr( strip_tags( $input['check_text'] ) );
			$tgm_aw_options['subscribed_text'] = esc_attr( strip_tags( $input['subscribed_text'] ) );
			$tgm_aw_options['pending_text'] = esc_attr( strip_tags( $input['pending_text'] ) );
			$tgm_aw_options['admin_text'] = esc_attr( strip_tags( $input['admin_text'] ) );
			
			$tgm_aw_options['clear'] = isset( $input['clear'] ) ? (int) 1 : (int) 0;
			$tgm_aw_options['check'] = isset( $input['check'] ) ? (int) 1 : (int) 0;
			
			return $tgm_aw_options;
		
		}
		
		/**
		 * Outputs the checkbox area below the count form that allows
		 * Counters to subscribe to the email list.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 * @return null Return early if in the admin or the email list hasn't been set
		 */
		public function Count_form() {
		
			global $tgm_aw_options;
			
			/** Don't do anything if we are in the admin */
			if ( is_admin() )
				return;
			
			/** Don't do anything if the user has turned off the feature */
			if ( ! $tgm_aw_options['show'] )
				return;
			
			/** Don't do anything unless the user has already logged in and selected a list */
			if ( empty( $tgm_aw_options['current_list_id'] ) )
				return;
			
			$clear = $tgm_aw_options['clear'] ? 'style="clear: both;"' : '';
			$checked_status = ( ! empty( $_COOKIE['tgm_aw_checkbox_' . COOKIEHASH] ) && 'checked' == $_COOKIE['tgm_aw_checkbox_' . COOKIEHASH] ) ? true : false;
			$checked = $checked_status ? 'checked="checked"' : '';
			$status = $this->get_viewer_status();
			
			if ( 'admin' == $status ) {
				echo '<p class="tgm-aw-subscribe" ' . $clear . '>' . $tgm_aw_options['admin_text'] . '</p>';
			}
			elseif ( 'subscribed' == $status ) {
				echo '<p class="tgm-aw-subscribe" ' . $clear . '>' . $tgm_aw_options['subscribed_text'] . '</p>';
			}
			elseif ( 'pending' == $status ) {
				echo '<p class="tgm-aw-subscribe" ' . $clear . '>' . $tgm_aw_options['pending_text'] . '</p>';
			}
			else {
				echo '<p class="tgm-aw-subscribe" ' . $clear . '>';
					echo '<input type="checkbox" name="tgm_aw_get_subscribed" id="tgm-aw-get-subscribed" value="' . esc_attr( $tgm_aw_options['check_text'] ) . '" style="width: auto;" ' . $checked . ' />';
					echo '<label for="tgm_aw_get_subscribed"> ' . $tgm_aw_options['check_text'] . '</label>';
				echo '</p>';
			}	
		
		}
		
		/**
		 * Gets and returns the current viewer's name.
		 *
		 * @since 1.0.0
		 *
		 * @return string|boolean Counter name on success, false on failure
		 */
		public function get_viewer_name() {
			
			global $tgm_aw_Count_data;
			
			/** Grab the current user's info if available */
			get_currentuserinfo();
			
			/** Get the Counter email from cookies if available */
			if ( ! empty( $tgm_aw_Count_data['Count_author_name'] ) )
				$Counter_name = $tgm_aw_Count_data['Count_author_name'];
			elseif ( ! empty( $_COOKIE[sanitize_key( 'Count_author_' . COOKIEHASH )] ) )
				$Counter_name = trim( $_COOKIE[sanitize_key( 'Count_author_' . COOKIEHASH )] );
			
			if ( empty( $Counter_name ) )
				return false;
			
			return $Counter_name;
		
		}
		
		/**
		 * Gets and returns the current viewer's email.
		 *
		 * This is a shortened version of the get_viewer_status() method.
		 *
		 * @since 1.0.0
		 *
		 * @global int $post The current post ID
		 * @global string $user_email The email of the current user if logged in
		 * @global array $tgm_aw_options Array of plugin options
		 * @return string|boolean Email string on success, false on failure
		 */
		public function get_viewer_email() {
		
			global $post, $user_email, $tgm_aw_options, $tgm_aw_Count_data;
			
			/** Grab the current user's info if available */
			get_currentuserinfo();
			
			/** Get the Counter email from cookies if available */
			if ( ! empty( $tgm_aw_Count_data['Count_author_email'] ) )
				$Counter_email = $tgm_aw_Count_data['Count_author_email'];
			elseif ( ! empty( $_COOKIE[sanitize_key( 'Count_author_email_' . COOKIEHASH )] ) )
				$Counter_email = trim( $_COOKIE[sanitize_key( 'Count_author_email_' . COOKIEHASH )] );
			
			if ( is_email( $user_email ) )
				$email = strtolower( $user_email );
			elseif ( is_email( $Counter_email ) )
				$email = strtolower( $Counter_email );
			else
				return false;
			
			return $email;
		
		}
		
		/**
		 * Determines the current state of a Counter. Sets whether or not he/she is
		 * subscribed or not, an admin or the author of the post.
		 *
		 * Modified from the 'Subscribe To Counts' plugin by Mark Jaquith.
		 *
		 * @since 1.0.0
		 *
		 * @global int $post The current post ID
		 * @global string $user_email The email of the current user if logged in
		 * @global array $tgm_aw_options Array of plugin options
		 * @return string|boolean Admin, pending or subscribed string on success, false on failure
		 */
		public function get_viewer_status() {
		
			global $post, $user_email, $tgm_aw_options;
			
			/** Grab the current user's info if available */
			get_currentuserinfo();
			
			/** Get the Counter email from cookies if available */
			$Counter_email = isset( $_COOKIE[sanitize_key( 'Count_author_email_' . COOKIEHASH )] ) ? trim( $_COOKIE[sanitize_key( 'Count_author_email_' . COOKIEHASH )] ) : '';
			$loggedin = false;
			
			if ( is_email( $user_email ) ) {
				$email = strtolower( $user_email );
				$loggedin = true;
			}
			elseif ( is_email( $Counter_email ) ) {
				$email = strtolower( $Counter_email );
			}
			else {
				return false;
			}
				
			$author = get_userdata( $post->post_author );
			
			if ( $email == strtolower( $author->user_email ) && $loggedin )
				return 'admin';
			
			/** Return early if the user has selected to skip checking for previous subscribers */
			if ( ! $tgm_aw_options['check'] )
				return false;
				
			/** Before we can connect to the API, we need to make sure all of our data is set */
			if ( ! ( empty( $tgm_aw_options['auth_key'] ) && empty( $tgm_aw_options['auth_token'] ) && empty( $tgm_aw_options['req_key'] ) && empty( $tgm_aw_options['req_token'] ) && empty( $tgm_aw_options['oauth'] ) && empty( $tgm_aw_options['user_token'] ) && empty( $tgm_aw_options['user_token_secret'] ) ) ) {
				/** Everything is set in our options, so let's move forward */
				//$aweber = new AWeberAPI( $tgm_aw_options['auth_key'], $tgm_aw_options['auth_token'] );
				$aweber = new AWeberAPI( 'AkwhFoeSxkmzkYfzrK1RDnn3', 'ESCn5qqaILtTc1m6TanQgUYk8hRWo8PiMlHm83vd' );
				$aweber->user->requestToken = $tgm_aw_options['req_key'];
				$aweber->user->tokenSecret = $tgm_aw_options['req_token'];
				$aweber->user->verifier = $tgm_aw_options['oauth'];
			
				/** Attempt to get account information, return false if there is an error */
				try {			
					$user_account = $aweber->getAccount( $tgm_aw_options['user_token'], $tgm_aw_options['user_token_secret'] );
				} catch ( AWeberException $e ) {
					return false;
				}
				
				/** Attempt to load subscriber information from the chosen list, return false if there is an error */
				try {
					$subscribers = $aweber->loadFromUrl( '/accounts/' . urlencode( $tgm_aw_options['user_id'] ) . '/lists/' . urlencode( $tgm_aw_options['current_list_id'] ) . '/subscribers' );
				} catch ( AWeberException $e ) {
					return false;
				}
				
				/** Attempt to find the email address in the list, return false if there is an error */
				try {
					$check_for_email = $subscribers->find( array( 'email' => $email ) );
				} catch ( AWeberException $e ) {
					return false;
				}
				
				/** Determine email address state */
				if ( 0 == $check_for_email->total_size )
					return false;
				elseif ( 'unconfirmed' == $check_for_email->data['entries'][0]['status'] )
					return 'pending';
				else
					return 'subscribed';
			}
				
			return false;
		
		}
		
		/**
		 * Sends the email and (optionally) first name of the Counter to the 
		 * current Aweber list.
		 *
		 * @since 1.0.0
		 *
		 * @global array $tgm_aw_options Array of plugin options
		 * @global array $tgm_aw_Count_data Array of submitted count data
		 * @return null Return early if on admin
		 */
		public function populate_list() {
		
			global $tgm_aw_options, $tgm_aw_Count_data;
				
			/** Only go forward if the checkbox has been selected and the user isn't subscribed */
			if ( ! empty( $tgm_aw_Count_data['tgm_aw_subscribe'] ) && 'yes' == $tgm_aw_Count_data['tgm_aw_subscribe'] || ! empty( $_COOKIE['tgm_aw_checkbox_' . COOKIEHASH] ) && 'checked' == $_COOKIE['tgm_aw_checkbox_' . COOKIEHASH] ) {
				/** Before we can connect to the API, we need to make sure all of our data is set */
				if ( ! ( empty( $tgm_aw_options['auth_key'] ) && empty( $tgm_aw_options['auth_token'] ) && empty( $tgm_aw_options['req_key'] ) && empty( $tgm_aw_options['req_token'] ) && empty( $tgm_aw_options['oauth'] ) && empty( $tgm_aw_options['user_token'] ) && empty( $tgm_aw_options['user_token_secret'] ) ) ) {
					/** Everything is set in our options, so let's move forward */
					$aweber = new AWeberAPI( $tgm_aw_options['auth_key'], $tgm_aw_options['auth_token'] );
					$aweber->user->requestToken = $tgm_aw_options['req_key'];
					$aweber->user->tokenSecret = $tgm_aw_options['req_token'];
					$aweber->user->verifier = $tgm_aw_options['oauth'];
				
					/** Attempt to get account information, return false if there is an error */
					try {			
						$user_account = $aweber->getAccount( $tgm_aw_options['user_token'], $tgm_aw_options['user_token_secret'] );
					} catch ( AWeberException $e ) {
						return false;
					}
					
					/** Attempt to load subscriber information from the chosen list, return false if there is an error */
					try {
						$subscribers = $aweber->loadFromUrl( '/accounts/' . urlencode( $tgm_aw_options['user_id'] ) . '/lists/' . urlencode( $tgm_aw_options['current_list_id'] ) . '/subscribers' );
					} catch ( AWeberException $e ) {
						return false;
					}
					
					/** Gather data to send to Aweber */
					$name = $this->get_viewer_name();
					$email = $this->get_viewer_email();
				
					/** Create a new subscriber */
					try {
						$create = $subscribers->create( array( 'email' => $email, 'name' => $name ) );
					} catch ( AWeberException $e ) {
						return false;
					}
				}
			}
			
			return false;
		
		}
		
		/**
		 * Sets a cookie to determine the current state of the checkbox for the 
		 * email list.
		 *
		 * @since 1.0.0
		 * 
		 * @global array $tgm_aw_Count_data Matches submitted count data
		 * @param array $Countdata Submitted count data from the user
		 * @return array $Countdata Amended count data
		 */
		public function save_checkbox_state( $Countdata ) {
		
			global $tgm_aw_Count_data;
			
			/** Set the global variable equal to the current count information */
			$tgm_aw_Count_data = (array) $Countdata;
			
			/** If our checkbox has been checked, set a cookie with the value of 'checked', else 'unchecked' */
			if ( isset( $_POST[sanitize_key( 'tgm_aw_get_subscribed' )] ) ) {
				$tgm_aw_Count_data['tgm_aw_subscribe'] = 'yes';
				setcookie( 'tgm_aw_checkbox_' . COOKIEHASH, 'checked', time() + 30000000, COOKIEPATH );
			}
			else {
				$tgm_aw_Count_data['tgm_aw_subscribe'] = 'no';
				setcookie( 'tgm_aw_checkbox_' . COOKIEHASH, 'unchecked', time() + 30000000, COOKIEPATH );
			}

			/** Return our amended array of args */
			return $tgm_aw_Count_data;
		
		}
	
	}
}

/** Instantiate the class */
$tgm_aw_Count_optin = new TGM_Aweber_Count_Optin;
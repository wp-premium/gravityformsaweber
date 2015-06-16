<?php

GFForms::include_feed_addon_framework();

class GFAWeber extends GFFeedAddOn {

	protected $_version = GF_AWEBER_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
	protected $_slug = 'gravityformsaweber';
	protected $_path = 'gravityformsaweber/aweber.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'AWeber Add-On';
	protected $_short_title = 'AWeber';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_aweber', 'gravityforms_aweber_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_aweber';
	protected $_capabilities_form_settings = 'gravityforms_aweber';
	protected $_capabilities_uninstall = 'gravityforms_aweber_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFAWeber();
		}

		return self::$_instance;
	}


	public function init() {

		parent::init();

		$this->add_delayed_payment_support( array(
				'option_label' => __( 'Subscribe user to AWeber only when payment is received.', 'gravityformsaweber' )
			) );

	}

	public function init_admin(){
		parent::init_admin();

		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	//------- AJAX FUNCTIONS ------------------//

	public function init_ajax(){
		parent::init_ajax();

		add_action( 'wp_ajax_gf_dismiss_aweber_menu', array( $this, 'ajax_dismiss_menu' ) );

	}

	public function maybe_create_menu( $menus ){
		$current_user = wp_get_current_user();
		$dismiss_aweber_menu = get_metadata( 'user', $current_user->ID, 'dismiss_aweber_menu', true );
		if ( $dismiss_aweber_menu != '1' ){
			$menus[] = array( 'name' => $this->_slug, 'label' => $this->get_short_title(), 'callback' => array( $this, 'temporary_plugin_page' ), 'permission' => $this->_capabilities_form_settings );
		}

		return $menus;
	}

	public function ajax_dismiss_menu(){

		$current_user = wp_get_current_user();
		update_metadata( 'user', $current_user->ID, 'dismiss_aweber_menu', '1' );
	}

	public function temporary_plugin_page(){
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
			function dismissMenu(){
				jQuery('#gf_spinner').show();
				jQuery.post(ajaxurl, {
						action : "gf_dismiss_aweber_menu"
					},
					function (response) {
						document.location.href='?page=gf_edit_forms';
						jQuery('#gf_spinner').hide();
					}
				);

			}
		</script>

		<div class="wrap about-wrap">
			<h1><?php _e( 'AWeber Add-On v2.0', 'gravityformsaweber' ) ?></h1>
			<div class="about-text"><?php _e( 'Thank you for updating! The new version of the Gravity Forms AWeber Add-On makes changes to how you manage your AWeber integration.', 'gravityformsaweber' ) ?></div>
			<div class="changelog">
				<hr/>
				<div class="feature-section col two-col">
					<div class="col-1">
						<h3><?php _e( 'Manage AWeber Contextually', 'gravityformsaweber' ) ?></h3>
						<p><?php _e( 'AWeber Feeds are now accessed via the AWeber sub-menu within the Form Settings for the Form with which you would like to integrate AWeber.', 'gravityformsaweber' ) ?></p>
					</div>
					<div class="col-2 last-feature">
						<img src="http://gravityforms.s3.amazonaws.com/webimages/AddonNotice/NewAWeber2.png">
					</div>
				</div>

				<hr/>

				<form method="post" id="dismiss_menu_form" style="margin-top: 20px;">
					<input type="checkbox" name="dismiss_aweber_menu" value="1" onclick="dismissMenu();"> <label><?php _e( 'I understand this change, dismiss this message!', 'gravityformsaweber' ) ?></label>
					<img id="gf_spinner" src="<?php echo GFCommon::get_base_url() . '/images/spinner.gif'?>" alt="<?php _e( 'Please wait...', 'gravityformsaweber' ) ?>" style="display:none;"/>
				</form>

			</div>
		</div>
	<?php
	}

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'AWeber Account Information', 'gravityformsaweber' ),
				'description' => sprintf( __( 'AWeber is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add them to your client\'s AWeber subscription list. If you don\'t have a AWeber account, you can %1$ssign up for one here%2$s', 'gravityformsaweber' ),
						'<a href="http://www.aweber.com" target="_blank">', '</a>.' )
					. '<br/><br/>' .
					sprintf( __( '%1$sClick here to retrieve your Authorization code%2$s', 'gravityformsaweber' ),
						'<a onclick="window.open(this.href,\'\',\'resizable=yes,location=no,width=750,height=525,status\'); return false" href="https://auth.aweber.com/1.0/oauth/authorize_app/2ad0d7d5">', '</a>.' )
					. '<br/>' .
					__( 'You will need to log in to your AWeber account. Upon a successful login, a string will be returned. Copy the whole string and paste into the text box below.', 'gravityformsaweber' ),
				'fields'      => array(
					array(
						'name'              => 'authorizationCode',
						'label'             => __( 'Authorization Code', 'gravityformsaweber' ),
						'type'              => 'authorization_code',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_key' )

					),
				)
			),
		);

	}

	public function settings_authorization_code( $field, $echo = true ) {

		$authorization_code_field = $this->settings_text( $field, false );

		$caption = __( 'You can find your unique Authorization code by clicking on the link above and login into your AWeber account.', 'gravityformsaweber' );

		if ( $echo ) {
			echo $authorization_code_field . '</br><small>' . $caption . '</small>';
		}

		return $authorization_code_field . '</br><small>' . $caption . '</small>';

	}

	// ------- Plugin list page -------
	public function feed_list_columns() {
		return array(
			'feedName'		=> __( 'Name', 'gravityformsaweber' ),
			'account'		=> __( 'AWeber Account', 'gravityformsaweber' ),
			'contactList'	=> __( 'AWeber List', 'gravityformsaweber' )
		);
	}

	public function get_column_value_contactList( $feed ) {
		return $this->get_list_name( $feed['meta']['account'], $feed['meta']['contactList'] );
	}

	private function get_list_name( $account_id, $list_id ) {
		global $_lists;

		if ( ! isset( $_lists ) ) {

			$aweber = $this->get_aweber_object();
			if ( ! $aweber ) {
				return '';
			}
			$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );

			$_lists = $account->lists;
		}

		$list_name_array = wp_filter_object_list( $_lists->data['entries'], array( 'id' => $list_id ), 'and', 'name' );
		if ( $list_name_array ) {
			$list_names = array_values( $list_name_array );
			$list_name  = $list_names[0];
		} else {
			$list_name = $list_id . ' (' . __( 'List not found in AWeber', 'gravityformsaweber' ) . ')';
		}

		return $list_name;
	}

	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( ! $this->is_valid_key() ) {
			?>
			<div><?php echo sprintf( __( 'We are unable to login to AWeber with the provided Authorization code. Please make sure you have entered a valid Authorization code in the %sSettings Page%s', 'gravityformsaweber' ),
					"<a href='" . esc_url( $this->get_plugin_settings_url() ) . "'>", '</a>' ); ?>
			</div>

			<?php
			return;
		}

		parent::feed_edit_page( $form, $feed_id );
	}

	public function feed_settings_fields() {
		return array(
			array(
				'title'       => __( 'AWeber Feed', 'gravityformsaweber' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformsaweber' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformsaweber' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsaweber' ),
					),
					array(
						'name'     => 'account',
						'label'    => __( 'Account', 'gravityformsaweber' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'hidden'   => $this->is_accounts_hidden(),
						'choices'  => $this->get_aweber_accounts(),
						'tooltip'  => '<h6>' . __( 'Account', 'gravityformsaweber' ) . '</h6>' . __( 'Select the AWeber account you would like to add your contacts to.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'contactList',
						'label'      => __( 'Contact List', 'gravityformsaweber' ),
						'type'       => 'contact_list',
						'onchange'   => 'jQuery(this).parents("form").submit();',
						'dependency' => array( $this, 'has_selected_account' ),
						'tooltip'    => '<h6>' . __( 'Contact List', 'gravityformsaweber' ) . '</h6>' . __( 'Select the AWeber list you would like to add your contacts to.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'listFields',
						'label'      => __( 'Map Fields', 'gravityformsaweber' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'	 => $this->create_list_field_map(),
						'tooltip'    => '<h6>' . __( 'Map Fields', 'gravityformsaweber' ) . '</h6>' . __( 'Associate your AWeber fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'optin',
						'label'      => __( 'Opt In', 'gravityformsaweber' ),
						'type'       => 'feed_condition',
						'dependency' => 'contactList',
						'tooltip'    => '<h6>' . __( 'Opt-In Condition', 'gravityformsaweber' ) . '</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to AWeber when the condition is met. When disabled all form submissions will be exported.', 'gravityformsaweber' ),
					),

				)
			),
		);

	}

	public function is_accounts_hidden() {
		if ( $this->has_multiple_accounts() ) {
			return false;
		}

		return true;
	}

	public function has_multiple_accounts() {
		$accounts = $this->get_accounts();
		if ( ! $accounts || $accounts->data['total_size'] == 1 ) {
			return false;
		}

		return true;
	}

	public function has_selected_account() {

		if ( $this->has_multiple_accounts() ) {
			$selected_account = $this->get_setting( 'account' );

			return ! empty( $selected_account );
		}

		return true;
	}

	private function get_accounts() {
		$accounts = GFCache::get( 'aweber_accounts' );
		if ( ! $accounts ) {
			$aweber = $this->get_aweber_object();
			$this->log_debug( __METHOD__ . '(): Getting account list.' );
			$accounts = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts' );
			GFCache::set( 'aweber_accounts', $accounts );
		}

		return $accounts;

	}

	public function settings_contact_list( $field, $echo = true ) {

		$account_id = $this->get_setting( 'account' );
		if ( empty( $account_id ) ) {
			$accounts   = $this->get_accounts();
			$account_id = $accounts->data['entries'][0]['id'];
		}

		$aweber = $this->get_aweber_object();

		$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );

		if ( ! $account ) {
			return;
		}

		$lists[] = array(
			'label' => 'Select List',
			'value' => '',
		);

		foreach ( $account->lists as $list ) {

			$lists[] = array(
				'label' => $list->name,
				'value' => $list->id,
			);

		}

		$field['type']    = 'select';
		$field['choices'] = $lists;

		$html = $this->settings_select( $field, false );

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function get_aweber_accounts() {

		$aweber_accounts = $this->get_accounts();

		if ( ! $aweber_accounts ) {
			return;
		}

		if ( $this->has_multiple_accounts() ) {
			$accounts_dropdown[] = array(
				'label' => 'Select Account',
				'value' => '',
			);

		}

		foreach ( $aweber_accounts as $account ) {

			$accounts_dropdown[] = array(
				'label' => $account->id,
				'value' => $account->id,
			);

		}

		return $accounts_dropdown;

	}

	public function create_list_field_map() {

		$list_id    = $this->get_setting( 'contactList' );
		if ( empty( $list_id ) ){
			return array();
		}

		$account_id = $this->get_setting( 'account' );

		if ( empty( $account_id ) ) {
			$accounts   = $this->get_accounts();
			$account_id = $accounts->data['entries'][0]['id'];
		}

		$custom_fields = $this->get_custom_fields( $list_id, $account_id );

		return $custom_fields;

	}

	public function update_plugin_settings( $settings ) {

		$saved_settings             = $this->get_plugin_settings();
		$authorization_is_different = $saved_settings['authorizationCode'] != $settings['authorizationCode'];

		if ( $authorization_is_different ) {
			$aweber_token                    = $this->get_aweber_tokens( $settings['authorizationCode'] );
			$settings['access_token']        = $aweber_token['access_token'];
			$settings['access_token_secret'] = $aweber_token['access_token_secret'];
		} else {
			$settings['access_token']        = $saved_settings['access_token'];
			$settings['access_token_secret'] = $saved_settings['access_token_secret'];
		}

		parent::update_plugin_settings( $settings );
	}

	// used to upgrade old feeds into new version
	public function upgrade( $previous_version ) {
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_aweber_version' );
		}
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		if ( $previous_is_pre_addon_framework ) {

			$old_feeds = $this->get_old_feeds();

			if ( $old_feeds ) {
				$counter = 1;
				foreach ( $old_feeds as $old_feed ) {
					$feed_name  = 'Feed ' . $counter;
					$form_id   = $old_feed['form_id'];
					$is_active = $old_feed['is_active'];

					$new_meta = array(
						'feedName'    => $feed_name,
						'account'     => rgar( $old_feed['meta'], 'client_id' ),
						'contactList' => rgar( $old_feed['meta'], 'contact_list_id' )
					);

					foreach ( $old_feed['meta']['field_map'] as $var_tag => $field_id ) {
						$new_meta[ 'listFields_' . $var_tag ] = $field_id;
					}

					$optin_enabled = rgar( $old_feed['meta'], 'optin_enabled' );
					if ( $optin_enabled ) {
						$new_meta['feed_condition_conditional_logic']        = 1;
						$new_meta['feed_condition_conditional_logic_object'] = array(
							'conditionalLogic' => array(
								'actionType' => 'show',
								'logicType'  => 'all',
								'rules'      => array(
									array(
										'fieldId'  => $old_feed['meta']['optin_field_id'],
										'operator' => $old_feed['meta']['optin_operator'],
										'value'    => $old_feed['meta']['optin_value'],
									),
								)
							)
						);
					} else {
						$new_meta['feed_condition_conditional_logic'] = 0;
					}

					$this->insert_feed( $form_id, $is_active, $new_meta );
					$counter ++;

				}

				$old_settings = get_option( 'gf_aweber_settings' );

				$new_settings = array(
					'authorizationCode'   => $old_settings['api_credentials'],
					'access_token'        => $old_settings['access_token'],
					'access_token_secret' => $old_settings['access_token_secret'],
				);

				parent::update_plugin_settings( $new_settings );

				//set paypal delay setting
				$this->update_paypal_delay_settings( 'delay_aweber_subscription' );
			}
		}

		return;
	}

	public function update_paypal_delay_settings( $old_delay_setting_name ){
		global $wpdb;
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		$new_delay_setting_name = 'delay_' . $this->_slug;

		//get paypal feeds from old table
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		//loop through feeds and look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard
		if ( ! empty( $paypal_feeds_old ) ){
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds_old as $old_feed ) {
				$meta = $old_feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ){
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					//update paypal meta to have new setting
					$meta = maybe_serialize( $meta );
					$wpdb->update("{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array('%s'), array('%d') );
				}
			}
		}

		//get paypal feeds from new framework table
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );
		if ( ! empty( $paypal_feeds ) ){
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds as $feed ) {
				$meta = $feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ){
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					$this->update_feed_meta( $feed['id'], $meta );
				}
			}
		}
	}

	public function get_old_paypal_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_paypal';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = GFFormsModel::get_form_table_name();
		$sql     = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		$this->log_debug( __METHOD__ . "(): getting old paypal feeds: {$sql}" );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		$count = sizeof( $results );

		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	public function get_old_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_aweber';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = RGFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM $table_name s
				INNER JOIN $form_table_name f ON s.form_id = f.id";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$count = sizeof( $results );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	public function get_custom_fields( $list_id, $account_id ) {

		$aweber = $this->get_aweber_object();

		$custom_fields = array(
			array( 'label' => 'Email Address', 'name' => 'email', 'required' => true, ),
			array( 'label' => 'Full Name', 'name' => 'fullname' ),
		);


		$aweber_custom_fields = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id . '/lists/' . $list_id . '/custom_fields' );

		foreach ( $aweber_custom_fields as $cf ) {
			$custom_fields[] = array( 'label' => $cf->data['name'], 'name' => 'cf_' . $cf->data['id'] );
		}

		return $custom_fields;

	}

	public function get_aweber_tokens( $api_credentials ) {

		list( $application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier ) = rgexplode( '|', $api_credentials, 5 );
		$this->include_api();

		$this->log_debug( __METHOD__ . "(): Getting tokens for key {$application_key}" );
		$aweber                     = new AWeberAPI( $application_key, $application_secret );
		$aweber->user->tokenSecret  = $request_token_secret;
		$aweber->user->requestToken = $request_token;
		$aweber->user->verifier     = $oauth_verifier;

		$access_token        = '';
		$access_token_secret = '';
		try {
			$this->log_debug( __METHOD__ . '(): Getting tokens.' );
			list( $access_token, $access_token_secret ) = $aweber->getAccessToken();
		} catch ( AWeberException $e ) {
			$this->log_error( __METHOD__ . "(): Unable to retrieve tokens: {$e}" );
		}

		return array( 'access_token' => $access_token, 'access_token_secret' => $access_token_secret );

	}

	public function is_valid_key() {

		$settings        = $this->get_plugin_settings();
		$api_credentials = $settings['authorizationCode'];
		if ( empty( $api_credentials ) ) {
			return null;
		}

		$aweber              = $this->get_aweber_object();
		$access_token        = $this->get_access_token();
		$access_token_secret = $this->get_access_token_secret();

		try {
			$this->log_debug( __METHOD__ . '(): Validating API credentials.' );
			$account = $aweber->getAccount( $access_token, $access_token_secret );
		} catch ( AWeberException $e ) {
			$this->log_error( __METHOD__ . "(): Unable to validate API credentials: {$e}" );
			$account = null;

		}

		if ( $account ) {
			$this->log_debug( __METHOD__ . '(): Credentials validated.' );

			return true;
		} else {
			return false;
		}

	}

	public function get_aweber_object() {
		$this->include_api();
		$tokens = $this->get_api_tokens();
		if ( empty( $tokens['application_key'] ) && empty( $tokens['application_secret'] ) && empty( $tokens['request_token'] ) && empty( $tokens['oauth_verifier'] ) ) {
			return false;
		}
		$aweber                     = new AWeberAPI( $tokens['application_key'], $tokens['application_secret'] );
		$aweber->user->requestToken = $tokens['request_token'];
		$aweber->user->verifier     = $tokens['oauth_verifier'];
		$aweber->user->accessToken  = $this->get_access_token();
		$aweber->user->tokenSecret  = $this->get_access_token_secret();

		return $aweber;
	}

	public function get_api_tokens() {
		$settings        = $this->get_plugin_settings();
		$api_credentials = $settings['authorizationCode'];
		list( $application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier ) = rgexplode( '|', $api_credentials, 5 );
		$api_tokens = array(
			'application_key'      => $application_key,
			'application_secret'   => $application_secret,
			'request_token'        => $request_token,
			'request_token_secret' => $request_token_secret,
			'oauth_verifier'       => $oauth_verifier,
		);

		return $api_tokens;
	}

	public function get_access_token() {
		$settings     = $this->get_plugin_settings();
		$access_token = $settings['access_token'];

		return $access_token;
	}

	public function get_access_token_secret() {
		$settings            = $this->get_plugin_settings();
		$access_token_secret = $settings['access_token_secret'];

		return $access_token_secret;
	}

	public function include_api() {

		if ( ! class_exists( 'AWeberServiceProvider' ) ) {
			require_once $this->get_base_path() . '/api/aweber_api.php';
		}

	}

	public function process_feed( $feed, $entry, $form ) {
		if ( ! $this->is_valid_key() ) {
			return;
		}

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {

		$email = $entry[ $feed['meta']['listFields_email'] ];
		$name  = '';
		if ( ! empty( $feed['meta']['listFields_fullname'] ) ) {
			$name = $this->get_name( $entry, $feed['meta']['listFields_fullname'] );
		}

		$account_id = $feed['meta']['account'];
		$list_id    = $feed['meta']['contactList'];

		$aweber = $this->get_aweber_object();
		$this->log_debug( __METHOD__ . '(): Getting account lists.' );
		$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );
		$this->log_debug( __METHOD__ . "(): Getting list for account {$account_id} with id {$list_id}" );
		$list = $account->loadFromUrl( "/accounts/{$account_id}/lists/{$list_id}" );

		$merge_vars = array( '' );
		$field_maps = $this->get_field_map_fields( $feed, 'listFields' );
		foreach ( $field_maps as $var_tag => $field_id ) {

			$field = RGFormsModel::get_field( $form, $field_id );
			if ( $field_id == intval( $field_id ) && RGFormsModel::get_input_type( $field ) == 'address' ) {
				$merge_vars[ $var_tag ] = $this->get_address( $entry, $field_id ); //handling full address
			} else if ( $field_id == intval( $field_id ) && RGFormsModel::get_input_type( $field ) == 'name' ) {
				$merge_vars[ $var_tag ] = $this->get_name( $entry, $field_id ); //handling full name
			} else if ( $var_tag != 'email' && $var_tag != 'fullname' ) {
				//ignoring email and full name fields as it will be handled separately
				$merge_vars[ $var_tag ] = apply_filters( 'gform_aweber_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			}
		}

		$custom_fields = $this->get_custom_fields( $list_id, $account_id );
		// removing email and full name from list of custom fields as they are handled separately
		unset( $custom_fields[0] );
		unset( $custom_fields[1] );
		$custom_fields = array_values( $custom_fields );

		$list_custom_fields = array();
		foreach ( $custom_fields as $cf ) {
			$key                                = $cf['name'];
			$list_custom_fields[ $cf['label'] ] = (string) $merge_vars[ $key ];
		}

		$params = array(
			'email'       => $email,
			'name'        => $name,
			'ad_tracking' => apply_filters( "gform_aweber_ad_tracking_{$form['id']}", apply_filters( 'gform_aweber_ad_tracking', $form['title'], $entry, $form, $feed ), $entry, $form, $feed )
		);

		if ( ! empty( $list_custom_fields ) ) {
			$params['custom_fields'] = $list_custom_fields;
		}

		//ad tracking has a max size of 20 characters
		if ( strlen( $params['ad_tracking'] ) > 20 ) {
			$params['ad_tracking'] = substr( $params['ad_tracking'], 0, 20 );
		}

		$params = apply_filters( "gform_aweber_args_pre_subscribe_{$form['id']}", apply_filters( 'gform_aweber_args_pre_subscribe', $params, $form, $entry, $feed ), $form, $entry, $feed );

		try {
			$subscribers = $list->subscribers;
			$this->log_debug( __METHOD__ . '(): Creating subscriber: ' . print_r( $params, true ) );
			$new_subscriber = $subscribers->create( $params );
			$this->log_debug( __METHOD__ . '(): Subscriber created.' );
		} catch ( AWeberAPIException $exc ) {
			$this->log_error( __METHOD__ . "(): Unable to create subscriber: {$exc}" );
		}

	}

	private function get_address( $entry, $field_id ) {
		$street_value  = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.1' ) ) );
		$street2_value = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.2' ) ) );
		$city_value    = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.3' ) ) );
		$state_value   = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.4' ) ) );
		$zip_value     = trim( rgar( $entry, $field_id . '.5' ) );
		$country_value = trim( rgar( $entry, $field_id . '.6' ) );

		if ( ! empty( $country_value ) ) {
			$country_value = class_exists( 'GF_Field_Address' ) ? GF_Fields::get( 'address' )->get_country_code( $country_value ) : GFCommon::get_country_code( $country_value );
		}

		$address = $street_value;
		$address .= ! empty( $address ) && ! empty( $street2_value ) ? "  $street2_value" : $street2_value;
		$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? ", $city_value," : $city_value;
		$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? "  $state_value" : $state_value;
		$address .= ! empty( $address ) && ! empty( $zip_value ) ? "  $zip_value," : $zip_value;
		$address .= ! empty( $address ) && ! empty( $country_value ) ? "  $country_value" : $country_value;

		return $address;
	}

	private function get_name( $entry, $field_id ) {

		//If field is aweber (one input), simply return full content
		$name = rgar( $entry, $field_id );
		if ( ! empty( $name ) ) {
			return $name;
		}

		//Complex field (multiple inputs). Join all pieces and create name
		$prefix = trim( rgar( $entry, $field_id . '.2' ) );
		$first  = trim( rgar( $entry, $field_id . '.3' ) );
		$middle = trim( rgar( $entry, $field_id . '.4' ) );
		$last   = trim( rgar( $entry, $field_id . '.6' ) );
		$suffix = trim( rgar( $entry, $field_id . '.8' ) );

		$name = $prefix;
		$name .= ! empty( $name ) && ! empty( $first ) ? " $first" : $first;
		$name .= ! empty( $name ) && ! empty( $middle ) ? " $middle" : $middle;
		$name .= ! empty( $name ) && ! empty( $last ) ? " $last" : $last;
		$name .= ! empty( $name ) && ! empty( $suffix ) ? " $suffix" : $suffix;

		return $name;
	}
}
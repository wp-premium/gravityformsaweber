<?php

GFForms::include_feed_addon_framework();

class GFAWeber extends GFFeedAddOn {

	protected $_version = GF_AWEBER_VERSION;
	protected $_min_gravityforms_version = '1.9.3';
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

	/**
	 * Get an instance of this class.
	 *
	 * @return GFAWeber
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFAWeber();
		}

		return self::$_instance;
	}


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * If the AWeber API key is valid initiate processing the feed otherwise abort.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 */
	public function process_feed( $feed, $entry, $form ) {
		if ( ! $this->is_valid_key() ) {
			return;
		}

		$this->export_feed( $entry, $form, $feed );

	}

	/**
	 * Process the feed, subscribe the user to the AWeber list.
	 *
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 * @param array $feed The feed object currently being processed.
	 */
	public function export_feed( $entry, $form, $feed ) {

		$email = $this->get_field_value( $form, $entry, $feed['meta']['listFields_email'] );
		$name  = '';
		if ( ! empty( $feed['meta']['listFields_fullname'] ) ) {
			$name = $this->get_field_value( $form, $entry, $feed['meta']['listFields_fullname'] );
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
			$merge_vars[ $var_tag ] = $this->get_field_value( $form, $entry, $field_id );
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
			'ad_tracking' => gf_apply_filters( 'gform_aweber_ad_tracking', $form['id'], $form['title'], $entry, $form, $feed )
		);

		if ( ! empty( $list_custom_fields ) ) {
			$params['custom_fields'] = $list_custom_fields;
		}

		//ad tracking has a max size of 20 characters
		if ( strlen( $params['ad_tracking'] ) > 20 ) {
			$params['ad_tracking'] = substr( $params['ad_tracking'], 0, 20 );
		}

		$params = gf_apply_filters( 'gform_aweber_args_pre_subscribe', $form['id'], $params, $form, $entry, $feed );

		try {
			$subscribers = $list->subscribers;
			$this->log_debug( __METHOD__ . '(): Creating subscriber: ' . print_r( $params, true ) );
			$new_subscriber = $subscribers->create( $params );
			$this->log_debug( __METHOD__ . '(): Subscriber created.' );
		} catch ( AWeberAPIException $exc ) {
			$this->log_error( __METHOD__ . "(): Unable to create subscriber: {$exc}" );
		}

	}

	/**
	 * Returns the value of the selected field.
	 *
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @return string
	 */
	public function get_field_value( $form, $entry, $field_id ) {

		if ( ! $this->is_gravityforms_supported( '1.9.7' ) ) {

			$is_integer = $field_id == intval( $field_id );
			$field      = GFFormsModel::get_field( $form, $field_id );
			$input_type = RGFormsModel::get_input_type( $field );

			if ( $is_integer && $input_type == 'address' ) {

				$field_value = $this->get_full_address( $entry, $field_id );

			} elseif ( $is_integer && $input_type == 'name' ) {

				$field_value = $this->get_full_name( $entry, $field_id );

			} else {

				$field_value = rgar( $entry, $field_id );

			}

		} else {

			$field_value = parent::get_field_value( $form, $entry, $field_id );
		}

		if ( ! $this->is_gravityforms_supported( '1.9.10.15' ) ) {

			return $this->maybe_override_field_value( $field_value, $form, $entry, $field_id );
		}

		return $field_value;
	}

	/**
	 * Use the legacy gform_aweber_field_value filter instead of the framework gform_SLUG_field_value filter.
	 *
	 * @param string $field_value The field value.
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {

		return gf_apply_filters( 'gform_aweber_field_value', array(
			$form['id'],
			$field_id
		), $field_value, $form['id'], $field_id, $entry );
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support( array(
			'option_label' => esc_html__( 'Subscribe user to AWeber only when payment is received.', 'gravityformsaweber' )
		) );

	}

	// ------- Plugin settings -------

	/**
	 * Define the settings which should appear on the Forms > Settings > AWeber tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => esc_html__( 'AWeber Account Information', 'gravityformsaweber' ),
				'description' => sprintf( esc_html__( 'AWeber is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add them to your client\'s AWeber subscription list. If you don\'t have a AWeber account, you can %1$ssign up for one here%2$s', 'gravityformsaweber' ),
						'<a href="http://www.aweber.com" target="_blank">', '</a>.' )
					. '<br/><br/>' .
					sprintf( esc_html__( '%1$sClick here to retrieve your Authorization code%2$s', 'gravityformsaweber' ),
						'<a onclick="window.open(this.href,\'\',\'resizable=yes,location=no,width=750,height=525,status\'); return false" href="https://auth.aweber.com/1.0/oauth/authorize_app/2ad0d7d5">', '</a>.' )
					. '<br/>' .
					esc_html__( 'You will need to log in to your AWeber account. Upon a successful login, a string will be returned. Copy the whole string and paste into the text box below.', 'gravityformsaweber' ),
				'fields'      => array(
					array(
						'name'              => 'authorizationCode',
						'label'             => esc_html__( 'Authorization Code', 'gravityformsaweber' ),
						'type'              => 'authorization_code',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_key' )

					),
				)
			),
		);

	}

	/**
	 * Define the markup for the authorization_code type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 *
	 * @return string
	 */
	public function settings_authorization_code( $field, $echo = true ) {

		$authorization_code_field = $this->settings_text( $field, false );

		$caption = esc_html__( 'You can find your unique Authorization code by clicking on the link above and login into your AWeber account.', 'gravityformsaweber' );

		if ( $echo ) {
			echo $authorization_code_field . '</br><small>' . $caption . '</small>';
		}

		return $authorization_code_field . '</br><small>' . $caption . '</small>';

	}

	// ------- Feed list page -------

	/**
	 * Prevent feeds being listed or created if the AWeber auth code isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->is_valid_key();

	}

	/**
	 * If the api key is invalid or empty return the appropriate message.
	 *
	 * @return string
	 */
	public function configure_addon_message() {

		$settings_label = sprintf( __( '%s Settings', 'gravityforms' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		$settings = $this->get_plugin_settings();

		if ( rgempty( 'authorizationCode', $settings ) ) {

			return sprintf( __( 'To get started, please configure your %s.', 'gravityforms' ), $settings_link );
		}

		return sprintf( __( 'We are unable to login to AWeber with the provided Authorization code. Please make sure you have entered a valid Authorization code on the %s page.', 'gravityformsaweber' ), $settings_link );

	}

	/**
	 * Display a warning message instead of the feeds if the AWeber auth code isn't valid.
	 *
	 * @param array $form The form currently being edited.
	 * @param integer $feed_id The current feed ID.
	 */
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( ! $this->can_create_feed() ) {

			echo '<h3><span>' . $this->feed_settings_title() . '</span></h3>';
			echo '<div>' . $this->configure_addon_message() . '</div>';

			return;
		}

		parent::feed_edit_page( $form, $feed_id );
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'		=> esc_html__( 'Name', 'gravityformsaweber' ),
			'account'		=> esc_html__( 'AWeber Account', 'gravityformsaweber' ),
			'contactList'	=> esc_html__( 'AWeber List', 'gravityformsaweber' )
		);
	}

	/**
	 * Returns the value to be displayed in the AWeber List column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_contactList( $feed ) {
		return $this->get_list_name( $feed['meta']['account'], $feed['meta']['contactList'] );
	}

	/**
	 * Return the name of the specified AWeber list.
	 *
	 * @param string $account_id The AWeber account ID.
	 * @param string $list_id The AWeber list ID.
	 *
	 * @return string
	 */
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
			$list_name = $list_id . ' (' . esc_html__( 'List not found in AWeber', 'gravityformsaweber' ) . ')';
		}

		return $list_name;
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'title'       => esc_html__( 'AWeber Feed', 'gravityformsaweber' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformsaweber' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . esc_html__( 'Name', 'gravityformsaweber' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsaweber' ),
					),
					array(
						'name'     => 'account',
						'label'    => esc_html__( 'Account', 'gravityformsaweber' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'hidden'   => $this->is_accounts_hidden(),
						'choices'  => $this->get_aweber_accounts(),
						'tooltip'  => '<h6>' . esc_html__( 'Account', 'gravityformsaweber' ) . '</h6>' . esc_html__( 'Select the AWeber account you would like to add your contacts to.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'contactList',
						'label'      => esc_html__( 'Contact List', 'gravityformsaweber' ),
						'type'       => 'contact_list',
						'onchange'   => 'jQuery(this).parents("form").submit();',
						'dependency' => array( $this, 'has_selected_account' ),
						'tooltip'    => '<h6>' . esc_html__( 'Contact List', 'gravityformsaweber' ) . '</h6>' . esc_html__( 'Select the AWeber list you would like to add your contacts to.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'listFields',
						'label'      => esc_html__( 'Map Fields', 'gravityformsaweber' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'	 => $this->create_list_field_map(),
						'tooltip'    => '<h6>' . esc_html__( 'Map Fields', 'gravityformsaweber' ) . '</h6>' . esc_html__( 'Associate your AWeber fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsaweber' ),
					),
					array(
						'name'       => 'optin',
						'label'      => esc_html__( 'Conditional Logic', 'gravityformsaweber' ),
						'type'       => 'feed_condition',
						'dependency' => 'contactList',
						'tooltip'    => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformsaweber' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to AWeber when the condition is met. When disabled all form submissions will be exported.', 'gravityformsaweber' ),
					),

				)
			),
		);

	}

	/**
	 * Check if the account setting should be displayed.
	 *
	 * @return bool
	 */
	public function is_accounts_hidden() {
		if ( $this->has_multiple_accounts() ) {
			return false;
		}

		return true;
	}

	/**
	 * If there are multiple AWeber accounts return an array of choices for the account setting.
	 *
	 * @return array|void
	 */
	public function get_aweber_accounts() {

		$aweber_accounts = $this->get_accounts();

		if ( ! $aweber_accounts ) {
			return;
		}

		if ( $this->has_multiple_accounts() ) {
			$accounts_dropdown[] = array(
				'label' => esc_html__( 'Select Account', 'gravityformsaweber' ),
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

	/**
	 * Define the markup for the contact_list type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 *
	 * @return string|void
	 */
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

	/**
	 * Return an array of AWeber fields which can be mapped to the Form fields/entry meta.
	 *
	 * @return array
	 */
	public function create_list_field_map() {

		$list_id = $this->get_setting( 'contactList' );
		if ( empty( $list_id ) ) {
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


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Do multiple accounts exist?
	 *
	 * @return bool
	 */
	public function has_multiple_accounts() {
		$accounts = $this->get_accounts();
		if ( ! $accounts || $accounts->data['total_size'] == 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Has a choice been selected for the account setting?
	 *
	 * @return bool
	 */
	public function has_selected_account() {

		if ( $this->has_multiple_accounts() ) {
			$selected_account = $this->get_setting( 'account' );

			return ! empty( $selected_account );
		}

		return true;
	}

	/**
	 * Return the AWeber accounts.
	 *
	 * @return mixed
	 */
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

	/**
	 * Return an array of AWeber fields for the specified list.
	 *
	 * @param string $list_id The AWeber list ID.
	 * @param string $account_id The AWeber account ID.
	 *
	 * @return array
	 */
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

	/**
	 * Return the AWeber tokens.
	 *
	 * @param $api_credentials
	 *
	 * @return array
	 */
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

	/**
	 * Validate the API credentials.
	 *
	 * @return bool|null
	 */
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

	/**
	 * Return the AWeberAPI object.
	 *
	 * @return AWeberAPI|bool
	 */
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

	/**
	 * Return the API tokens.
	 *
	 * @return array
	 */
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

	/**
	 * Return the value of the access_token setting.
	 *
	 * @return string
	 */
	public function get_access_token() {
		$settings     = $this->get_plugin_settings();
		$access_token = $settings['access_token'];

		return $access_token;
	}

	/**
	 * Return the value of the access_token_secret setting.
	 *
	 * @return string
	 */
	public function get_access_token_secret() {
		$settings            = $this->get_plugin_settings();
		$access_token_secret = $settings['access_token_secret'];

		return $access_token_secret;
	}

	/**
	 * Include the AWeber API.
	 */
	public function include_api() {

		if ( ! class_exists( 'AWeberServiceProvider' ) ) {
			require_once $this->get_base_path() . '/api/aweber_api.php';
		}

	}


	// # TO FRAMEWORK MIGRATION ----------------------------------------------------------------------------------------

	/**
	 * Initialize the admin specific hooks.
	 */
	public function init_admin() {
		parent::init_admin();
		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	/**
	 * Maybe add the temporary plugin page to the menu.
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function maybe_create_menu( $menus ) {
		$current_user        = wp_get_current_user();
		$dismiss_aweber_menu = get_metadata( 'user', $current_user->ID, 'dismiss_aweber_menu', true );
		if ( $dismiss_aweber_menu != '1' ) {
			$menus[] = array( 'name'       => $this->_slug,
			                  'label'      => $this->get_short_title(),
			                  'callback'   => array( $this, 'temporary_plugin_page' ),
			                  'permission' => $this->_capabilities_form_settings
			);
		}

		return $menus;
	}

	/**
	 * Initialize the AJAX hooks.
	 */
	public function init_ajax() {
		parent::init_ajax();
		add_action( 'wp_ajax_gf_dismiss_aweber_menu', array( $this, 'ajax_dismiss_menu' ) );
	}

	/**
	 * Update the user meta to indicate they shouldn't see the temporary plugin page again.
	 */
	public function ajax_dismiss_menu() {

		$current_user = wp_get_current_user();
		update_metadata( 'user', $current_user->ID, 'dismiss_aweber_menu', '1' );
	}

	/**
	 * Display a temporary page explaining how feeds are now managed.
	 */
	public function temporary_plugin_page() {
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
			function dismissMenu() {
				jQuery('#gf_spinner').show();
				jQuery.post(ajaxurl, {
						action: "gf_dismiss_aweber_menu"
					},
					function (response) {
						document.location.href = '?page=gf_edit_forms';
						jQuery('#gf_spinner').hide();
					}
				);

			}
		</script>

		<div class="wrap about-wrap">
			<h1><?php esc_html_e( 'AWeber Add-On v2.0', 'gravityformsaweber' ) ?></h1>

			<div class="about-text"><?php esc_html_e( 'Thank you for updating! The new version of the Gravity Forms AWeber Add-On makes changes to how you manage your AWeber integration.', 'gravityformsaweber' ) ?></div>
			<div class="changelog">
				<hr/>
				<div class="feature-section col two-col">
					<div class="col-1">
						<h3><?php esc_html_e( 'Manage AWeber Contextually', 'gravityformsaweber' ) ?></h3>

						<p><?php esc_attr_e( 'AWeber Feeds are now accessed via the AWeber sub-menu within the Form Settings for the Form with which you would like to integrate AWeber.', 'gravityformsaweber' ) ?></p>
					</div>
					<div class="col-2 last-feature">
						<img src="http://gravityforms.s3.amazonaws.com/webimages/AddonNotice/NewAWeber2.png">
					</div>
				</div>

				<hr/>

				<form method="post" id="dismiss_menu_form" style="margin-top: 20px;">
					<input type="checkbox" name="dismiss_aweber_menu" value="1" onclick="dismissMenu();">
					<label><?php esc_html_e( 'I understand this change, dismiss this message!', 'gravityformsaweber' ) ?></label>
					<img id="gf_spinner" src="<?php echo GFCommon::get_base_url() . '/images/spinner.gif' ?>" alt="<?php esc_attr_e( 'Please wait...', 'gravityformsaweber' ) ?>" style="display:none;"/>
				</form>

			</div>
		</div>
		<?php
	}

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 */
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
					$feed_name = 'Feed ' . $counter;
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

	/**
	 * Migrate the plugin settings.
	 *
	 * @param array $settings
	 */
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

	/**
	 * Migrate the delayed payment setting for the PayPal add-on integration.
	 *
	 * @param $old_delay_setting_name
	 */
	public function update_paypal_delay_settings( $old_delay_setting_name ) {
		global $wpdb;
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		$new_delay_setting_name = 'delay_' . $this->_slug;

		//get paypal feeds from old table
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		//loop through feeds and look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard
		if ( ! empty( $paypal_feeds_old ) ) {
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds_old as $old_feed ) {
				$meta = $old_feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ) {
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					//update paypal meta to have new setting
					$meta = maybe_serialize( $meta );
					$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );
				}
			}
		}

		//get paypal feeds from new framework table
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );
		if ( ! empty( $paypal_feeds ) ) {
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds as $feed ) {
				$meta = $feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ) {
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					$this->update_feed_meta( $feed['id'], $meta );
				}
			}
		}
	}

	/**
	 * Retrieve any old PayPal feeds.
	 *
	 * @return bool|array
	 */
	public function get_old_paypal_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_paypal';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = GFFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
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

	/**
	 * Retrieve any old feeds which need migrating to the framework,
	 *
	 * @return bool|array
	 */
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

}
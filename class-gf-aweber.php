<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms AWeber Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GFAWeber extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  Unknown
	 * @access private
	 * @var    GFAWeber|null $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the AWeber Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_version Contains the version, defined from aweber.php
	 */
	protected $_version = GF_AWEBER_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.11';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsaweber';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_path The path of the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsaweber/aweber.php';

	/**
	 * Defines the full path to the class file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_full_path The full path to this file.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_url The URL of the Add-On.
	 */
	protected $_url = 'https://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'AWeber Add-On';

	/**
	 * Defines the short title of this Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_short_title The short title of the Add-On.
	 */
	protected $_short_title = 'AWeber';

	/**
	 * Defines if Add-On should use Gravity Forms server for update data.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_aweber';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_aweber';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_aweber_uninstall';

	/**
	 * Defines the capabilities needed for the AWeber Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On.
	 */
	protected $_capabilities = array( 'gravityforms_aweber', 'gravityforms_aweber_uninstall' );

	/**
	 * Get an instance of this class.
	 *
	 * @since  Unknown
	 * @access public
	 * @static
	 *
	 * @return GFAWeber
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Plugin starting point.
	 * Handles hooks, loading of language files and PayPal delayed payment support.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFeedAddOn::add_delayed_payment_support()
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support( array(
			'option_label' => esc_html__( 'Subscribe user to AWeber only when payment is received.', 'gravityformsaweber' ),
		) );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Define the settings which should appear on the Forms > Settings > AWeber tab.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		$auth_url   = 'https://auth.aweber.com/1.0/oauth/authorize_app/' . $this->get_app_id();
		$auth_a_tag = sprintf( '<a onclick="window.open(this.href,\'\',\'resizable=yes,location=no,width=750,height=525,status\'); return false" href="%s">', esc_url( $auth_url ) );

		return array(
			array(
				'title'       => esc_html__( 'AWeber Account Information', 'gravityformsaweber' ),
				'description' => sprintf(
					'<p>%s</p> <p>%s<br/>%s</p>',
					sprintf(
						esc_html__( 'AWeber is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add it to your client\'s AWeber subscription list. If you don\'t have an AWeber account, you can %1$ssign up for one here%2$s', 'gravityformsaweber' ),
						'<a href="http://www.aweber.com" target="_blank">',
						'</a>.'
					),
					sprintf(
						esc_html__( '%1$sClick here to retrieve your Authorization code%2$s', 'gravityformsaweber' ),
						$auth_a_tag,
						'</a>.'
					),
					esc_html__( 'You will need to log in to your AWeber account. Upon a successful login, a string will be returned. Copy the whole string and paste into the text box below.', 'gravityformsaweber' )
				),
				'fields'      => array(
					array(
						'name'              => 'authorizationCode',
						'label'             => esc_html__( 'Authorization Code', 'gravityformsaweber' ),
						'type'              => 'authorization_code',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_key' ),
					),
				),
			),
		);

	}

	/**
	 * Returns the AWeber app id to be used when authorizing the add-on with AWeber.
	 *
	 * @since 2.7.1
	 *
	 * @return string
	 */
	public function get_app_id() {

		/**
		 * Allows a custom AWeber app id to be defined for use when authorizing the add-on with AWeber.
		 *
		 * @since 2.7.1
		 *
		 * @param string $app_id The AWeber app id.
		 */
		$app_id = apply_filters( 'gform_aweber_app_id', '2ad0d7d5' );

		return $app_id;
	}

	/**
	 * Migrate the plugin settings.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @uses   GFAddOn::get_plugin_settings()
	 * @uses   GFAWeber::get_aweber_tokens()
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
	 * Define the markup for the authorization_code type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed.
	 *
	 * @return string
	 */
	public function settings_authorization_code( $field, $echo = true ) {

		// Prepare text input.
		$html = $this->settings_text( $field, false );

		// Add caption.
		$html .= sprintf(
			'</br><small>%s</small>',
			esc_html__( 'You can find your unique Authorization code by clicking on the link above and logging into your AWeber account.', 'gravityformsaweber' )
		);

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAWeber::create_list_field_map()
	 * @uses   GFAWeber::get_aweber_accounts()
	 * @uses   GFAWeber::is_accounts_hidden()
	 *
	 * @return array
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
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformsaweber' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsaweber' )
						),
					),
					array(
						'name'     => 'account',
						'label'    => esc_html__( 'Account', 'gravityformsaweber' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'hidden'   => $this->is_accounts_hidden(),
						'choices'  => $this->get_aweber_accounts(),
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Account', 'gravityformsaweber' ),
							esc_html__( 'Select the AWeber account you would like to add your contacts to.', 'gravityformsaweber' )
						),
					),
					array(
						'name'       => 'contactList',
						'label'      => esc_html__( 'Contact List', 'gravityformsaweber' ),
						'type'       => 'contact_list',
						'onchange'   => 'jQuery(this).parents("form").submit();',
						'dependency' => array( $this, 'has_selected_account' ),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Contact List', 'gravityformsaweber' ),
							esc_html__( 'Select the AWeber list you would like to add your contacts to.', 'gravityformsaweber' )
						),
					),
					array(
						'name'       => 'listFields',
						'label'      => esc_html__( 'Map Fields', 'gravityformsaweber' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'  => $this->create_list_field_map(),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformsaweber' ),
							esc_html__( 'Associate your AWeber fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsaweber' )
						),
					),
					array(
						'name'    => 'tags',
						'type'    => 'text',
						'dependency' => 'contactList',
						'class'   => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'label'   => esc_html__( 'Tags', 'gravityformsaweber' ),
						'tooltip' => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Tags', 'gravityformsaweber' ),
							esc_html__( 'Associate tags to your AWeber subscribers with a comma separated list. (e.g. new lead, Gravity Forms, web source)', 'gravityformsaweber' )
						),
					),
					array(
						'name'       => 'optin',
						'label'      => esc_html__( 'Conditional Logic', 'gravityformsaweber' ),
						'type'       => 'feed_condition',
						'dependency' => 'contactList',
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformsaweber' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to AWeber when the condition is met. When disabled all form submissions will be exported.', 'gravityformsaweber' )
						),
					),
				),
			),
		);

	}

	/**
	 * Check if the account setting should be displayed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAWeber::get_default_account()
	 * @uses   GFAWeber::has_multiple_accounts()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return bool
	 */
	public function is_accounts_hidden() {

		// Get account ID.
		$account_id = $this->get_setting( 'account', $this->get_default_account() );

		if ( ( ! empty( $account_id ) && ! $this->is_valid_account_id( $account_id ) ) || $this->has_multiple_accounts() ) {
			return false;
		}

		return true;

	}

	/**
	 * Has a choice been selected for the account setting?
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAWeber::get_default_account()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return bool
	 */
	public function has_selected_account() {

		// Get account ID.
		$account_id = $this->get_setting( 'account', $this->get_default_account() );

		return $this->is_valid_account_id( $account_id );

	}

	/**
	 * If there are multiple AWeber accounts, return an array of choices for the account setting.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAWeber::get_accounts()
	 * @uses   GFAWeber::has_multiple_accounts()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return array
	 */
	public function get_aweber_accounts() {

		// Get AWeber accounts.
		$aweber_accounts = $this->get_accounts();

		// If no accounts were found, return.
		if ( ! $aweber_accounts ) {
			return array();
		}

		// Get account ID.
		$account_id = $this->get_setting( 'account' );

		// Initialize choices array.
		$choices = array();

		// Add initial choice.
		if ( ( ! empty( $account_id ) && ! $this->is_valid_account_id( $account_id ) ) || $this->has_multiple_accounts() ) {

			$choices[] = array(
				'label' => esc_html__( 'Select Account', 'gravityformsaweber' ),
				'value' => '',
			);

		}

		// Loop through accounts.
		foreach ( $aweber_accounts as $account ) {

			// Add account as choice.
			$choices[] = array(
				'label' => esc_html( $account->id ),
				'value' => esc_attr( $account->id ),
			);

		}

		return $choices;

	}

	/**
	 * Define the markup for the contact_list type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed.
	 *
	 * @uses   AWeberAPIBase::loadFromUrl()
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAddOn::settings_select()
	 * @uses   GFAWeber::get_aweber_object()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return string
	 */
	public function settings_contact_list( $field, $echo = true ) {

		// Get account ID.
		$account_id = $this->get_setting( 'account', $this->get_default_account() );

		// If account ID is invalid, return.
		if ( ! $this->is_valid_account_id( $account_id ) ) {
			return '';
		}

		// Get AWeber API object.
		$aweber = $this->get_aweber_object();

		// Get AWeber account.
		$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );

		// If AWeber account could not be retrieved, return.
		if ( ! $account ) {
			return '';
		}

		// Add initial choice.
		$field['choices'] = array(
			array(
				'label' => esc_html__( 'Select List', 'gravityformsaweber' ),
				'value' => '',
			),
		);

		// Loop through lists.
		foreach ( $account->lists as $list ) {

			// Add list as choice.
			$field['choices'][] = array(
				'label' => esc_html( $list->name ),
				'value' => esc_attr( $list->id ),
			);

		}

		// Prepare input.
		$html = $this->settings_select( $field, false );

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Return an array of AWeber fields which can be mapped to the Form fields/entry meta.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_setting()
	 * @uses   GFAWeber::get_custom_fields()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return array
	 */
	public function create_list_field_map() {

		// Get account and list IDs.
		$account_id = $this->get_setting( 'account' );
		$list_id    = $this->get_setting( 'contactList' );

		// If no list is selected or the account ID is invalid, return.
		if ( empty( $list_id ) || ! $this->is_valid_account_id( $account_id ) ) {
			return array();
		}

		return $this->get_custom_fields( $list_id, $account_id );

	}

	/**
	 * Prevent feeds being listed or created if the AWeber auth code isn't valid.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAWeber::is_valid_key()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->is_valid_key();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'    => esc_html__( 'Name', 'gravityformsaweber' ),
			'account'     => esc_html__( 'AWeber Account', 'gravityformsaweber' ),
			'contactList' => esc_html__( 'AWeber List', 'gravityformsaweber' ),
		);

	}

	/**
	 * Returns the value to be displayed in the AWeber Account column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed The current Feed object.
	 *
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return string
	 */
	public function get_column_value_account( $feed ) {

		// Get account ID.
		$account_id = rgars( $feed, 'meta/account' );

		return $this->is_valid_account_id( $account_id ) ? esc_html( $account_id ) : esc_html__( 'Invalid ID', 'gravityformsaweber' );

	}

	/**
	 * Returns the value to be displayed in the AWeber List column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed The current Feed Object.
	 *
	 * @uses   AWeberAPIBase::loadFromUrl()
	 * @uses   GFAWeber::get_aweber_object()
	 * @uses   GFAWeber::is_valid_account_id()
	 *
	 * @return string
	 */
	public function get_column_value_contactList( $feed ) {

		global $_lists;

		// Get account and list IDs.
		$account_id = rgars( $feed, 'meta/account' );
		$list_id    = rgars( $feed, 'meta/contactList' );

		// If lists are not defined, retrieve them.
		if ( ! isset( $_lists ) ) {

			// Get AWeber API object.
			$aweber = $this->get_aweber_object();

			// If AWeber object could not be retrieved or account ID is invalid, return.
			if ( ! $aweber || ! $this->is_valid_account_id( $account_id ) ) {
				return '';
			}

			// Get account.
			$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );

			// Define lists.
			$_lists = $account->lists;

		}

		// Filter lists.
		$list_name_array = wp_filter_object_list( $_lists->data['entries'], array( 'id' => $list_id ), 'and', 'name' );

		// If lists were found, return selected list.
		if ( $list_name_array ) {

			$list_names = array_values( $list_name_array );

			return esc_html( rgar( $list_names, 0 ) );

		} else {

			return $list_id . ' (' . esc_html__( 'List not found in AWeber', 'gravityformsaweber' ) . ')';

		}

	}






	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed, subscribe the user to the AWeber list.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed  The current Feed object.
	 * @param array $entry The current Entry object.
	 * @param array $form  The current Form object.
	 *
	 * @uses   AWeberAPIBase::loadFromUrl()
	 * @uses   GFAddOn::get_field_map_fields()
	 * @uses   GFAddOn::get_field_value()
	 * @uses   GFAddOn::log_debug
	 * @uses   GFAWeber::get_aweber_object()
	 * @uses   GFAWeber::get_custom_fields()
	 * @uses   GFAWeber::is_valid_account_id()
	 * @uses   GFAWeber::is_valid_key()
	 * @uses   GFCommon::is_invalid_or_empty_email()
	 * @uses   GFFeedAddOn::add_feed_error()
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API credentials are invalid, exit.
		if ( ! $this->is_valid_key() ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user because API could not be initialized.', 'gravityformsaweber' ), $feed, $entry, $form );
			return $entry;
		}

		// Get email address.
		$email = $this->get_field_value( $form, $entry, rgars( $feed, 'meta/listFields_email' ) );

		// If email address is invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user because email address was invalid.', 'gravityformsaweber' ), $feed, $entry, $form );
			return $entry;
		}

		// Get account ID.
		$account_id = rgars( $feed, 'meta/account' );

		// If account ID is invalid, exit.
		if ( ! $this->is_valid_account_id( $account_id ) ) {
			$this->add_feed_error( esc_html__( 'Unable to subscribe user because account ID was invalid.', 'gravityformsaweber' ), $feed, $entry, $form );
			return $entry;
		}

		// Get list ID.
		$list_id = rgars( $feed, 'meta/contactList' );

		// Get AWeber API object.
		$aweber = $this->get_aweber_object();

		// Get account.
		$this->log_debug( __METHOD__ . '(): Getting account lists.' );
		$account = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id );

		// Get list.
		$this->log_debug( __METHOD__ . "(): Getting list for account {$account_id} with id {$list_id}" );
		$list = $account->loadFromUrl( "/accounts/{$account_id}/lists/{$list_id}" );

		// Prepare merge vars array.
		$merge_vars = array( '' );

		// Add field map fields to merge vars array.
		$field_maps = $this->get_field_map_fields( $feed, 'listFields' );
		foreach ( $field_maps as $var_tag => $field_id ) {
			$merge_vars[ $var_tag ] = $this->get_field_value( $form, $entry, $field_id );
		}

		// Get custom fields.
		$custom_fields = $this->get_custom_fields( $list_id, $account_id );

		// Removing email and full name from list of custom fields as they are handled separately.
		unset( $custom_fields[0] );
		unset( $custom_fields[1] );
		$custom_fields = array_values( $custom_fields );

		// Add custom fields.
		$list_custom_fields = array();
		foreach ( $custom_fields as $cf ) {
			$key                                = $cf['name'];
			$list_custom_fields[ $cf['label'] ] = (string) $merge_vars[ $key ];
		}

		// Prepare subscriber arguments.
		$params = array(
			'email'       => $email,
			'name'        => $this->get_field_value( $form, $entry, rgars( $feed, 'meta/listFields_fullname' ) ),
			'ad_tracking' => gf_apply_filters( 'gform_aweber_ad_tracking', $form['id'], $form['title'], $entry, $form, $feed ),
		);

		// If custom fields were found, add to subscriber arguments.
		if ( ! empty( $list_custom_fields ) ) {
			$params['custom_fields'] = $list_custom_fields;
		}

		// Ad tracking has a max size of 20 characters.
		if ( strlen( $params['ad_tracking'] ) > 20 ) {
			$params['ad_tracking'] = substr( $params['ad_tracking'], 0, 20 );
		}

		// Get tags.
		$tags = explode(',', rgars( $feed, 'meta/tags' ) );
		$tags = array_map( 'trim', $tags );

		// Prepare tags.
		if ( ! empty( $tags ) ) {

			// Loop through tags, replace merge tags.
			foreach ( $tags as &$tag ) {
				$tag = GFCommon::replace_variables( $tag, $form, $entry, false, false, false, 'text' );
				$tag = trim( $tag );
			}

			// Remove empty tags.
			$tags = array_filter( $tags );

		}

		// Add tags.
		if ( ! empty( $tags ) ) {
			$params['tags'] = $tags;
		}

		$params = gf_apply_filters( 'gform_aweber_args_pre_subscribe', $form['id'], $params, $form, $entry, $feed );

		try {

			$subscribers = $list->subscribers;
			$this->log_debug( __METHOD__ . '(): Creating subscriber: ' . print_r( $params, true ) );
			$new_subscriber = $subscribers->create( $params );
			$this->log_debug( __METHOD__ . '(): Subscriber created.' );

			$subscriber = rgobj( $new_subscriber, 'data' );

			/**
			 * Perform a custom action when a subscriber is successfully added to the list.
			 *
			 * @param array $subscriber The subscriber properties.
			 * @param array $form       The form currently being processed.
			 * @param array $entry      The entry currently being processed.
			 * @param array $feed       The feed currently being processed.
			 *
			 * @since 2.4.1
			 */
			do_action( 'gform_aweber_post_subscriber_created', $subscriber, $form, $entry, $feed );

		} catch ( AWeberAPIException $exc ) {

			$this->add_feed_error( "Unable to create subscriber: {$exc}", $feed, $entry, $form );

		}

		return $entry;

	}

	/**
	 * Use the legacy gform_aweber_field_value filter instead of the framework gform_SLUG_field_value filter.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $field_value The field value.
	 * @param array  $form        The current Form object.
	 * @param array  $entry       The current Entry object.
	 * @param string $field_id    The ID of the field being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {

		return gf_apply_filters( 'gform_aweber_field_value', array(
			$form['id'],
			$field_id,
		), $field_value, $form['id'], $field_id, $entry );

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Return the default account.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAWeber::get_accounts()
	 *
	 * @return string
	 */
	public function get_default_account() {

		// Get AWeber accounts.
		$accounts = $this->get_accounts();

		// Return first account ID.
		if ( is_object( $accounts ) && is_array( $accounts->data['entries'] ) ) {

			return $accounts->data['entries']['0']['id'];
		}

		return '';

	}

	/**
	 * Check if the account ID is valid.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $account_id The AWeber account ID.
	 *
	 * @uses   GFAWeber::get_accounts()
	 *
	 * @return bool
	 */
	public function is_valid_account_id( $account_id ) {

		// If account ID is empty, return.
		if ( empty( $account_id ) ) {
			return false;
		}

		// Get AWeber accounts.
		$accounts = $this->get_accounts();

		if ( is_object( $accounts ) && is_array( $accounts->data['entries'] ) ) {

			// Loop through accounts.
			foreach ( $accounts->data['entries'] as $account ) {

				// If this is the account we are validating, return.
				if ( $account_id == $account['id'] ) {
					return true;
				}

			}

		}

		return false;

	}

	/**
	 * Do multiple accounts exist?
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAWeber::get_accounts()
	 *
	 * @return bool
	 */
	public function has_multiple_accounts() {

		// Get AWeber accounts.
		$accounts = $this->get_accounts();

		// If only one account was found, return.
		if ( ! $accounts || $accounts->data['total_size'] == 1 ) {
			return false;
		}

		return true;

	}

	/**
	 * Return the AWeber accounts.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   AWeberAPIBase::loadFromUrl()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAWeber::get_aweber_object()
	 * @uses   GFCache::get()
	 * @uses   GFCache::set()
	 *
	 * @return mixed
	 */
	private function get_accounts() {

		// Get accounts from cache.
		$accounts = GFCache::get( 'aweber_accounts' );

		// If accounts were found, return them.
		if ( $accounts ) {
			return $accounts;
		}

		// Get AWeber API object.
		$aweber = $this->get_aweber_object();

		// Get accounts.
		$accounts = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts' );

		// Log accounts.
		$this->log_debug( __METHOD__ . '(): Retrieve AWeber accounts; ' . print_r( $accounts, true ) );

		// Save accounts to cache.
		GFCache::set( 'aweber_accounts', $accounts );

		return $accounts;

	}

	/**
	 * Return an array of AWeber fields for the specified list.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $list_id    The AWeber list ID.
	 * @param string $account_id The AWeber account ID.
	 *
	 * @uses   AWeberAPIBase::loadFromUrl()
	 * @uses   GFAWeber::get_aweber_object()
	 *
	 * @return array
	 */
	public function get_custom_fields( $list_id, $account_id ) {

		// Initialize default custom field choices.
		$custom_fields = array(
			array(
				'label'      => esc_html__( 'Email Address', 'gravityformsaweber' ),
				'name'       => 'email',
				'required'   => true,
				'field_type' => array( 'email', 'hidden' ),
			),
			array(
				'label' => esc_html__( 'Full Name', 'gravityformsaweber' ),
				'name'  => 'fullname',
			),
		);

		// Get AWeber API object.
		$aweber = $this->get_aweber_object();

		// Get AWeber custom fields.
		$aweber_custom_fields = $aweber->loadFromUrl( 'https://api.aweber.com/1.0/accounts/' . $account_id . '/lists/' . $list_id . '/custom_fields' );

		// Loop through custom fields.
		foreach ( $aweber_custom_fields as $cf ) {

			// If custom field name or ID is empty, skip.
			if ( empty( $cf->data['name'] ) || empty( $cf->data['id'] ) ) {
				continue;
			}

			// Add custom field to array.
			$custom_fields[] = array(
				'label' => esc_html( $cf->data['name'] ),
				'name'  => esc_attr( 'cf_' . $cf->data['id'] ),
			);

		}

		return $custom_fields;

	}

	/**
	 * Return the AWeber tokens.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $api_credentials AWeber API credentials.
	 *
	 * @uses   AWeberAPI::getAccessToken()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 * @uses   GFAWeber::include_api()
	 *
	 * @return array
	 */
	public function get_aweber_tokens( $api_credentials = '' ) {

		// Include AWeber API library.
		$this->include_api();

		// Separate API credentials.
		list( $application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier ) = rgexplode( '|', $api_credentials, 5 );

		// Log that we are getting authentication tokens.
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
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   AWeberAPI::getAccount()
	 * @uses   GFAddOn::get_plugin_setting()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error
	 * @uses   GFAWeber::get_access_token()
	 * @uses   GFAWeber::get_access_token_secret()
	 * @uses   GFAWeber::get_aweber_object()
	 *
	 * @return bool|null
	 */
	public function is_valid_key() {

		// Get API credentials.
		$api_credentials = $this->get_plugin_setting( 'authorizationCode' );

		// If API credentials are empty, return.
		if ( empty( $api_credentials ) ) {
			return null;
		}

		// Get AWeber API object and access tokens.
		$aweber              = $this->get_aweber_object();
		$access_token        = $this->get_access_token();
		$access_token_secret = $this->get_access_token_secret();

		try {

			// Log that we are validating API credentials.
			$this->log_debug( __METHOD__ . '(): Validating API credentials.' );

			// Get account.
			$account = $aweber->getAccount( $access_token, $access_token_secret );

		} catch ( AWeberException $e ) {

			// Log that API credentials could not be validated.
			$this->log_error( __METHOD__ . "(): Unable to validate API credentials: {$e}" );

			// Set account to null.
			$account = null;

		}

		// Log that credentials were validated.
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
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   AWeberAPI
	 * @uses   GFAWeber::get_access_token()
	 * @uses   GFAWeber::get_access_token_secret()
	 * @uses   GFAWeber::get_api_tokens()
	 * @uses   GFAWeber::include_api()
	 *
	 * @return AWeberAPI|bool
	 */
	public function get_aweber_object() {

		// Include AWeber API library.
		$this->include_api();

		// Get API tokens.
		$tokens = $this->get_api_tokens();

		// If tokens are empty, return.
		if ( empty( $tokens['application_key'] ) && empty( $tokens['application_secret'] ) && empty( $tokens['request_token'] ) && empty( $tokens['oauth_verifier'] ) ) {
			return false;
		}

		// Initialize new AWeber API object.
		$aweber = new AWeberAPI( $tokens['application_key'], $tokens['application_secret'] );

		// Assign AWeber credentials to object.
		$aweber->user->requestToken = $tokens['request_token'];
		$aweber->user->verifier     = $tokens['oauth_verifier'];
		$aweber->user->accessToken  = $this->get_access_token();
		$aweber->user->tokenSecret  = $this->get_access_token_secret();

		return $aweber;

	}

	/**
	 * Return the API tokens.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 *
	 * @return array
	 */
	public function get_api_tokens() {

		// Get API credentials.
		$api_credentials = $this->get_plugin_setting( 'authorizationCode' );

		// Separate token details.
		list( $application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier ) = rgexplode( '|', $api_credentials, 5 );

		return array(
			'application_key'      => $application_key,
			'application_secret'   => $application_secret,
			'request_token'        => $request_token,
			'request_token_secret' => $request_token_secret,
			'oauth_verifier'       => $oauth_verifier,
		);

	}

	/**
	 * Return the value of the access_token setting.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 *
	 * @return string
	 */

	public function get_access_token() {

		return $this->get_plugin_setting( 'access_token' );

	}

	/**
	 * Return the value of the access_token_secret setting.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 *
	 * @return string
	 */
	public function get_access_token_secret() {

		return $this->get_plugin_setting( 'access_token_secret' );

	}

	/**
	 * Include the AWeber API.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::get_base_path()
	 */
	public function include_api() {

		if ( ! class_exists( 'AWeberServiceProvider' ) ) {
			require_once $this->get_base_path() . '/includes/autoload.php';
		}

	}





	// # UPGRADES ------------------------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 *
	 * @uses   GFAddOn::update_plugin_settings()
	 * @uses   GFAWeber::get_old_feeds()
	 * @uses   GFAWeber::update_paypal_delay_settings()
	 * @uses   GFFeedAddOn::insert_feed()
	 */
	public function upgrade( $previous_version ) {

		// If previous version is empty, check legacy option.
		$previous_version = empty( $previous_version ) ? get_option( 'gf_aweber_version' ) : $previous_version;

		// Determine if previous version is before the Add-On Framework update.
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		// If previous version is not before the Add-On Framework update, exit.
		if ( ! $previous_is_pre_addon_framework ) {
			return;
		}

		// Get old feeds.
		$old_feeds = $this->get_old_feeds();

		// If no old feeds were found, exit.
		if ( ! $old_feeds ) {
			return;
		}

		// Loop through old feeds.
		foreach ( $old_feeds as $i => $old_feed ) {

			// Prepare feed name.
			$feed_name = 'Feed ' . ( $i + 1 );

			// Get feed form ID and active state.
			$form_id   = $old_feed['form_id'];
			$is_active = $old_feed['is_active'];

			// Initialize feed meta array.
			$new_meta = array(
				'feedName'    => $feed_name,
				'account'     => rgar( $old_feed['meta'], 'client_id' ),
				'contactList' => rgar( $old_feed['meta'], 'contact_list_id' ),
			);

			// Migrate field mapping.
			foreach ( $old_feed['meta']['field_map'] as $var_tag => $field_id ) {
				$new_meta[ 'listFields_' . $var_tag ] = $field_id;
			}

			// Migrate Opt-In condition.
			if ( rgars( $old_feed, 'meta/optin_enabled' ) ) {

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
						),
					),
				);

			} else {

				$new_meta['feed_condition_conditional_logic'] = 0;

			}

			// Insert new feed.
			$this->insert_feed( $form_id, $is_active, $new_meta );

		}

		// Get old plugin settings.
		$old_settings = get_option( 'gf_aweber_settings' );

		// Prepare new plugin settings.
		$new_settings = array(
			'authorizationCode'   => $old_settings['api_credentials'],
			'access_token'        => $old_settings['access_token'],
			'access_token_secret' => $old_settings['access_token_secret'],
		);

		// Save plugin settings.
		parent::update_plugin_settings( $new_settings );

		// Set PayPal delay setting.
		$this->update_paypal_delay_settings( 'delay_aweber_subscription' );

	}

	/**
	 * Migrate the delayed payment setting for the PayPal Add-On integration.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $old_delay_setting_name
	 *
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAWeber::get_old_paypal_feeds()
	 * @uses   GFFeedAddOn::get_feeds_by_slug()
	 * @uses   GFFeedAddOn::update_feed_meta()
	 * @uses   wpdb::update()
	 */
	public function update_paypal_delay_settings( $old_delay_setting_name ) {

		global $wpdb;

		// Log that we are updating PayPal delay settings.
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		// Prepare new PayPal delay setting name.
		$new_delay_setting_name = 'delay_' . $this->_slug;

		// Get PayPal feeds from old table.
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		// Loop through feeds and look for delay setting and create duplicate with new delay setting for the non-framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds_old ) ) {

			// Log that old feeds were found.
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through feeds.
			foreach ( $paypal_feeds_old as $old_feed ) {

				// Get old feed meta.
				$meta = $old_feed['meta'];

				// If PayPal delay setting was not found, skip.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				// Copy delay meta.
				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];

				// Serialize meta.
				$meta = maybe_serialize( $meta );

				// Update PayPal meta.
				$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );

			}

		}

		// Get PayPal feeds from new framework table.
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );

		// Loop through feeds and look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds ) ) {

			// Log that new PayPal feeds were found.
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through feeds.
			foreach ( $paypal_feeds as $feed ) {

				// Get feed meta.
				$meta = $feed['meta'];

				// If PayPal delay setting was not found, skip.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				// Copy delay meta.
				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];

				// Update feed.
				$this->update_feed_meta( $feed['id'], $meta );

			}

		}

	}

	/**
	 * Retrieve any old PayPal feeds.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::table_exists()
	 * @uses   GFFormsModel::get_form_table_name()
	 * @uses   wpdb::get_results()
	 *
	 * @return bool|array
	 */
	public function get_old_paypal_feeds() {

		global $wpdb;

		// Prepare table name.
		$table_name = $wpdb->prefix . 'rg_paypal';

		// If table does not exist, return.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Prepare SQL statement.
		$form_table_name = GFFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		// Log SQL statement.
		$this->log_debug( __METHOD__ . "(): getting old paypal feeds: {$sql}" );

		// Get PayPal feeds.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Log SQL error.
		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		// Get number of feeds.
		$count = count( $results );

		// Log number of feeds.
		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		// Unserialize feed meta.
		for ( $i = 0; $i < $count; $i++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

	/**
	 * Retrieve any old feeds which need migrating to the framework.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::table_exists()
	 * @uses   GFFormsModel::get_form_table_name()
	 * @uses   wpdb::get_results()
	 *
	 * @return bool|array
	 */
	public function get_old_feeds() {

		global $wpdb;

		// Prepare table name.
		$table_name = $wpdb->prefix . 'rg_aweber';

		// If table does not exist, return.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Prepare SQL statement.
		$form_table_name = GFFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM $table_name s
				INNER JOIN $form_table_name f ON s.form_id = f.id";

		// Get old feeds.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Uneserialize feed meta.
		for ( $i = 0; $i < count( $results ); $i++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

}

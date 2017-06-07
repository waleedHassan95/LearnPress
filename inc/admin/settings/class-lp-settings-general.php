<?php

/**
 * Class LP_Settings_General
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings/Classes
 * @version 1.0
 */
class LP_Settings_General extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'general';
		$this->text = __( 'General', 'learnpress' );
		parent::__construct();
	}

	/**
	 * Return fields for settings page.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'learn-press/general-settings-fields',
			array(
				/*array(
					'title'   => __( 'Instructors registration', 'learnpress' ),
					'desc'    => __( 'Create option for instructors registration.', 'learnpress' ),
					'id'      => $this->get_field_name( 'instructor_registration' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),*/
				array(
					'title' => __( 'Currency', 'learnpress' ),
					'type'  => 'heading',
					'desc'  => __( 'Setting up your currency unit and its formatting.', 'learnpress' )
				),
				array(
					'title'   => __( 'Currency', 'learnpress' ),
					'id'      => $this->get_field_name( 'currency' ),
					'default' => 'USD',
					'type'    => 'select',
					'options' => $this->_get_currency_options()
				),
				array(
					'title'   => __( 'Currency position', 'learnpress' ),
					'id'      => $this->get_field_name( 'currency_pos' ),
					'default' => 'left',
					'type'    => 'select',
					'options' => $this->_get_currency_positions()
				),
				array(
					'title'   => __( 'Thousands Separator', 'learnpress' ),
					'id'      => $this->get_field_name( 'thousands_separator' ),
					'default' => ',',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Decimals Separator', 'learnpress' ),
					'id'      => $this->get_field_name( 'decimals_separator' ),
					'default' => '.',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Number of Decimals', 'learnpress' ),
					'id'      => $this->get_field_name( 'number_of_decimals' ),
					'default' => '2',
					'type'    => 'number'
				)/*,
				array(
					'title'   => __( 'Load css', 'learnpress' ),
					'id'      => $this->get_field_name( 'load_css' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Load default stylesheet for LearnPress', 'learnpress' )
				),
				array(
					'title'   => __( 'Debug mode', 'learnpress' ),
					'id'      => $this->get_field_name( 'debug' ),
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Turn on/off debug mode for developer', 'learnpress' )
				),
				array(
					'title' => __( 'Logout', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Redirect to page', 'learnpress' ),
					'id'      => $this->get_field_name( 'logout_redirect_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				),*/
			)
		);
		// Deprecated
		$settings = apply_filters( 'learn_press_general_settings', $settings );

		return $settings;
	}

	private function _get_currency_options() {
		$currencies = array();

		if ( $payment_currencies = learn_press_get_payment_currencies() ) {
			foreach ( $payment_currencies as $code => $symbol ) {
				$currencies[ $code ] = $symbol;
			}
		}

		return $currencies;
	}

	private function _get_currency_positions() {
		$positions = array();
		foreach ( learn_press_currency_positions() as $pos => $text ) {
			switch ( $pos ) {
				case 'left':
					$text = sprintf( '%s ( %s%s )', $text, learn_press_get_currency_symbol(), '69.99' );
					break;
				case 'right':
					$text = sprintf( '%s ( %s%s )', $text, '69.99', learn_press_get_currency_symbol() );
					break;
				case 'left_with_space':
					$text = sprintf( '%s ( %s %s )', $text, learn_press_get_currency_symbol(), '69.99' );
					break;
				case 'right_with_space':
					$text = sprintf( '%s ( %s %s )', $text, '69.99', learn_press_get_currency_symbol() );
					break;
			}
			$positions[ $pos ] = $text;
		}

		return $positions;
	}
}

return new LP_Settings_General();
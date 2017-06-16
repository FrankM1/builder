<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * A simple Select box control.
 *
 * @param string $default     The selected option key
 *                            Default empty
 * @param array $options      Array of key & value pairs: `[ 'key' => 'value', ... ]`
 *                            Default empty
 *
 * @since 1.0.0
 */
class Control_Select extends Base_Control {

	public function get_type() {
		return 'select';
	}

	public function content_template() {
		?>
		<div class="builder-control-field">
			<label class="builder-control-title">{{{ data.label }}}</label>
			<div class="builder-control-input-wrapper">
				<select data-setting="{{ data.name }}">
				<<#
				_.each( data.options, function( option_title, option_value ) {
					if( typeof option_title == 'object' ) {
						#>
							<optgroup label="{{{ option_value }}}">
						<#
						_.each( option_title, function( title, value ) {
							#>
							<option value="{{ value }}">{{{ title }}}</option>
							<#
						} );
						#>
							</optgroup>
						<#
					} else {
						#>
						<option value="{{ option_value }}">{{{ option_title }}}</option>
						<#
					}
				} );

				#>
				</select>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="builder-control-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
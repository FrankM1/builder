<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * An 'Order By' select box control.
 *
 *	@param array $default {
 * 		@type string $order_by      The selected order
 *                                  Default empty
 * 		@type string $reverse_order Whether to reverse the order
 *                                  Default empty
 * }
 *
 * @param array $options      Array of key & value pairs: `[ 'key' => 'value', ... ]`
 *                            Default empty
 *
 * @since 1.0.0
 */
class Control_Order extends Base_Control_Multiple {

	public function get_type() {
		return 'order';
	}

	public function get_default_value() {
		return [
			'order_by' => '',
			'reverse_order' => '',
		];
	}

	public function content_template() {
		?>
		<div class="builder-control-field">
			<label class="builder-control-title">{{{ data.label }}}</label>
			<div class="builder-control-input-wrapper">
				<div class="builder-control-oreder-wrapper">
					<select data-setting="order_by">
						<# _.each( data.options, function( option_title, option_value ) { #>
							<option value="{{ option_value }}">{{{ option_title }}}</option>
							<# } ); #>
					</select>
					<input id="builder-control-order-input-{{ data._cid }}" type="checkbox" data-setting="reverse_order">
					<label for="builder-control-order-input-{{ data._cid }}" class="builder-control-order-label">
						<i class="fa fa-sort-amount-desc"></i>
					</label>
				</div>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="builder-control-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
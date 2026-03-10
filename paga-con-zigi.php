<?php

if (! defined('ABSPATH')) {
	exit;
}

/*
 * Plugin Name: Paga con ZIGI
 * Description: Método de pago para WooCommerce que permite realizar pagos escaneando un código QR (Cuik) con la app ZIGI (Guatemala).
 * Requires at least: 5.2
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Version: 2.1.0
 * Author: Andrés Turcios
 * Plugin URI: https://neopunto.com
 * Author URI: https://neopunto.com
 * Text Domain: paga-con-zigi
 * Requires Plugins: woocommerce
 * WC tested up to: 10.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'zigi_payment_add_gateway_class');
if (!function_exists('zigi_payment_add_gateway_class')) {
	function zigi_payment_add_gateway_class($gateways)
	{
		$gateways[] = 'Zigi_Payment_WC_Gateway';
		return $gateways;
	}
}

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', 'zigi_payment_hpos_compatibility');
if (!function_exists('zigi_payment_hpos_compatibility')) {
	function zigi_payment_hpos_compatibility()
	{
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'zigi_payment_init_gateway_class', 11);
if (!function_exists('zigi_payment_init_gateway_class')) {
	function zigi_payment_init_gateway_class()
	{

		if (class_exists('WC_Payment_Gateway')) {

			require_once plugin_dir_path(__FILE__) . 'functions.php';

			if (!class_exists('Zigi_Payment_WC_Gateway')) {
				class Zigi_Payment_WC_Gateway extends WC_Payment_Gateway
				{

					public function __construct()
			{

				$this->id = 'zigi_payment'; // payment gateway plugin ID
				$this->icon = ''; // No icon
				$this->has_fields = true; // in case you need a custom credit card form
				$this->method_title = __('Paga con ZIGI', 'paga-con-zigi');
				$this->method_description = __('Método de pago QR ZIGI.', 'paga-con-zigi'); // will be displayed on the options page

				// gateways can support subscriptions, refunds, saved payment methods,
				// but in this tutorial we begin with simple payments
				$this->supports = array(
					'products'
				);

				// Method with all the options fields
				$this->init_form_fields();

				// Load the settings.
				$this->init_settings();
				$this->title = $this->get_option('title');
				$this->description = $this->get_option('description');
				$this->enabled = $this->get_option('enabled');

				// This action hook saves the settings
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}

			/**
			 * Plugin options, we deal with it in Step 3 too
			 */
			public function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title'       => __('Habilitar/Deshabilitar', 'paga-con-zigi'),
						'label'       => __('Habilitar Paga con ZIGI', 'paga-con-zigi'),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no'
					),
					'title' => array(
						'title'       => __('Título', 'paga-con-zigi'),
						'type'        => 'text',
						'description' => __('Esto controla el título que el usuario ve durante el pago.', 'paga-con-zigi'),
						'default'     => __('Paga con ZIGI', 'paga-con-zigi'),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __('Descripción', 'paga-con-zigi'),
						'type'        => 'textarea',
						'description' => __('Esto controla la descripción que el usuario ve durante el pago.', 'paga-con-zigi'),
						'default'     => __('Método de pago vía QR ZIGI. Al realizar el pago, debes adjuntar el comprobante con la orden de compra.', 'paga-con-zigi'),
						'desc_tip'    => true,
					),
					'front_description' => array(
						'title'       => __('Descripción del Popup', 'paga-con-zigi'),
						'type'        => 'textarea',
						'default'     => __('Debes escanear el código QR, hacer clic en continuar para adjuntar la captura (es el único comprobante de pago) y podrás completar la compra.', 'paga-con-zigi'),
						'desc_tip'    => true,
					),
					'limit_amount' => array(
						'title'       => __('Monto Límite', 'paga-con-zigi'),
						'type'        => 'text',
						'description' => __('En este campo puedes ingresar el monto límite de pago', 'paga-con-zigi'),
						'default'     => '',
						'desc_tip'    => true,
					),
					'message_limit_amount' => array(
						'title'       => __('Mensaje de Monto Límite', 'paga-con-zigi'),
						'type'        => 'text',
						'description' => __('Agrega el mensaje para informar sobre el límite del monto a pagar.', 'paga-con-zigi'),
						'default'     => __('Este método no permite pagos mayores a 500 por día.', 'paga-con-zigi'),
						'desc_tip'    => true,
					),
					'number_telephone' => array(
						'title'       => __('Número de Teléfono Afiliado', 'paga-con-zigi'),
						'type'        => 'text',
						'description' => __('Ingresa el número afiliado a ZIGI (Guatemala).', 'paga-con-zigi'),
						'default'     => '',
						'desc_tip'    => true,
					),
					'upload_qr' => array(
						'title'       => __('Seleccionar Imagen QR', 'paga-con-zigi'),
						'type'        => 'button',
						'class'		  => 'kwp_upload_image_button button-secondary',
						'label'		  => 'axa',
						'description' => __('Aquí debes subir la imagen QR.', 'paga-con-zigi'),
						'desc_tip'    => true,
					),
					'preview_qr' => array(
						'title'       => '',
						'type'        => 'hidden',
						'class'		  => 'kwp_preview_qr',
					)
				);
			}


			public function generate_button_html($key, $data)
			{
				$field    = $this->plugin_id . $this->id . '_' . $key;
				$defaults = array(
					'class'             => 'button-secondary',
					'css'               => '',
					'custom_attributes' => array(),
					'desc_tip'          => false,
					'description'       => '',
					'title'             => '',
				);

				$data = wp_parse_args($data, $defaults);

				ob_start();
			?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr($field); ?>"><?php echo wp_kses_post($data['title']); ?></label>
						<?php echo wp_kses_post($this->get_tooltip_html($data)); ?>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
							<div class="upload_area zigi-payment-upload-wrapper">
								<span><?php esc_html_e('Sube el QR aquí', 'paga-con-zigi'); ?></span>
								<button class="<?php echo esc_attr($data['class']); ?>" type="button" name="<?php echo esc_attr($field); ?>" id="<?php echo esc_attr($field); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php echo wp_kses_post($this->get_custom_attribute_html($data)); ?>><?php echo wp_kses_post($data['title']); ?></button>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr($field); ?>"><?php esc_html_e('Vista Previa', 'paga-con-zigi'); ?></label>
					</th>
					<td class="forminp yape-preview-area">
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e('Vista Previa', 'paga-con-zigi'); ?></span></legend>
							<div class="preview_area">
								<?php
								$options = get_option('woocommerce_zigi_payment_settings');
								if (isset($options['preview_qr']) && !empty($options['preview_qr'])) {
								?>
									<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Imagen controlada 
									?>
									<img src="<?php echo esc_attr($options['preview_qr']); ?>" class="upload_qr" alt="Preview Icon" loading="lazy">
									<button class="remove_qr button-secondary" type="button"><?php esc_html_e('Eliminar', 'paga-con-zigi'); ?></button>
									<?php echo esc_html($this->get_description_html($data)); ?>
								<?php } ?>
							</div>
						</fieldset>
					</td>
				</tr>
				<?php
				return ob_get_clean();
			}

			/**
			 * You will need it if you want your custom credit card form, Step 4 is about it
			 */
			public function payment_fields()
			{

				// ok, let's display some description before the payment form
				if ($this->description) {
					// display the description with <p> tags etc.
					echo wp_kses_post(wpautop($this->description));
				}
				
				// Add hidden input for the payment receipt
				?>
				<input type="hidden" name="zigi-payment-qrcode" id="zigi-payment-qrcode" value="" />
				<?php
			}

			/*
			 * We're processing the payments here, everything about it is in Step 5
			 */
			public function process_payment($order_id)
			{

				$order = wc_get_order($order_id);

				if (isset($_POST['zigi-payment-qrcode']) && !empty($_POST['zigi-payment-qrcode'])) {
					$order->update_meta_data('zigi-payment-qrcode', esc_url_raw($_POST['zigi-payment-qrcode']));
					$order->save();
				}


				// Mark as on-hold (we're awaiting the payment)
				$order->update_status('on-hold', __('Esperando pago offline', 'paga-con-zigi'));

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				WC()->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result'    => 'success',
					'redirect'  => $this->get_return_url($order)
				);
			}
				}
			}
		}
	}
}

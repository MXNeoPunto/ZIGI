<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!function_exists('zigi_payment_admin_script')) {
	function zigi_payment_admin_script()
	{

		if (! did_action('wp_enqueue_media')) {
			wp_enqueue_media();
		}
		wp_enqueue_script('zigi-payment-admin', plugins_url('/assets/woopro.js', __FILE__), array('jquery'), '1.1', false);
		wp_enqueue_style('zigi-payment-admin', plugins_url('/assets/woopro.css', __FILE__), array(), '1.1');
	}
}
add_action('admin_enqueue_scripts', 'zigi_payment_admin_script');

if (!function_exists('zigi_payment_popup')) {
	function zigi_payment_popup()
	{

		$options = get_option('woocommerce_zigi_payment_settings');
?>
		<div class="popup-wrapper">
			<span class="helper"></span>
			<div class="popup-main-wrapper">
				<div class="popupCloseButton">&times;</div>
				<div class="first-step" data-price-limit="<?php echo (isset($options['limit_amount']) && !empty($options['limit_amount'])) ? esc_attr($options['limit_amount']) : ''; ?>">
					<?php
					if (isset($options['preview_qr']) && !empty($options['preview_qr'])) {
					?>
						<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Imagen controlada ?>
						<img src="<?php echo esc_url($options['preview_qr']); ?>" class="popup-qr" alt="Preview Image" loading="lazy" />
						<?php if (isset($options['number_telephone']) && !empty($options['number_telephone'])) { ?>
							<span class="telephone-number"><a href="tel:<?php echo esc_attr($options['number_telephone']); ?>"><?php esc_html_e('Agregar Contacto:', 'paga-con-zigi'); ?> <?php echo esc_attr($options['number_telephone']); ?></a></span>
						<?php } ?>
						<span class="price"><?php esc_html_e('Monto a Pagar', 'paga-con-zigi'); ?><?php echo wp_kses_post(WC()->cart->get_cart_total()); ?></span>
						<?php if (isset($options['message_limit_amount']) && !empty($options['message_limit_amount'])) { ?>
							<p class="message-limit-amount"><?php echo esc_attr($options['message_limit_amount']); ?></p>
						<?php } ?>
						<?php if (isset($options['front_description']) && !empty($options['front_description'])) { ?>
							<p><?php echo esc_html($options['front_description']); ?></p>
						<?php } ?>
					<?php } ?>
					<div class="popup-price-wrapper"></div>
				</div>
				<div class="second-step">
					<form method="post" enctype="multipart/form-data" novalidate="" class="box has-advanced-upload">
						<div class="box__input">
							<svg class="box__icon" xmlns="http://www.w3.org/2000/svg" width="50" height="43" viewBox="0 0 50 43"><path d="M48.4 26.5c-.9 0-1.7.7-1.7 1.7v11.6h-43.3v-11.6c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v13.2c0 .9.7 1.7 1.7 1.7h46.7c.9 0 1.7-.7 1.7-1.7v-13.2c0-1-.7-1.7-1.7-1.7zm-24.5 6.1c.3.3.8.5 1.2.5.4 0 .9-.2 1.2-.5l10-11.6c.7-.7.7-1.7 0-2.4s-1.7-.7-2.4 0l-7.1 8.3v-25.3c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v25.3l-7.1-8.3c-.7-.7-1.7-.7-2.4 0s-.7 1.7 0 2.4l10 11.6z"/></svg>
							<input type="file" name="files" id="file" class="box__file" accept=".png, .jpg, .jpeg, .gif">
							<label for="file"><strong><?php esc_html_e('Elige un archivo', 'paga-con-zigi'); ?></strong><span class="box__dragndrop"> <?php esc_html_e('o arrástralo aquí', 'paga-con-zigi'); ?></span>.</label>
							<button type="submit" class="box__button"><?php esc_html_e('Seleccionar Archivo', 'paga-con-zigi'); ?></button>
						</div>
						<input type="hidden" name="ajax" value="1">
					</form>
					<div class="error"><?php esc_html_e('Por favor sube tu comprobante', 'paga-con-zigi'); ?></div>

					<div class="submit-wrapper">
						<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Imagen estática del plugin, no requiere attachment ID ?>
						<img src="<?php echo esc_url(plugins_url('/assets/loader.gif', __FILE__)); ?>" class="loader" alt="<?php esc_attr_e( 'Cargando...', 'paga-con-zigi' ); ?>"  width="25" height="25" loading="lazy" />
						<input type="submit" name="final_order" class="finalized_order btn_submit" value="<?php echo esc_attr('Completar Compra', 'paga-con-zigi'); ?>">
					</div>
				</div>
				<div class="zigi-footer">
					Respaldado por Banco Industrial
				</div>
			</div>
		</div>
<?php
	}
}
add_action('wp_footer', 'zigi_payment_popup');

if (!function_exists('zigi_payment_front_script')) {
	function zigi_payment_front_script()
	{

		wp_enqueue_script('zigi_payment_qr', plugins_url('assets/woopro-front.js', __FILE__), array('jquery'), '1.1', false);
		wp_enqueue_style('zigi_payment_qr', plugins_url('assets/woopro-front.css', __FILE__), array(), '1.1');
		wp_localize_script(
			'zigi_payment_qr',
			'kwajaxurl',
			array(
				'ajaxurl' 	=> admin_url('admin-ajax.php'),
				'nonce' 	=> wp_create_nonce('zigi_payment_qr_nonce')
			)
		);

		wp_localize_script(
			'zigi_payment_qr',
			'kwp_translate',
			array(
				'kwp_pqr_btn_continue' => __('Continuar', 'paga-con-zigi'),
				'kwp_pqr_upload_images' => __('Por favor sube solo imágenes', 'paga-con-zigi'),
			)
		);
	}
}
add_action('wp_enqueue_scripts', 'zigi_payment_front_script');

if (!function_exists('zigi_payment_qr_code_upload_dir')) {
	function zigi_payment_qr_code_upload_dir($dirs)
	{
		$custom_subdir = '/zigi-payment-qrcode';

		$new_path = $dirs['basedir'] . $custom_subdir;
		$new_url  = $dirs['baseurl'] . $custom_subdir;

		// Crea la carpeta si no existe (usando WP_Filesystem)
		global $wp_filesystem;
		if (! $wp_filesystem) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if (! $wp_filesystem->is_dir($new_path)) {
			$wp_filesystem->mkdir($new_path);
			$wp_filesystem->put_contents($new_path . '/index.html', '', FS_CHMOD_FILE);
		}

		return array_merge($dirs, array(
			'path'   => $new_path,
			'url'    => $new_url,
			'subdir' => $custom_subdir,
		));
	}
}

if (!function_exists('zigi_payment_qr_code_callback')) {
	function zigi_payment_qr_code_callback()
	{

		$wp_nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!$wp_nonce || !wp_verify_nonce($wp_nonce, 'zigi_payment_qr_nonce')) {
			wp_send_json_error('Nonce inválido.', 403);
		}

		if (!isset($_FILES['files'])) {
			wp_send_json_error('Archivo no recibido.', 400);
		}

		if (
			!isset($_FILES['files']['name']) || !isset($_FILES['files']['type']) ||
			!isset($_FILES['files']['tmp_name']) || !isset($_FILES['files']['error']) ||
			!isset($_FILES['files']['size'])
		) {
			wp_send_json_error('Archivo no recibido.', 400);
		}

		$file = [
			'name'     => sanitize_file_name($_FILES['files']['name']),
			'type'     => sanitize_mime_type($_FILES['files']['type']),
			'tmp_name' => sanitize_text_field($_FILES['files']['tmp_name']),
			'error'    => absint($_FILES['files']['error']),
			'size'     => absint($_FILES['files']['size']),
		];

		if (!is_uploaded_file($file['tmp_name'])) {
			wp_send_json_error('Carga inválida.', 400);
		}

		$check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
		$ext = $check['ext'];

		if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
			wp_send_json_error('error: Tipo de archivo inválido. Solo se permiten JPG, JPEG y PNG.', 400);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		add_filter('upload_dir', 'zigi_payment_qr_code_upload_dir');

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'gif'      => 'image/gif',
			),
		);

		$file_name = pathinfo($file['name'], PATHINFO_FILENAME);
		$new_filename = sanitize_file_name($file_name . '-' . time() . "." . $ext);
		$file['name'] = $new_filename;

		$file_return = wp_handle_upload($file, $overrides);

		remove_filter('upload_dir', 'zigi_payment_qr_code_upload_dir');

		if (isset($file_return['url'])) {
			wp_send_json_success([
				'message' => 'Archivo subido.',
				'url' => esc_url_raw($file_return['url'])
			]);
		} else {
			wp_send_json_error('Falló la carga.', 500);
		}

		wp_die();
	}
}
add_action('wp_ajax_zigi_payment_qr_code', 'zigi_payment_qr_code_callback');
add_action('wp_ajax_nopriv_zigi_payment_qr_code', 'zigi_payment_qr_code_callback');

/* Add meta box for edit order */
if (!function_exists('zigi_payment_meta_box')) {
	function zigi_payment_meta_box()
	{
		if (defined('WC_VERSION') && version_compare(WC_VERSION, '7.0.0', '>=')) {
			add_meta_box('zigi-payment-meta-box', __('Comprobante de Pago QR', 'paga-con-zigi'), 'zigi_payment_meta_box_callback', 'woocommerce_page_wc-orders', 'normal');
		} else {
			add_meta_box('zigi-payment-meta-box', __('Comprobante de Pago QR', 'paga-con-zigi'), 'zigi_payment_meta_box_callback', 'shop_order', 'normal');
		}
	}
}
add_action('add_meta_boxes', 'zigi_payment_meta_box');

/* Meta box callback */
if (!function_exists('zigi_payment_meta_box_callback')) {
	function zigi_payment_meta_box_callback($post)
	{
		$object = $post;
		if (! is_object($post) || ! is_a($post, 'WC_Order')) {
			$object = wc_get_order($post->ID);
		}
		
		if ($object) {
			$zigi_payment_qrcode = $object->get_meta('zigi-payment-qrcode', true);

			if (! empty($zigi_payment_qrcode) && esc_url($zigi_payment_qrcode)) {
				echo '<a href="' . esc_url($zigi_payment_qrcode) . '" target="_blank" loading="lazy">';
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Imagen controlada
				echo '<img src="' . esc_url($zigi_payment_qrcode) . '" alt="Imagen de Pago" width="200" height="200" loading="lazy" />';
				echo '</a>';
			}
		}
	}
}

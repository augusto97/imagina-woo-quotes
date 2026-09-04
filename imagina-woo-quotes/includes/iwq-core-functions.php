<?php
/**
 * Funciones de uso general del plugin.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Aviso cuando WooCommerce no está activo.
 *
 * @return void
 */
function iwq_missing_woocommerce_notice() {
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'Imagina Woo Quotes necesita que WooCommerce esté instalado y activo.', 'imagina-woo-quotes' );
	echo '</p></div>';
}

/**
 * Lee una opción del plugin.
 *
 * Todas las opciones viven bajo el prefijo `iwq_` para no ensuciar la tabla
 * de opciones con nombres genéricos.
 *
 * @param string $key     Clave sin prefijo.
 * @param mixed  $default Valor por defecto si la opción no existe.
 * @return mixed
 */
function iwq_get_option( $key, $default = '' ) {
	$value = get_option( 'iwq_' . $key, null );

	if ( null !== $value ) {
		return $value;
	}

	// Opción nunca guardada: vale el mismo valor por defecto que siembra la
	// instalación, para que el admin y el front cuenten lo mismo. Los
	// textos traducibles obligan a esperar a `init`.
	if ( did_action( 'init' ) ) {
		$defaults = IWQ_Install::get_default_options();

		if ( array_key_exists( $key, $defaults ) ) {
			return $defaults[ $key ];
		}
	}

	return $default;
}

/**
 * Guarda una opción del plugin.
 *
 * @param string $key   Clave sin prefijo.
 * @param mixed  $value Valor a guardar.
 * @return bool
 */
function iwq_update_option( $key, $value ) {
	return update_option( 'iwq_' . $key, $value );
}

/**
 * Comprueba si una opción booleana está activada.
 *
 * @param string $key     Clave sin prefijo.
 * @param bool   $default Valor por defecto.
 * @return bool
 */
function iwq_option_enabled( $key, $default = false ) {
	return 'yes' === iwq_get_option( $key, $default ? 'yes' : 'no' );
}

/**
 * Carga un template permitiendo que el tema lo sobreescriba.
 *
 * Busca en este orden:
 *   1. `{tema-hijo}/imagina-woo-quotes/{$template}`
 *   2. `{tema}/imagina-woo-quotes/{$template}`
 *   3. `{plugin}/templates/{$template}`
 *
 * @param string $template Ruta relativa dentro de templates/.
 * @param array  $args     Variables a extraer en el scope del template.
 * @param bool   $return   Si true devuelve el HTML en vez de imprimirlo.
 * @return string|void
 */
function iwq_get_template( $template, $args = array(), $return = false ) {
	$located = locate_template( array( IWQ_SLUG . '/' . $template ) );

	if ( ! $located ) {
		$located = IWQ_TEMPLATE_PATH . $template;
	}

	/**
	 * Filtra la ruta final del template a cargar.
	 *
	 * @param string $located  Ruta absoluta resuelta.
	 * @param string $template Ruta relativa pedida.
	 * @param array  $args     Argumentos del template.
	 */
	$located = apply_filters( 'iwq_get_template', $located, $template, $args );

	if ( ! is_readable( $located ) ) {
		return $return ? '' : null;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- patrón estándar de templates de WooCommerce.
		extract( $args, EXTR_SKIP );
	}

	if ( $return ) {
		ob_start();
		include $located;
		return ob_get_clean();
	}

	include $located;
}

/**
 * Devuelve los estados de pedido que representan un presupuesto.
 *
 * @param bool $with_prefix Si true devuelve las claves con el prefijo `wc-`.
 * @return string[]
 */
function iwq_get_quote_statuses( $with_prefix = false ) {
	$statuses = array( 'iwq-new', 'iwq-pending', 'iwq-accepted', 'iwq-rejected', 'iwq-expired' );

	if ( $with_prefix ) {
		$statuses = array_map(
			static function ( $status ) {
				return 'wc-' . $status;
			},
			$statuses
		);
	}

	return $statuses;
}

/**
 * Comprueba si un pedido está en alguno de los estados de presupuesto.
 *
 * @param WC_Order|int $order Pedido o su ID.
 * @return bool
 */
function iwq_is_quote( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;

	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	return in_array( $order->get_status(), iwq_get_quote_statuses(), true );
}

/**
 * Devuelve el objeto presupuesto para un pedido.
 *
 * @param WC_Order|int $order Pedido o su ID.
 * @return IWQ_Quote|false
 */
function iwq_get_quote( $order ) {
	$order_id = is_numeric( $order ) ? (int) $order : $order->get_id();

	try {
		return new IWQ_Quote( $order_id );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Genera la URL firmada para que el cliente acepte o rechace un presupuesto.
 *
 * La firma es un HMAC del par (id, acción) con la sal de autenticación de
 * WordPress, así el enlace del email no se puede forjar ni reutilizar para
 * otro pedido.
 *
 * @param WC_Order $order  Pedido.
 * @param string   $action `accept` o `reject`.
 * @return string
 */
function iwq_get_quote_action_url( $order, $action ) {
	if ( ! $order instanceof WC_Order || ! in_array( $action, array( 'accept', 'reject' ), true ) ) {
		return '';
	}

	$args = array(
		'iwq_quote'  => $order->get_id(),
		'iwq_action' => $action,
		'iwq_key'    => $order->get_order_key(),
		'iwq_token'  => iwq_get_quote_action_token( $order, $action ),
	);

	$page_id = (int) iwq_get_option( 'quote_page_id' );
	$base    = $page_id ? get_permalink( $page_id ) : home_url( '/' );

	return add_query_arg( $args, $base );
}

/**
 * Calcula el token HMAC de una acción sobre un presupuesto.
 *
 * @param WC_Order $order  Pedido.
 * @param string   $action `accept` o `reject`.
 * @return string
 */
function iwq_get_quote_action_token( $order, $action ) {
	$payload = $order->get_id() . '|' . $action . '|' . $order->get_order_key();

	return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
}

/**
 * Valida el token recibido en la URL contra el esperado.
 *
 * @param WC_Order $order  Pedido.
 * @param string   $action `accept` o `reject`.
 * @param string   $token  Token recibido.
 * @return bool
 */
function iwq_verify_quote_action_token( $order, $action, $token ) {
	return hash_equals( iwq_get_quote_action_token( $order, $action ), (string) $token );
}

/**
 * Devuelve la etiqueta legible de un estado de presupuesto.
 *
 * @param string $status Estado con o sin prefijo `wc-`.
 * @return string
 */
function iwq_get_status_label( $status ) {
	if ( 0 !== strpos( $status, 'wc-' ) ) {
		$status = 'wc-' . $status;
	}

	$labels = IWQ_Order_Statuses::get_statuses();

	return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
}

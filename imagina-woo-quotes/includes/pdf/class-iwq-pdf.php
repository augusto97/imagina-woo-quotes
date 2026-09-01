<?php
/**
 * Generación del PDF del presupuesto.
 *
 * dompdf solo se carga cuando realmente se genera un documento: es la
 * dependencia más pesada del plugin y no debe entrar en memoria en cada
 * petición.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_PDF
 */
class IWQ_PDF {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_serve_pdf' ) );
	}

	/**
	 * Indica si el PDF se puede generar en esta instalación.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( '\Dompdf\Dompdf' );
	}

	/**
	 * Devuelve el PDF cacheado o lo genera si hace falta.
	 *
	 * @param int  $order_id ID del pedido.
	 * @param bool $force    Si true regenera aunque exista.
	 * @return string|false Ruta al archivo, o false si no se pudo generar.
	 */
	public static function get_or_generate( $order_id, $force = false ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! iwq_is_quote( $order ) ) {
			return false;
		}

		$path = self::get_path( $order );

		// El PDF se invalida cuando el pedido cambia: comparamos su fecha de
		// modificación con la del archivo.
		if ( ! $force && is_readable( $path ) && filemtime( $path ) >= $order->get_date_modified()->getTimestamp() ) {
			return $path;
		}

		return self::generate( $order );
	}

	/**
	 * Genera el PDF y lo guarda en disco.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string|false Ruta al archivo, o false si falló.
	 */
	public static function generate( $order ) {
		if ( ! self::is_available() ) {
			wc_get_logger()->warning(
				'No se pudo generar el PDF: falta la librería dompdf. Ejecuta «composer install» en el directorio del plugin.',
				array( 'source' => 'imagina-woo-quotes' )
			);

			return false;
		}

		$html = self::get_html( $order );
		$path = self::get_path( $order );

		if ( ! wp_mkdir_p( dirname( $path ) ) ) {
			return false;
		}

		try {
			$options = new \Dompdf\Options();
			$options->set( 'isRemoteEnabled', true );
			$options->set( 'isHtml5ParserEnabled', true );
			$options->set( 'defaultFont', iwq_get_option( 'pdf_font', 'DejaVu Sans' ) );
			$options->set( 'chroot', ABSPATH );

			$dompdf = new \Dompdf\Dompdf( $options );

			$dompdf->loadHtml( $html, get_bloginfo( 'charset' ) );
			$dompdf->setPaper(
				iwq_get_option( 'pdf_paper_size', 'A4' ),
				iwq_get_option( 'pdf_orientation', 'portrait' )
			);
			$dompdf->render();

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $path, $dompdf->output() );

			$order->update_meta_data( IWQ_Quote::META_PDF_FILE, basename( $path ) );
			$order->save();

			return $path;
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				'Error al generar el PDF del presupuesto: ' . $e->getMessage(),
				array( 'source' => 'imagina-woo-quotes' )
			);

			return false;
		}
	}

	/**
	 * Construye el HTML completo del documento.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string
	 */
	public static function get_html( $order ) {
		$quote = new IWQ_Quote( $order );

		IWQ_PDF_Blocks::set_context( $quote );

		$content = self::render_template( $order );

		IWQ_PDF_Blocks::set_context( null );

		$html = iwq_get_template(
			'pdf/document.php',
			array(
				'order'   => $order,
				'quote'   => $quote,
				'content' => $content,
				'styles'  => self::get_styles(),
			),
			true
		);

		/**
		 * Filtra el HTML final del PDF antes de renderizarlo.
		 *
		 * @param string   $html  HTML completo del documento.
		 * @param WC_Order $order Pedido.
		 */
		return apply_filters( 'iwq_pdf_html', $html, $order );
	}

	/**
	 * Renderiza los bloques de la plantilla elegida.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string
	 */
	private static function render_template( $order ) {
		$template_id = (int) $order->get_meta( '_iwq_pdf_template_id' );

		if ( ! $template_id ) {
			$template_id = (int) iwq_get_option( 'pdf_template_id' );
		}

		$template = $template_id ? get_post( $template_id ) : null;

		if ( ! $template || IWQ_PDF_Template_CPT::POST_TYPE !== $template->post_type ) {
			// Sin plantilla configurada usamos el diseño de reserva, para que
			// el PDF nunca salga vacío.
			return iwq_get_template( 'pdf/fallback.php', array( 'order' => $order ), true );
		}

		$content = do_blocks( $template->post_content );

		return IWQ_PDF_Placeholders::replace( $content, $order );
	}

	/**
	 * CSS del documento.
	 *
	 * Se inyecta en línea porque dompdf no comparte contexto con el sitio.
	 *
	 * @return string
	 */
	private static function get_styles() {
		$css = file_get_contents( IWQ_DIR . 'assets/css/pdf.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$custom = iwq_get_option( 'pdf_custom_css', '' );

		/**
		 * Filtra el CSS aplicado al PDF.
		 *
		 * @param string $css CSS completo.
		 */
		return apply_filters( 'iwq_pdf_styles', $css . "\n" . wp_strip_all_tags( $custom ) );
	}

	/* ---------------------------------------------------------------------
	 * Archivos y descarga
	 * ------------------------------------------------------------------ */

	/**
	 * Directorio donde se guardan los PDF.
	 *
	 * @return string
	 */
	public static function get_dir() {
		return trailingslashit( IWQ_Uploads::get_base_dir() ) . 'pdf';
	}

	/**
	 * Ruta del PDF de un pedido.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string
	 */
	public static function get_path( $order ) {
		return trailingslashit( self::get_dir() ) . self::get_filename( $order );
	}

	/**
	 * Nombre del archivo, según el patrón configurado.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string
	 */
	public static function get_filename( $order ) {
		$pattern = iwq_get_option( 'pdf_filename', 'presupuesto-{order_number}' );

		$name = strtr(
			$pattern,
			array(
				'{order_number}' => $order->get_order_number(),
				'{order_id}'     => $order->get_id(),
				'{date}'         => gmdate( 'Y-m-d' ),
				'{site_title}'   => sanitize_title( get_bloginfo( 'name' ) ),
			)
		);

		// El hash de la clave del pedido impide adivinar la ruta de otro PDF.
		return sanitize_file_name( $name ) . '-' . substr( md5( $order->get_order_key() ), 0, 8 ) . '.pdf';
	}

	/**
	 * URL de descarga del PDF.
	 *
	 * @param WC_Order $order Pedido.
	 * @return string
	 */
	public static function get_download_url( $order ) {
		return add_query_arg(
			array(
				'iwq_pdf' => $order->get_id(),
				'key'     => $order->get_order_key(),
			),
			home_url( '/' )
		);
	}

	/**
	 * Sirve el PDF si la petición trae credenciales válidas.
	 *
	 * @return void
	 */
	public static function maybe_serve_pdf() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- se valida con la clave del pedido.
		if ( empty( $_GET['iwq_pdf'] ) ) {
			return;
		}

		$order_id = absint( $_GET['iwq_pdf'] );
		$key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		// phpcs:enable

		$order = wc_get_order( $order_id );

		if ( ! $order || ! iwq_is_quote( $order ) ) {
			wp_die( esc_html__( 'Ese presupuesto no existe.', 'imagina-woo-quotes' ), 404 );
		}

		$is_owner = hash_equals( $order->get_order_key(), $key )
			|| ( is_user_logged_in() && get_current_user_id() === $order->get_customer_id() );

		if ( ! $is_owner && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver este presupuesto.', 'imagina-woo-quotes' ), 403 );
		}

		$path = self::get_or_generate( $order_id );

		if ( ! $path || ! is_readable( $path ) ) {
			wp_die( esc_html__( 'No se pudo generar el PDF del presupuesto.', 'imagina-woo-quotes' ), 500 );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}
}

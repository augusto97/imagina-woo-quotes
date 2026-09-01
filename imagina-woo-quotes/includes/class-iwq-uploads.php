<?php
/**
 * Gestión de los archivos que los clientes adjuntan al formulario.
 *
 * Los archivos se guardan fuera del alcance directo del navegador y solo se
 * sirven a través de un endpoint que comprueba permisos: un adjunto de un
 * presupuesto puede contener planos, presupuestos de la competencia o datos
 * personales, y no debe quedar accesible por URL adivinable.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class IWQ_Uploads
 */
class IWQ_Uploads {

	/**
	 * Extensiones admitidas por defecto.
	 *
	 * Deliberadamente conservador: documentos e imágenes, nada ejecutable.
	 *
	 * @var string[]
	 */
	private static $default_extensions = array(
		'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
		'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
		'txt', 'zip', 'dwg', 'dxf',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_serve_file' ) );
	}

	/**
	 * Directorio base donde se guardan los adjuntos.
	 *
	 * @return string
	 */
	public static function get_base_dir() {
		$uploads = wp_upload_dir();

		/**
		 * Filtra el directorio donde se guardan los adjuntos del formulario.
		 *
		 * @param string $dir Ruta absoluta.
		 */
		return apply_filters( 'iwq_uploads_dir', trailingslashit( $uploads['basedir'] ) . 'imagina-quotes' );
	}

	/**
	 * Tamaño máximo admitido para un campo, en bytes.
	 *
	 * @param array $field Definición del campo.
	 * @return int
	 */
	public static function get_max_size( $field = array() ) {
		$max = ! empty( $field['max_size'] ) ? (int) $field['max_size'] * MB_IN_BYTES : 0;

		if ( ! $max ) {
			$max = (int) iwq_get_option( 'upload_max_size', 5 ) * MB_IN_BYTES;
		}

		// Nunca prometemos más de lo que el servidor acepta.
		return min( $max, wp_max_upload_size() );
	}

	/**
	 * Extensiones admitidas para un campo.
	 *
	 * @param array $field Definición del campo.
	 * @return string[]
	 */
	public static function get_allowed_extensions( $field = array() ) {
		$extensions = ! empty( $field['extensions'] )
			? array_map( 'trim', explode( ',', $field['extensions'] ) )
			: self::$default_extensions;

		$extensions = array_filter( array_map( 'strtolower', $extensions ) );

		/**
		 * Filtra las extensiones admitidas en los adjuntos.
		 *
		 * @param string[] $extensions Extensiones sin punto.
		 * @param array    $field      Definición del campo.
		 */
		return apply_filters( 'iwq_allowed_extensions', $extensions, $field );
	}

	/**
	 * Valor del atributo `accept` del input.
	 *
	 * @param array $field Definición del campo.
	 * @return string
	 */
	public static function get_accept_attribute( $field = array() ) {
		$extensions = self::get_allowed_extensions( $field );

		return implode(
			',',
			array_map(
				static function ( $ext ) {
					return '.' . $ext;
				},
				$extensions
			)
		);
	}

	/**
	 * Valida y almacena un archivo subido.
	 *
	 * @param array $file  Entrada de `$_FILES`.
	 * @param array $field Definición del campo.
	 * @return array|WP_Error Datos del archivo guardado, o error.
	 */
	public static function handle( $file, $field ) {
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'iwq_upload_error', __( 'No se pudo subir el archivo. Inténtalo de nuevo.', 'imagina-woo-quotes' ) );
		}

		$max_size = self::get_max_size( $field );

		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'iwq_upload_too_big',
				sprintf(
					/* translators: %s: tamaño máximo legible. */
					__( 'El archivo supera el tamaño máximo de %s.', 'imagina-woo-quotes' ),
					size_format( $max_size )
				)
			);
		}

		$name      = sanitize_file_name( $file['name'] );
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$allowed   = self::get_allowed_extensions( $field );

		if ( ! in_array( $extension, $allowed, true ) ) {
			return new WP_Error(
				'iwq_upload_bad_type',
				sprintf(
					/* translators: %s: lista de extensiones admitidas. */
					__( 'Tipo de archivo no admitido. Se aceptan: %s.', 'imagina-woo-quotes' ),
					implode( ', ', $allowed )
				)
			);
		}

		// Comprobamos el tipo real del contenido, no solo la extensión: un
		// `.jpg` puede contener cualquier cosa.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $name );

		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return new WP_Error( 'iwq_upload_bad_content', __( 'El contenido del archivo no coincide con su extensión.', 'imagina-woo-quotes' ) );
		}

		$dir = trailingslashit( self::get_base_dir() ) . gmdate( 'Y/m' );

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'iwq_upload_dir', __( 'No se pudo preparar el destino del archivo.', 'imagina-woo-quotes' ) );
		}

		// Nombre impredecible: aunque alguien adivine el directorio, no puede
		// enumerar los archivos de otros clientes.
		$stored_name = wp_generate_password( 24, false, false ) . '.' . $extension;
		$destination = trailingslashit( $dir ) . $stored_name;

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new WP_Error( 'iwq_upload_move', __( 'No se pudo guardar el archivo.', 'imagina-woo-quotes' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		@chmod( $destination, 0644 );

		return array(
			'name'      => $name,
			'file'      => gmdate( 'Y/m' ) . '/' . $stored_name,
			'size'      => (int) $file['size'],
			'type'      => $check['type'],
			'token'     => wp_generate_password( 20, false, false ),
			'uploaded'  => time(),
		);
	}

	/**
	 * URL de descarga de un adjunto.
	 *
	 * @param int   $order_id ID del pedido.
	 * @param string $field_id ID del campo.
	 * @return string
	 */
	public static function get_download_url( $order_id, $field_id ) {
		return add_query_arg(
			array(
				'iwq_download' => $order_id,
				'field'        => $field_id,
				'nonce'        => wp_create_nonce( 'iwq_download_' . $order_id ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Sirve un adjunto si la petición trae credenciales válidas.
	 *
	 * @return void
	 */
	public static function maybe_serve_file() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- el nonce se comprueba explícitamente más abajo.
		if ( empty( $_GET['iwq_download'] ) || empty( $_GET['field'] ) ) {
			return;
		}

		$order_id = absint( $_GET['iwq_download'] );
		$field_id = sanitize_key( $_GET['field'] );
		$nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		// phpcs:enable

		if ( ! wp_verify_nonce( $nonce, 'iwq_download_' . $order_id ) ) {
			wp_die( esc_html__( 'Este enlace de descarga ya no es válido.', 'imagina-woo-quotes' ), 403 );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || ! self::user_can_download( $order ) ) {
			wp_die( esc_html__( 'No tienes permiso para descargar este archivo.', 'imagina-woo-quotes' ), 403 );
		}

		$quote = iwq_get_quote( $order );
		$data  = $quote ? $quote->get_form_data() : array();

		if ( empty( $data[ $field_id ]['file'] ) ) {
			wp_die( esc_html__( 'El archivo solicitado no existe.', 'imagina-woo-quotes' ), 404 );
		}

		self::stream( $data[ $field_id ] );
	}

	/**
	 * Comprueba si el usuario actual puede descargar los adjuntos del pedido.
	 *
	 * @param WC_Order $order Pedido.
	 * @return bool
	 */
	private static function user_can_download( $order ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return is_user_logged_in() && get_current_user_id() === $order->get_customer_id();
	}

	/**
	 * Envía el archivo al navegador.
	 *
	 * @param array $data Datos del adjunto.
	 * @return void
	 */
	private static function stream( $data ) {
		$path = trailingslashit( self::get_base_dir() ) . $data['file'];

		// `realpath` más la comprobación de prefijo cierran cualquier intento
		// de salir del directorio con `../`.
		$real = realpath( $path );
		$base = realpath( self::get_base_dir() );

		if ( ! $real || ! $base || 0 !== strpos( $real, $base ) || ! is_readable( $real ) ) {
			wp_die( esc_html__( 'El archivo solicitado no existe.', 'imagina-woo-quotes' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: ' . $data['type'] );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( $data['name'] ) . '"' );
		header( 'Content-Length: ' . filesize( $real ) );
		header( 'X-Content-Type-Options: nosniff' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $real );
		exit;
	}
}

<?php
/**
 * Envoltorio de los emails HTML.
 *
 * Con el diseño «Como WooCommerce» delega en la cabecera y el pie de la
 * tienda; con los demás pinta el suyo. El contenido llega ya renderizado.
 *
 * @package ImaginaWooQuotes
 *
 * @var string   $style         Diseño: moderno, minimal o woocommerce.
 * @var string   $email_heading Encabezado.
 * @var string   $content       HTML del cuerpo.
 * @var WC_Email $email         Email en curso.
 */

defined( 'ABSPATH' ) || exit;

if ( 'woocommerce' === $style ) {
	do_action( 'woocommerce_email_header', $email_heading, $email );
	echo '<div class="iwq-email-body"><div class="iwq-email-content">' . $content . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado por las partes.
	do_action( 'woocommerce_email_footer', $email );
	return;
}

$iwq_logo = IWQ_Email_Styles::get_logo_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<title><?php echo esc_html( $email_heading ); ?></title>
</head>
<body style="margin:0;padding:0;">
	<div class="iwq-email-body">
		<table class="iwq-email-outer" width="100%" cellpadding="0" cellspacing="0" role="presentation">
			<tr>
				<td align="center">
					<table class="iwq-email-card" cellpadding="0" cellspacing="0" role="presentation">
						<?php if ( 'moderno' === $style ) : ?>
							<tr><td class="iwq-email-accent" style="font-size:0;line-height:0;">&nbsp;</td></tr>
						<?php endif; ?>
						<tr>
							<td class="iwq-email-head">
								<?php if ( $iwq_logo ) : ?>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $iwq_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></a>
								<?php else : ?>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="font-weight:700;font-size:18px;text-decoration:none;color:#1f2937;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td class="iwq-email-content">
								<h1 class="iwq-h1"><?php echo esc_html( $email_heading ); ?></h1>
								<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado por las partes. ?>
							</td>
						</tr>
					</table>
					<div class="iwq-footer"><?php echo wp_kses_post( wpautop( IWQ_Email_Styles::get_footer_text() ) ); ?></div>
				</td>
			</tr>
		</table>
	</div>
</body>
</html>

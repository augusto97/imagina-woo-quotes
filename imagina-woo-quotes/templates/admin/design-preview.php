<?php
/**
 * Vista previa de la pestaña Diseño.
 *
 * Se pinta dentro de un iframe con la hoja real del front, aislado del CSS
 * del admin. El script de la pestaña vuelca los ajustes como variables CSS
 * sobre el documento del iframe, así que lo que se ve es lo que saldrá.
 *
 * @package ImaginaWooQuotes
 */

defined( 'ABSPATH' ) || exit;

$iwq_css_url = add_query_arg( 'ver', IWQ_VERSION, IWQ_URL . 'assets/css/frontend.css' );
$iwq_thumb   = wc_placeholder_img_src( 'woocommerce_thumbnail' );
$iwq_label   = iwq_get_option( 'button_label', __( 'Solicitar presupuesto', 'imagina-woo-quotes' ) );
$iwq_submit  = iwq_get_option( 'submit_label', __( 'Enviar solicitud', 'imagina-woo-quotes' ) );

ob_start();
?>
<!doctype html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="<?php echo esc_url( $iwq_css_url ); ?>">
<style>
	html, body { margin: 0; background: transparent; }
	body { font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #1f2937; }
	.stage { display: grid; grid-template-columns: minmax( 0, 1fr ) 360px; gap: 24px; padding: 24px; }
	.stage > * { min-width: 0; }
	.card { padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
	.card h4 { margin: 0 0 6px; font-size: 16px; }
	.card .price { margin: 0 0 14px; color: #6b7280; }
	.card .hint { margin: 14px 0 0; font-size: 12px; color: #9ca3af; }
	.dummy { display: inline-block; padding: 0.7em 1.4em; border-radius: 8px; background: #111827; color: #fff; font-weight: 600; margin-right: 8px; }
	.iwq-drawer { position: relative; inset: auto; z-index: 1; height: 100%; }
	.iwq-drawer__panel { position: relative; width: 100%; height: auto; min-height: 380px; border-radius: 10px; border: 1px solid #e5e7eb; transform: none !important; }
	.iwq-drawer .button { padding: 0.7em 1em; border-radius: 4px; background: #111827; color: #fff; font-weight: 500; font-size: 14px; text-decoration: none; cursor: pointer; }
	.iwq-drawer__overlay { display: none; }
	.iwq-quote-page__form { margin-top: 20px; }
	.iwq-quote-page__form h3 { margin: 0 0 12px; font-size: 16px; }
	@media ( max-width: 760px ) { .stage { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="iwq stage">
	<div>
		<div class="card">
			<h4><?php esc_html_e( 'Silla de roble', 'imagina-woo-quotes' ); ?></h4>
			<p class="price">120,00 €</p>
			<span class="dummy"><?php esc_html_e( 'Añadir al carrito', 'imagina-woo-quotes' ); ?></span>
			<button type="button" class="iwq-add-button iwq-add-button--single" data-preview="button"><span class="iwq-add-button__spinner" aria-hidden="true"></span><span class="iwq-add-button__label"><?php echo esc_html( $iwq_label ); ?></span></button>
			<p class="hint"><?php esc_html_e( 'Así se verá junto al botón de compra de tu tema.', 'imagina-woo-quotes' ); ?></p>
		</div>

		<div class="iwq-quote-page iwq-quote-page--card" data-preview="page">
			<div class="iwq-quote-page__layout">
				<div class="iwq-quote-page__form">
					<h3><?php esc_html_e( 'Cuéntanos qué necesitas', 'imagina-woo-quotes' ); ?></h3>
					<div class="iwq-form-grid">
						<div class="iwq-field iwq-field--half"><label class="iwq-field__label"><?php esc_html_e( 'Nombre', 'imagina-woo-quotes' ); ?> <abbr class="iwq-field__required">*</abbr></label><input class="iwq-field__control" type="text" value="Ana García"></div>
						<div class="iwq-field iwq-field--half"><label class="iwq-field__label"><?php esc_html_e( 'Email', 'imagina-woo-quotes' ); ?> <abbr class="iwq-field__required">*</abbr></label><input class="iwq-field__control" type="text" value="ana@ejemplo.com"></div>
						<div class="iwq-field"><label class="iwq-field__label"><?php esc_html_e( 'Mensaje', 'imagina-woo-quotes' ); ?></label><textarea class="iwq-field__control" rows="2"><?php esc_html_e( 'Necesito 12 unidades para una oficina.', 'imagina-woo-quotes' ); ?></textarea></div>
					</div>
					<p style="margin:14px 0 0"><button type="button" class="iwq-add-button iwq-submit" data-preview="submit"><span class="iwq-add-button__spinner" aria-hidden="true"></span><span class="iwq-add-button__label"><?php echo esc_html( $iwq_submit ); ?></span></button> <button type="button" class="iwq-link-button"><?php esc_html_e( 'Vaciar la lista', 'imagina-woo-quotes' ); ?></button></p>
				</div>
			</div>
		</div>
	</div>

	<div class="iwq-drawer woocommerce iwq-drawer--compact is-open" data-preview="drawer">
		<div class="iwq-drawer__overlay"></div>
		<div class="iwq-drawer__panel">
			<div class="iwq-drawer__header">
				<h2 class="iwq-drawer__title"><span data-preview="drawer-title"><?php esc_html_e( 'Tu presupuesto', 'imagina-woo-quotes' ); ?></span> <span class="iwq-drawer__count"><?php esc_html_e( '(3 productos)', 'imagina-woo-quotes' ); ?></span></h2>
				<button type="button" class="iwq-drawer__close" aria-label="<?php esc_attr_e( 'Cerrar', 'imagina-woo-quotes' ); ?>"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
			</div>
			<div class="iwq-drawer__body">
				<table class="iwq-mini-items"><tbody>
					<tr class="iwq-mini-item">
						<td class="iwq-mini-item__image"><a href="#"><img src="<?php echo esc_url( $iwq_thumb ); ?>" alt=""></a></td>
						<td class="iwq-mini-item__product"><div class="iwq-mini-item__wrap"><a class="iwq-mini-item__name" href="#"><?php esc_html_e( 'Silla de roble', 'imagina-woo-quotes' ); ?></a><div class="iwq-mini-item__price">120,00 €</div><div class="iwq-mini-item__controls"><div class="iwq-qty"><input type="number" class="iwq-qty__input iwq-quantity" value="2" min="1"><button type="button" class="iwq-qty__button iwq-qty__button--minus">&minus;</button><button type="button" class="iwq-qty__button iwq-qty__button--plus">&#xFF0B;</button></div><button type="button" class="iwq-mini-item__remove"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg></button></div></div></td>
						<td class="iwq-mini-item__total">240,00 €</td>
					</tr>
					<tr class="iwq-mini-item">
						<td class="iwq-mini-item__image"><a href="#"><img src="<?php echo esc_url( $iwq_thumb ); ?>" alt=""></a></td>
						<td class="iwq-mini-item__product"><div class="iwq-mini-item__wrap"><a class="iwq-mini-item__name" href="#"><?php esc_html_e( 'Mesa extensible', 'imagina-woo-quotes' ); ?></a><div class="iwq-mini-item__price">650,00 €</div><div class="iwq-mini-item__meta">180 cm</div><div class="iwq-mini-item__controls"><div class="iwq-qty"><input type="number" class="iwq-qty__input iwq-quantity" value="1" min="1"><button type="button" class="iwq-qty__button iwq-qty__button--minus">&minus;</button><button type="button" class="iwq-qty__button iwq-qty__button--plus">&#xFF0B;</button></div><button type="button" class="iwq-mini-item__remove"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg></button></div></div></td>
						<td class="iwq-mini-item__total">650,00 €</td>
					</tr>
				</tbody></table>
			</div>
			<div class="iwq-drawer__footer">
				<div class="iwq-drawer__subtotal"><span><?php esc_html_e( 'Subtotal', 'imagina-woo-quotes' ); ?></span><span>890,00 €</span><div class="iwq-drawer__subtotal-desc"><?php esc_html_e( 'Precios de catálogo, orientativos. Te confirmaremos el presupuesto.', 'imagina-woo-quotes' ); ?></div></div>
				<div class="iwq-drawer__actions iwq-drawer__actions--stacked"><button type="button" class="button wp-element-button iwq-drawer__continue"><?php esc_html_e( 'Seguir viendo productos', 'imagina-woo-quotes' ); ?></button><a class="button alt wp-element-button iwq-drawer__submit" href="#" data-preview="drawer-footer"><?php esc_html_e( 'Ver y enviar la solicitud', 'imagina-woo-quotes' ); ?></a></div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
<?php
$iwq_doc = ob_get_clean();
?>
<div class="iwq-design-preview" id="iwq-design-preview">
	<iframe
		id="iwq-design-preview-frame"
		title="<?php esc_attr_e( 'Vista previa del diseño', 'imagina-woo-quotes' ); ?>"
		srcdoc="<?php echo esc_attr( $iwq_doc ); ?>"
		sandbox="allow-same-origin"
	></iframe>
</div>

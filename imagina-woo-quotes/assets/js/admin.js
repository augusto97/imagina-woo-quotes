/**
 * Imagina Woo Quotes — administración.
 *
 * Solo se carga en la página de ajustes del plugin.
 */
( function ( $ ) {
	'use strict';

	var settings = window.iwqAdmin || {};
	var i18n = settings.i18n || {};

	/* ------------------------------------------------------------------
	 * Constructor de formularios
	 * --------------------------------------------------------------- */

	var $builder = $( '.iwq-builder' );

	if ( $builder.length ) {
		var $list = $builder.find( '.iwq-builder__list' );

		$list.sortable( {
			handle: '.iwq-builder__handle',
			placeholder: 'iwq-builder__placeholder',
			forcePlaceholderSize: true,
			update: reindex
		} );

		// Abrir y cerrar el panel de un campo.
		$builder.on( 'click', '.iwq-builder__toggle', function () {
			var $body = $( this ).closest( '.iwq-builder__item' ).find( '.iwq-builder__body' );
			var isOpen = ! $body.prop( 'hidden' );

			$body.prop( 'hidden', isOpen );
			$( this ).attr( 'aria-expanded', ! isOpen );
		} );

		// El título del encabezado sigue a la etiqueta mientras se escribe.
		$builder.on( 'input', '.iwq-builder__label-input', function () {
			var value = $( this ).val();

			$( this )
				.closest( '.iwq-builder__item' )
				.find( '.iwq-builder__title' )
				.text( value || i18n.newField );
		} );

		// Cambiar el tipo muestra u oculta los ajustes que le corresponden.
		$builder.on( 'change', '.iwq-builder__type-select', function () {
			var type = $( this ).val();
			var $item = $( this ).closest( '.iwq-builder__item' );
			var hasOptions = [ 'select', 'multiselect', 'radio', 'checkbox' ].indexOf( type ) !== -1;

			$item.find( '.iwq-builder__options' ).prop( 'hidden', ! hasOptions );
			$item.find( '.iwq-builder__file' ).prop( 'hidden', type !== 'file' );

			var label = settings.fieldTypes && settings.fieldTypes[ type ]
				? settings.fieldTypes[ type ].label
				: type;

			$item.find( '.iwq-builder__type' ).text( label );
		} );

		// Un campo desactivado se atenúa, para verlo de un vistazo.
		$builder.on( 'change', '.iwq-builder__enabled', function () {
			$( this )
				.closest( '.iwq-builder__item' )
				.toggleClass( 'is-disabled', ! $( this ).prop( 'checked' ) );
		} );

		$builder.on( 'click', '.iwq-builder__delete', function () {
			if ( ! window.confirm( i18n.confirmDelete ) ) {
				return;
			}

			$( this ).closest( '.iwq-builder__item' ).remove();
			reindex();
		} );

		$builder.on( 'click', '.iwq-builder__add', function () {
			var template = $( '#tmpl-iwq-builder-field' ).html();

			if ( ! template ) {
				return;
			}

			var index = $list.children().length;
			var id = 'field_' + Date.now().toString( 36 );

			var html = template
				.split( '__NAME__' ).join( $builder.data( 'name' ) )
				.split( '__INDEX__' ).join( index )
				.split( '__ID__' ).join( id );

			$list.append( html );
			$list.children().last()[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
		} );
	}

	/**
	 * Renumera los campos tras reordenar o borrar, para que los nombres del
	 * formulario sigan siendo consecutivos.
	 */
	function reindex() {
		$( '.iwq-builder__list' ).children().each( function ( index ) {
			$( this ).attr( 'data-index', index );

			$( this ).find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' );

				$( this ).attr( 'name', name.replace( /\[\d+\]/, '[' + index + ']' ) );
			} );
		} );
	}

	/* ------------------------------------------------------------------
	 * Selector de color
	 * --------------------------------------------------------------- */

	if ( $.fn.wpColorPicker ) {
		$( '.iwq-color-field' ).wpColorPicker();
	}

	/* ------------------------------------------------------------------
	 * Vista previa de emails y PDF
	 * --------------------------------------------------------------- */

	var $preview = $( '.iwq-preview' );

	if ( $preview.length ) {
		var ajax = $preview.data( 'ajax' );
		var nonce = $preview.data( 'nonce' );
		var view = 'html';
		var $frame = $( '#iwq-preview-iframe' );
		var $meta = $( '#iwq-preview-meta' );
		var $result = $( '#iwq-preview-result' );

		function params( extra ) {
			return $.extend( {
				nonce: nonce,
				email: $( '#iwq-preview-email' ).val(),
				order: $( '#iwq-preview-order' ).val()
			}, extra || {} );
		}

		function refresh() {
			if ( ! $( '#iwq-preview-order' ).val() ) {
				$frame.attr( 'srcdoc', '<p style="font-family:sans-serif;color:#666;padding:24px">' + i18n.noOrder + '</p>' );
				$meta.prop( 'hidden', true );
				return;
			}

			$frame.removeAttr( 'srcdoc' );

			$.getJSON( ajax, params( { action: 'iwq_preview_meta' } ), function ( response ) {
				if ( ! response.success ) {
					$result.text( response.data.message );
					return;
				}

				var d = response.data;

				$meta.prop( 'hidden', false );
				$meta.find( '[data-meta="subject"]' ).text( d.subject );
				$meta.find( '[data-meta="from"]' ).text( d.from );
				$meta.find( '[data-meta="to"]' ).text( d.to );
				$meta.find( '[data-meta="status"]' ).text( d.status );
				$meta.find( '[data-meta="attachments"]' ).text(
					d.attachments.length ? d.attachments.map( function ( a ) { return a.name + ' (' + a.size + ')'; } ).join( ', ' ) : i18n.noAttachments
				);
				$meta.find( '[data-meta="disabled"]' ).prop( 'hidden', d.enabled );
				$preview.data( 'pdfUrl', d.pdf_url );

				if ( view === 'pdf' ) {
					$frame.attr( 'src', d.pdf_url || 'about:blank' );
				} else {
					$frame.attr( 'src', ajax + '?' + $.param( params( { action: 'iwq_preview_email', format: view } ) ) );
				}
			} );
		}

		$preview.on( 'change', '#iwq-preview-email, #iwq-preview-order', refresh );

		$preview.on( 'click', '.iwq-preview__view button', function () {
			view = $( this ).data( 'view' );
			$( '.iwq-preview__view button' ).removeClass( 'button-primary is-active' );
			$( this ).addClass( 'button-primary is-active' );
			refresh();
		} );

		$preview.on( 'click', '#iwq-preview-sample', function () {
			var $button = $( this ).prop( 'disabled', true );

			$.post( ajax, { action: 'iwq_create_sample', nonce: nonce }, function ( response ) {
				$button.prop( 'disabled', false );

				if ( ! response.success ) {
					$result.text( response.data.message );
					return;
				}

				$( '#iwq-preview-order' )
					.find( 'option[value=""]' ).remove().end()
					.prepend( $( '<option>', { value: response.data.id, text: response.data.label } ) )
					.val( response.data.id );

				refresh();
			} );
		} );

		$preview.on( 'click', '#iwq-preview-send', function () {
			var $button = $( this ).prop( 'disabled', true );

			$result.text( i18n.sending );

			$.post( ajax, params( { action: 'iwq_send_test_email', to: $( '#iwq-preview-to' ).val() } ), function ( response ) {
				$button.prop( 'disabled', false );
				$result.text( response.data.message );
			} );
		} );

		refresh();
	}

	/* ------------------------------------------------------------------
	 * Selector de imagen
	 * --------------------------------------------------------------- */

	$( document ).on( 'click', '.iwq-media-select', function ( event ) {
		event.preventDefault();

		var $field = $( this ).closest( '.iwq-media-field' );

		var frame = window.wp.media( {
			title: $( this ).text(),
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			$field.find( 'input[type="hidden"]' ).val( attachment.id );
			$field.find( '.iwq-media-field__preview' ).html( '<img src="' + url + '" alt="">' );
			$field.find( '.iwq-media-clear' ).prop( 'hidden', false );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.iwq-media-clear', function ( event ) {
		event.preventDefault();

		var $field = $( this ).closest( '.iwq-media-field' );

		$field.find( 'input[type="hidden"]' ).val( '' );
		$field.find( '.iwq-media-field__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );
}( window.jQuery ) );

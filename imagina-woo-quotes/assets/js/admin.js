/**
 * Imagina Woo Quotes — administración.
 *
 * Solo se carga en la página de ajustes del plugin.
 */
( function ( $ ) {
	'use strict';

	var settings = window.iwqAdmin || {};
	var i18n = settings.i18n || {};

	// WordPress limpia settings-updated de la URL con un script en línea que
	// corre antes que este, así que la marca llega en el DOM.
	var justSaved = $( '.iwq-app' ).data( 'saved' ) === 1;

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
			$( '.iwq-preview__view button' ).removeClass( 'is-active' ).attr( 'aria-pressed', 'false' );
			$( this ).addClass( 'is-active' ).attr( 'aria-pressed', 'true' );
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
	 * Barra de guardado, avisos y atajos
	 * --------------------------------------------------------------- */

	var $app = $( '.iwq-app' );
	var $form = $( '#iwq-settings-form' );
	var $status = $( '.iwq-savebar__status' );
	var $toast = $( '.iwq-toast' );
	var toastTimer = null;
	var dirty = false;
	var submitting = false;

	/**
	 * Muestra un aviso flotante unos segundos.
	 *
	 * @param {string} message Texto.
	 * @param {string} type    success | error.
	 */
	function toast( message, type ) {
		if ( ! $toast.length ) {
			return;
		}

		window.clearTimeout( toastTimer );

		var icon = type === 'error'
			? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h0"/></svg>'
			: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>';

		$toast
			.attr( 'class', 'iwq-toast iwq-toast--' + ( type || 'success' ) )
			.html( icon + '<span></span>' )
			.find( 'span' ).text( message ).end()
			.prop( 'hidden', false );

		// Dos fotogramas: primero existe, luego se anima.
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				$toast.addClass( 'is-visible' );
			} );
		} );

		toastTimer = window.setTimeout( function () {
			$toast.removeClass( 'is-visible' );
			window.setTimeout( function () {
				$toast.prop( 'hidden', true );
			}, 200 );
		}, 3500 );
	}

	/**
	 * Los avisos que imprime WordPress tras guardar se convierten en un
	 * aviso flotante; los errores de saneado se quedan visibles en la página.
	 */
	function absorbNotices() {
		var $notices = $app.children( '.notice, .updated' );

		$notices.each( function () {
			var $notice = $( this );
			var text = $.trim( $notice.text() );

			if ( $notice.hasClass( 'notice-error' ) || $notice.hasClass( 'error' ) ) {
				toast( text, 'error' );
				return;
			}

			if ( $notice.hasClass( 'notice-success' ) || $notice.hasClass( 'updated' ) || $notice.attr( 'id' ) === 'setting-error-settings_updated' ) {
				toast( text || i18n.saved, 'success' );
				$notice.remove();
			}
		} );

		// Fuera del menú «Ajustes» WordPress no imprime el aviso de guardado:
		// solo deja settings-updated=true en la URL, así que lo anunciamos aquí.
		if ( justSaved && ! $toast.hasClass( 'is-visible' ) ) {
			toast( i18n.saved, 'success' );
		}

		// Limpia la URL para que recargar no vuelva a anunciar el guardado.
		if ( window.history.replaceState && /[?&]settings-updated=/.test( window.location.search ) ) {
			var clean = window.location.href.replace( /([?&])settings-updated=[^&]*&?/, '$1' ).replace( /[?&]$/, '' );
			window.history.replaceState( null, '', clean );
		}
	}

	/**
	 * Marca el formulario como modificado y lo refleja en la barra.
	 */
	function setDirty( value ) {
		dirty = value;
		$status.toggleClass( 'is-dirty', dirty ).text( dirty ? i18n.unsaved : i18n.noChanges );
	}

	if ( $form.length ) {
		// WordPress mueve los avisos tras el h1 de .wrap con un pequeño
		// retraso; esperamos a que lo haga antes de leerlos.
		window.setTimeout( absorbNotices, 50 );

		$form.on( 'change input', 'input, select, textarea', function () {
			if ( ! dirty ) {
				setDirty( true );
			}
		} );

		// Reordenar campos del constructor también cuenta como cambio.
		$form.on( 'sortupdate', function () {
			setDirty( true );
		} );

		$form.on( 'submit', function () {
			submitting = true;
			$status.removeClass( 'is-dirty' ).text( i18n.saving );
			$form.find( '.iwq-savebar .iwq-btn--primary' ).prop( 'disabled', true );
		} );

		$( document ).on( 'keydown', function ( event ) {
			if ( ( event.ctrlKey || event.metaKey ) && ! event.altKey && String( event.key ).toLowerCase() === 's' ) {
				event.preventDefault();
				$form.trigger( 'submit' );
			}
		} );

		$( window ).on( 'beforeunload', function ( event ) {
			if ( dirty && ! submitting ) {
				event.preventDefault();
				event.returnValue = i18n.leave;
				return i18n.leave;
			}
		} );
	} else {
		window.setTimeout( absorbNotices, 50 );
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

			$field.find( 'input[type="hidden"]' ).val( attachment.id ).trigger( 'change' );
			$field.find( '.iwq-media-field__preview' ).html( '<img src="' + url + '" alt="">' );
			$field.find( '.iwq-media-clear' ).prop( 'hidden', false );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.iwq-media-clear', function ( event ) {
		event.preventDefault();

		var $field = $( this ).closest( '.iwq-media-field' );

		$field.find( 'input[type="hidden"]' ).val( '' ).trigger( 'change' );
		$field.find( '.iwq-media-field__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );
}( window.jQuery ) );

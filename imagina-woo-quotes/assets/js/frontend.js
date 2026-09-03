/**
 * Imagina Woo Quotes — front.
 *
 * Sin dependencias: ni jQuery ni ninguna librería. Todo el estado del
 * servidor llega en `iwqData`; el contador se refleja en localStorage para
 * que las páginas cacheadas puedan pintarlo sin una petición de red.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'iwq_quote_state';

	// Solo los botones que llevan un producto son «añadir a la lista». Otros
	// elementos comparten la clase por estilo (enviar, aceptar, enlaces) y no
	// deben tocarse.
	var ADD_SELECTOR = '.iwq-add-button[data-product-id]';

	var settings = window.iwqData || {};
	var i18n = settings.i18n || {};

	/* ------------------------------------------------------------------
	 * Utilidades
	 * --------------------------------------------------------------- */

	/**
	 * Construye la URL de un endpoint AJAX de WooCommerce.
	 *
	 * @param {string} action Nombre de la acción.
	 * @return {string} URL completa.
	 */
	function endpoint( action ) {
		return ( settings.ajaxUrl || '' ).replace( '%%endpoint%%', action );
	}

	/**
	 * Envía una petición al servidor.
	 *
	 * @param {string} action Acción de WooCommerce.
	 * @param {Object} data   Pares clave/valor a enviar.
	 * @return {Promise<Object>} Cuerpo `data` de la respuesta.
	 */
	function post( action, data ) {
		var body = new FormData();

		body.append( 'nonce', settings.nonce );

		Object.keys( data || {} ).forEach( function ( key ) {
			var value = data[ key ];

			if ( value === null || value === undefined ) {
				return;
			}

			if ( typeof value === 'object' && ! ( value instanceof File ) ) {
				Object.keys( value ).forEach( function ( sub ) {
					body.append( key + '[' + sub + ']', value[ sub ] );
				} );
				return;
			}

			body.append( key, value );
		} );

		return fetch( endpoint( action ), {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		} )
			.then( function ( response ) {
				return response.json().then( function ( json ) {
					if ( ! response.ok || ! json.success ) {
						var error = new Error( ( json.data && json.data.message ) || i18n.error );
						error.data = json.data || {};
						throw error;
					}

					return json.data;
				} );
			} );
	}

	/**
	 * Lee el estado guardado en el navegador.
	 *
	 * @return {{count: number, ids: number[]}} Estado conocido.
	 */
	function readState() {
		try {
			return JSON.parse( localStorage.getItem( STORAGE_KEY ) ) || { count: 0, ids: [] };
		} catch ( e ) {
			return { count: 0, ids: [] };
		}
	}

	/**
	 * Guarda el estado en el navegador.
	 *
	 * @param {Object} state Estado a persistir.
	 */
	function writeState( state ) {
		try {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
		} catch ( e ) {
			// Modo privado o almacenamiento lleno: seguimos sin persistir.
		}
	}

	/* ------------------------------------------------------------------
	 * Avisos accesibles
	 * --------------------------------------------------------------- */

	var liveRegion = null;

	/**
	 * Muestra un aviso flotante y lo anuncia a los lectores de pantalla.
	 *
	 * @param {string} message Texto del aviso.
	 * @param {string} type    `success` o `error`.
	 */
	function notify( message, type ) {
		if ( ! liveRegion ) {
			liveRegion = document.createElement( 'div' );
			liveRegion.className = 'iwq-toasts';
			liveRegion.setAttribute( 'role', 'status' );
			liveRegion.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( liveRegion );
		}

		var toast = document.createElement( 'div' );

		toast.className = 'iwq-toast iwq-toast--' + ( type || 'success' );
		toast.textContent = message;
		liveRegion.appendChild( toast );

		// Forzamos un reflow para que la transición de entrada se ejecute.
		void toast.offsetWidth;
		toast.classList.add( 'is-visible' );

		setTimeout( function () {
			toast.classList.remove( 'is-visible' );
			toast.addEventListener( 'transitionend', function () {
				toast.remove();
			}, { once: true } );
		}, 4000 );
	}

	/* ------------------------------------------------------------------
	 * Contador y botones
	 * --------------------------------------------------------------- */

	/**
	 * Refleja el estado actual en los contadores y botones de la página.
	 *
	 * @param {Object} state Estado con `count` e `ids`.
	 */
	function syncUI( state ) {
		document.querySelectorAll( '.iwq-count' ).forEach( function ( node ) {
			node.textContent = state.count;
			node.hidden = state.count === 0;
		} );

		// Contador del título y subtotal del panel, como el mini carrito.
		var quantity = typeof state.quantity === 'number' ? state.quantity : state.count;

		document.querySelectorAll( '.iwq-drawer__count' ).forEach( function ( node ) {
			node.textContent = quantity === 1 ? i18n.countOne : String( i18n.countMany || '(%d)' ).replace( '%d', quantity );
			node.hidden = ! state.count;
		} );

		if ( typeof state.total === 'string' ) {
			document.querySelectorAll( '.iwq-drawer__subtotal' ).forEach( function ( node ) {
				node.hidden = state.total === '';
				node.querySelector( '.iwq-drawer__subtotal-value' ).innerHTML = state.total;
			} );
		}

		refreshEmptyState( state.count );

		document.querySelectorAll( ADD_SELECTOR ).forEach( function ( button ) {
			var id = parseInt( button.dataset.productId, 10 );

			setButtonAdded( button, state.ids.indexOf( id ) !== -1 );
		} );
	}

	/**
	 * Cambia un botón entre «añadir» y «ya añadido».
	 *
	 * @param {HTMLElement} button Botón.
	 * @param {boolean}     added  Si el producto está en la lista.
	 */
	function setButtonAdded( button, added ) {
		var label = button.querySelector( '.iwq-add-button__label' ) || button;

		button.classList.toggle( 'is-added', added );
		label.textContent = added ? i18n.added : ( button.dataset.label || i18n.add );

		if ( added ) {
			button.setAttribute( 'href', settings.quoteUrl || '#' );
		}
	}

	/**
	 * Añade un producto a la lista.
	 *
	 * @param {HTMLElement} button Botón pulsado.
	 */
	function addToQuote( button ) {
		if ( button.classList.contains( 'is-loading' ) ) {
			return;
		}

		// Si ya está en la lista, el botón hace de enlace a la página.
		if ( button.classList.contains( 'is-added' ) && settings.quoteUrl ) {
			window.location.href = settings.quoteUrl;
			return;
		}

		var form = button.closest( 'form.cart' );
		var quantityInput = form && form.querySelector( 'input.qty' );
		var variationInput = form && form.querySelector( 'input[name="variation_id"]' );

		var payload = {
			product_id: button.dataset.productId,
			quantity: quantityInput ? quantityInput.value : 1,
			variation_id: variationInput ? variationInput.value : 0
		};

		// Recogemos los atributos elegidos de un producto variable.
		if ( form ) {
			form.querySelectorAll( '[name^="attribute_"]' ).forEach( function ( input ) {
				payload[ 'variation[' + input.name + ']' ] = input.value;
			} );
		}

		button.classList.add( 'is-loading' );
		button.setAttribute( 'aria-busy', 'true' );

		post( 'iwq_add_item', payload )
			.then( function ( data ) {
				writeState( { count: data.count, ids: data.ids } );
				syncUI( data );
				notify( i18n.itemAdded, 'success' );

				if ( settings.redirect && settings.quoteUrl ) {
					window.location.href = settings.quoteUrl;
					return;
				}

				if ( settings.openAfterAdd ) {
					openDrawer();
				}
			} )
			.catch( function ( error ) {
				notify( error.message || i18n.error, 'error' );
			} )
			.finally( function () {
				button.classList.remove( 'is-loading' );
				button.removeAttribute( 'aria-busy' );
			} );
	}

	/* ------------------------------------------------------------------
	 * Panel lateral
	 * --------------------------------------------------------------- */

	var drawer = null;
	var lastFocused = null;

	/**
	 * Abre el panel lateral y carga su contenido.
	 */
	function openDrawer() {
		drawer = drawer || document.getElementById( 'iwq-drawer' );

		if ( ! drawer ) {
			if ( settings.quoteUrl ) {
				window.location.href = settings.quoteUrl;
			}
			return;
		}

		lastFocused = document.activeElement;

		drawer.hidden = false;
		document.body.classList.add( 'iwq-drawer-open' );

		// Un reflow antes de añadir la clase permite animar la entrada.
		void drawer.offsetWidth;
		drawer.classList.add( 'is-open' );

		loadDrawerContent();

		var closeButton = drawer.querySelector( '.iwq-drawer__close' );

		if ( closeButton ) {
			closeButton.focus();
		}

		document.addEventListener( 'keydown', onDrawerKeydown );
	}

	/**
	 * Cierra el panel lateral y devuelve el foco a su origen.
	 */
	function closeDrawer() {
		if ( ! drawer || drawer.hidden ) {
			return;
		}

		drawer.classList.remove( 'is-open' );
		document.body.classList.remove( 'iwq-drawer-open' );
		document.removeEventListener( 'keydown', onDrawerKeydown );

		var hide = function () {
			drawer.hidden = true;
		};

		// Esperamos a que termine la animación, con un tope por si el
		// usuario ha desactivado las transiciones.
		var panel = drawer.querySelector( '.iwq-drawer__panel' );

		if ( panel && window.getComputedStyle( panel ).transitionDuration !== '0s' ) {
			panel.addEventListener( 'transitionend', hide, { once: true } );
		} else {
			hide();
		}

		if ( lastFocused ) {
			lastFocused.focus();
			lastFocused = null;
		}
	}

	/**
	 * Gestiona Escape y el ciclo de tabulación dentro del panel.
	 *
	 * @param {KeyboardEvent} event Evento de teclado.
	 */
	function onDrawerKeydown( event ) {
		if ( event.key === 'Escape' ) {
			closeDrawer();
			return;
		}

		if ( event.key !== 'Tab' ) {
			return;
		}

		var focusables = drawer.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])'
		);

		if ( ! focusables.length ) {
			return;
		}

		var first = focusables[ 0 ];
		var last = focusables[ focusables.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/**
	 * Pide al servidor el contenido del panel.
	 */
	function loadDrawerContent() {
		var body = drawer.querySelector( '.iwq-drawer__body' );

		if ( ! body ) {
			return;
		}

		body.setAttribute( 'aria-busy', 'true' );

		post( 'iwq_get_list', {} )
			.then( function ( data ) {
				body.innerHTML = data.html || '';
				writeState( { count: data.count, ids: data.ids } );
				syncUI( data );
			} )
			.catch( function () {
				body.textContent = i18n.error;
			} )
			.finally( function () {
				body.removeAttribute( 'aria-busy' );
			} );
	}

	/* ------------------------------------------------------------------
	 * Operaciones sobre la lista
	 * --------------------------------------------------------------- */

	/**
	 * Quita una línea de la lista.
	 *
	 * @param {HTMLElement} button Botón de eliminar.
	 */
	function removeItem( button ) {
		var row = button.closest( '[data-item-key]' );

		if ( ! row ) {
			return;
		}

		row.classList.add( 'is-removing' );

		post( 'iwq_remove_item', { key: row.dataset.itemKey } )
			.then( function ( data ) {
				row.remove();
				writeState( { count: data.count, ids: data.ids } );
				syncUI( data );
				notify( i18n.itemRemoved, 'success' );
			} )
			.catch( function ( error ) {
				row.classList.remove( 'is-removing' );
				notify( error.message || i18n.error, 'error' );
			} );
	}

	/**
	 * Botones más y menos del control de cantidad del panel.
	 *
	 * @param {HTMLElement} button Botón pulsado.
	 */
	function stepQuantity( button ) {
		var wrap = button.closest( '.iwq-qty' );
		var input = wrap && wrap.querySelector( '.iwq-quantity' );

		if ( ! input ) {
			return;
		}

		var value = parseInt( input.value, 10 ) || 1;
		var min = parseInt( input.min, 10 ) || 1;
		var max = parseInt( input.max, 10 ) || Infinity;

		value += button.classList.contains( 'iwq-qty__button--minus' ) ? -1 : 1;
		value = Math.min( max, Math.max( min, value ) );

		if ( String( value ) === input.value ) {
			return;
		}

		input.value = value;
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	var quantityTimer = null;

	/**
	 * Guarda la nueva cantidad de una línea, agrupando pulsaciones seguidas.
	 *
	 * @param {HTMLElement} input Campo de cantidad.
	 */
	function updateQuantity( input ) {
		var row = input.closest( '[data-item-key]' );

		if ( ! row ) {
			return;
		}

		clearTimeout( quantityTimer );

		quantityTimer = setTimeout( function () {
			post( 'iwq_update_item', { key: row.dataset.itemKey, quantity: input.value } )
				.then( function ( data ) {
					writeState( { count: data.count, ids: data.ids } );
					syncUI( data );

					// La tabla tipo carrito muestra el subtotal de la línea; el
					// HTML viene de wc_price() en nuestro propio endpoint.
					var subtotal = row.querySelector( '.iwq-line-subtotal' );

					if ( subtotal && data.subtotal ) {
						subtotal.innerHTML = data.subtotal;
					}
				} )
				.catch( function ( error ) {
					notify( error.message || i18n.error, 'error' );
				} );
		}, 500 );
	}

	/**
	 * Vacía la lista tras confirmarlo con el usuario.
	 */
	function clearList() {
		if ( ! window.confirm( i18n.confirmClear ) ) {
			return;
		}

		post( 'iwq_clear_list', {} )
			.then( function ( data ) {
				var body = drawer && drawer.querySelector( '.iwq-drawer__body' );

				if ( body ) {
					body.innerHTML = data.html || '';
				}

				document.querySelectorAll( '.iwq-list__row, .iwq-cart-row' ).forEach( function ( row ) {
					row.remove();
				} );

				writeState( { count: 0, ids: [] } );
				syncUI( data );
			} )
			.catch( function ( error ) {
				notify( error.message || i18n.error, 'error' );
			} );
	}

	/**
	 * Muestra el mensaje de lista vacía cuando corresponde.
	 *
	 * @param {number} count Número de líneas restantes.
	 */
	function refreshEmptyState( count ) {
		document.querySelectorAll( '.iwq-empty' ).forEach( function ( node ) {
			node.hidden = count > 0;
		} );

		document.querySelectorAll( '.iwq-has-items' ).forEach( function ( node ) {
			node.hidden = count === 0;
		} );
	}

	/* ------------------------------------------------------------------
	 * Formulario de solicitud
	 * --------------------------------------------------------------- */

	/**
	 * Envía el formulario de solicitud por AJAX.
	 *
	 * @param {HTMLFormElement} form Formulario.
	 */
	function submitForm( form ) {
		var button = form.querySelector( '[type="submit"]' );
		var body = new FormData( form );

		body.append( 'nonce', settings.nonce );

		clearFormErrors( form );

		if ( button ) {
			button.disabled = true;
			button.setAttribute( 'aria-busy', 'true' );
		}

		getRecaptchaToken( form )
			.then( function ( token ) {
				if ( token ) {
					body.set( 'iwq_recaptcha_token', token );
				}

				return fetch( endpoint( 'iwq_submit_request' ), {
					method: 'POST',
					body: body,
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				} );
			} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( ! json.success ) {
					showFormErrors( form, json.data || {} );
					return;
				}

				writeState( { count: 0, ids: [] } );

				if ( json.data.redirect ) {
					window.location.href = json.data.redirect;
					return;
				}

				replaceWithMessage( form, json.data.message );
				form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			} )
			.catch( function () {
				notify( i18n.error, 'error' );
			} )
			.finally( function () {
				if ( button ) {
					button.disabled = false;
					button.removeAttribute( 'aria-busy' );
				}
			} );
	}

	/**
	 * Sustituye el contenido de un formulario por un mensaje de éxito.
	 *
	 * Se usa `textContent` en lugar de `innerHTML`: aunque el mensaje viene de
	 * un ajuste del administrador, un texto nunca debe interpretarse como
	 * marcado.
	 *
	 * @param {HTMLElement} form    Formulario.
	 * @param {string}      message Texto a mostrar.
	 */
	function replaceWithMessage( form, message ) {
		var box = document.createElement( 'div' );

		box.className = 'iwq-success';
		box.setAttribute( 'role', 'status' );
		box.textContent = message;

		form.replaceChildren( box );
	}

	/**
	 * Borra los mensajes de error de una validación anterior.
	 *
	 * @param {HTMLFormElement} form Formulario.
	 */
	function clearFormErrors( form ) {
		form.querySelectorAll( '.iwq-field--error' ).forEach( function ( field ) {
			field.classList.remove( 'iwq-field--error' );
		} );

		form.querySelectorAll( '.iwq-field__error' ).forEach( function ( node ) {
			node.remove();
		} );
	}

	/**
	 * Pinta los errores devueltos por el servidor junto a cada campo.
	 *
	 * @param {HTMLFormElement} form Formulario.
	 * @param {Object}          data Respuesta de error.
	 */
	function showFormErrors( form, data ) {
		var errors = data.errors || {};
		var firstField = null;

		Object.keys( errors ).forEach( function ( id ) {
			var control = form.querySelector( '#iwq-field-' + id );
			var field = control && control.closest( '.iwq-field' );

			if ( ! field ) {
				return;
			}

			field.classList.add( 'iwq-field--error' );

			var message = document.createElement( 'span' );

			message.className = 'iwq-field__error';
			message.setAttribute( 'role', 'alert' );
			message.textContent = errors[ id ];
			field.appendChild( message );

			if ( ! firstField ) {
				firstField = control;
			}
		} );

		if ( firstField ) {
			firstField.focus();
			firstField.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		} else if ( data.message ) {
			notify( data.message, 'error' );
		}
	}

	/**
	 * Obtiene el token de reCAPTCHA v3, si está configurado.
	 *
	 * @param {HTMLFormElement} form Formulario.
	 * @return {Promise<string>} Token, o cadena vacía.
	 */
	function getRecaptchaToken( form ) {
		var holder = form.querySelector( '.iwq-recaptcha-token' );

		if ( ! holder || ! window.grecaptcha || ! window.grecaptcha.execute ) {
			return Promise.resolve( '' );
		}

		return new Promise( function ( resolve ) {
			window.grecaptcha.ready( function () {
				window.grecaptcha
					.execute( holder.dataset.sitekey, { action: 'iwq_quote' } )
					.then( resolve )
					.catch( function () {
						resolve( '' );
					} );
			} );
		} );
	}

	/* ------------------------------------------------------------------
	 * Contraoferta
	 * --------------------------------------------------------------- */

	/**
	 * Envía una contraoferta desde la vista del pedido.
	 *
	 * @param {HTMLFormElement} form Formulario de contraoferta.
	 */
	function submitCounterOffer( form ) {
		var button = form.querySelector( '[type="submit"]' );

		if ( button ) {
			button.disabled = true;
		}

		post( 'iwq_counter_offer', {
			order_id: form.dataset.orderId,
			offer: form.querySelector( '[name="iwq_offer"]' ).value,
			message: ( form.querySelector( '[name="iwq_message"]' ) || {} ).value || ''
		} )
			.then( function ( data ) {
				replaceWithMessage( form, data.message );
			} )
			.catch( function ( error ) {
				notify( error.message || i18n.error, 'error' );

				if ( button ) {
					button.disabled = false;
				}
			} );
	}

	/* ------------------------------------------------------------------
	 * Arranque
	 * --------------------------------------------------------------- */

	/**
	 * Engancha los manejadores por delegación, así funcionan también con
	 * el contenido que llega por AJAX.
	 */
	function init() {
		syncUI( readState() );

		document.addEventListener( 'click', function ( event ) {
			var target = event.target;

			var add = target.closest( ADD_SELECTOR );

			if ( add ) {
				event.preventDefault();
				addToQuote( add );
				return;
			}

			if ( target.closest( '.iwq-open-drawer' ) ) {
				event.preventDefault();
				openDrawer();
				return;
			}

			if ( target.closest( '.iwq-drawer__close' ) || target.closest( '.iwq-drawer__overlay' ) ) {
				event.preventDefault();
				closeDrawer();
				return;
			}

			var step = target.closest( '.iwq-qty__button' );

			if ( step ) {
				event.preventDefault();
				stepQuantity( step );
				return;
			}

			if ( target.closest( '.iwq-drawer__continue' ) ) {
				event.preventDefault();
				closeDrawer();
				return;
			}

			var remove = target.closest( '.iwq-remove-item' );

			if ( remove ) {
				event.preventDefault();
				removeItem( remove );
				return;
			}

			if ( target.closest( '.iwq-clear-list' ) ) {
				event.preventDefault();
				clearList();
			}
		} );

		document.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( '.iwq-quantity' ) ) {
				updateQuantity( event.target );
			}
		} );

		document.addEventListener( 'submit', function ( event ) {
			if ( event.target.matches( '.iwq-request-form' ) ) {
				event.preventDefault();
				submitForm( event.target );
				return;
			}

			if ( event.target.matches( '.iwq-counter-offer-form' ) ) {
				event.preventDefault();
				submitCounterOffer( event.target );
			}
		} );

		// Otra pestaña puede haber cambiado la lista.
		window.addEventListener( 'storage', function ( event ) {
			if ( event.key === STORAGE_KEY ) {
				syncUI( readState() );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

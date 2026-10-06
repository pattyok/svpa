/**
 * River Gauge Reading: refresh the reading from the local REST endpoint and
 * keep the "an hour ago" text current. The server-rendered markup stays in
 * place if anything fails.
 */
( function () {
	const blocks = document.querySelectorAll(
		".wp-block-carkeek-site-blocks-gauge-reading[data-endpoint]"
	);
	if ( ! blocks.length ) {
		return;
	}

	function relativeTime( date ) {
		const minutes = Math.round( ( Date.now() - date.getTime() ) / 60000 );
		if ( minutes < 1 ) {
			return "just now";
		}
		if ( minutes < 60 ) {
			return 1 === minutes ? "a minute ago" : `${ minutes } minutes ago`;
		}
		const hours = Math.round( minutes / 60 );
		if ( hours < 24 ) {
			return 1 === hours ? "an hour ago" : `${ hours } hours ago`;
		}
		const days = Math.round( hours / 24 );
		return 1 === days ? "a day ago" : `${ days } days ago`;
	}

	function updateRelative( block ) {
		const time = block.querySelector( ".gauge-reading__time time" );
		const target = block.querySelector( ".gauge-reading__relative" );
		if ( ! time || ! target ) {
			return;
		}
		const date = new Date( time.getAttribute( "datetime" ) );
		if ( ! isNaN( date ) ) {
			target.textContent = `${ relativeTime( date ) }/`;
		}
	}

	function applyParts( block, parts ) {
		Object.keys( parts ).forEach( ( name ) => {
			const current = block.querySelector( `[data-gauge-part="${ name }"]` );
			if ( current ) {
				current.outerHTML = parts[ name ];
			}
		} );
	}

	// One request per station, however many blocks show it.
	const byEndpoint = new Map();
	blocks.forEach( ( block ) => {
		updateRelative( block );
		const endpoint = block.dataset.endpoint;
		byEndpoint.set( endpoint, [ ...( byEndpoint.get( endpoint ) || [] ), block ] );
	} );

	byEndpoint.forEach( ( group, endpoint ) => {
		fetch( endpoint, { credentials: "omit" } )
			.then( ( response ) => ( response.ok ? response.json() : null ) )
			.then( ( data ) => {
				if ( ! data || ! data.found || ! data.parts ) {
					return;
				}
				group.forEach( ( block ) => {
					applyParts( block, data.parts );
					updateRelative( block );
				} );
			} )
			.catch( () => {} );
	} );

	setInterval( () => blocks.forEach( updateRelative ), 60000 );
} )();

const HTTP_PROTOCOLS = new Set( [ 'http:', 'https:' ] );

/**
 * Parse the mixed REST response format currently returned by the plugin.
 *
 * @param {*} response REST response value.
 * @return {Object|null} Parsed response, or null when it is invalid.
 */
export function parseLogResponse( response ) {
	if ( response && typeof response === 'object' ) {
		return response;
	}

	if ( typeof response !== 'string' ) {
		return null;
	}

	try {
		const parsed = JSON.parse( response );
		return parsed && typeof parsed === 'object' ? parsed : null;
	} catch {
		return null;
	}
}

/**
 * Convert legacy HTML-formatted log messages to inert, readable text.
 *
 * This is intentionally not an HTML sanitizer. Callers must render the return
 * value as a normal React child so any malformed or residual markup remains
 * escaped and cannot become executable DOM.
 *
 * @param {*} value Log message.
 * @return {string} Inert text.
 */
export function toInertLogText( value ) {
	if ( value === null || typeof value === 'undefined' ) {
		return '';
	}

	return String( value )
		.replace( /<\s*br\s*\/?>/gi, ' ' )
		.replace( /<[^>]*>/g, '' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/**
 * Parse the one legacy anchor shape emitted by archive completion tasks.
 *
 * The returned values are text and URL data only. Callers must construct the
 * anchor with React; no source HTML is ever returned or injected into the DOM.
 * Any extra tag, unknown/duplicate attribute, non-HTTP(S) URL, URL userinfo, or
 * malformed anchor makes the entire message fall back to inert text.
 *
 * @param {*} value Activity-log message.
 * @return {{before: string, after: string, link: {href: string, label: string}|null}} Parsed message.
 */
export function parseActivityLogMessage( value ) {
	const source =
		value === null || typeof value === 'undefined' ? '' : String( value );
	const fallback = {
		before: toInertLogText( source ),
		after: '',
		link: null,
	};
	const anchor = source.match(
		/^([\s\S]*?)<a(\s+[^<>]*?)>([^<>]*)<\/a>([\s\S]*?)$/i
	);

	if ( ! anchor ) {
		return fallback;
	}

	const beforeSource = anchor[ 1 ];
	const attributeSource = anchor[ 2 ];
	const label = toInertLogText( anchor[ 3 ] );
	const afterSource = anchor[ 4 ];

	// Completion messages are otherwise plain text. Refuse to extract a link
	// from a larger HTML payload such as a script, image, or second anchor.
	if ( /[<>]/.test( beforeSource + afterSource ) || ! label ) {
		return fallback;
	}

	const attributes = parseAnchorAttributes( attributeSource );
	if ( ! attributes || ! attributes.href ) {
		return fallback;
	}

	const href = attributes.href
		.replace( /&(?:amp|#0*38|#x0*26);/gi, '&' )
		.trim();
	if ( ! /^https?:\/\//i.test( href ) ) {
		return fallback;
	}

	const safeUrl = getSafeLogUrl( href );
	if ( ! safeUrl || ! /^https?:\/\//i.test( safeUrl.href ) ) {
		return fallback;
	}

	return {
		before: toInertLogText( beforeSource ),
		after: toInertLogText( afterSource ),
		link: {
			href: safeUrl.href,
			label,
		},
	};
}

/**
 * Parse the tightly allowlisted attributes accepted on a legacy log anchor.
 *
 * @param {string} source Raw attribute source, including leading whitespace.
 * @return {Object<string,string>|null} Parsed attributes, or null when invalid.
 */
function parseAnchorAttributes( source ) {
	const allowed = new Set( [ 'href', 'target', 'rel' ] );
	const attributes = {};
	let remainder = source;

	while ( remainder.length ) {
		const attribute = remainder.match(
			/^\s+([a-z][a-z0-9_-]*)\s*=\s*(["'])(.*?)\2/i
		);
		if ( ! attribute ) {
			return null;
		}

		const name = attribute[ 1 ].toLowerCase();
		if (
			! allowed.has( name ) ||
			Object.prototype.hasOwnProperty.call( attributes, name )
		) {
			return null;
		}

		attributes[ name ] = attribute[ 3 ];
		remainder = remainder.slice( attribute[ 0 ].length );
	}

	if ( attributes.target && attributes.target.toLowerCase() !== '_blank' ) {
		return null;
	}

	if ( attributes.rel ) {
		const rel = attributes.rel
			.toLowerCase()
			.split( /\s+/ )
			.filter( Boolean );
		if (
			! rel.includes( 'noopener' ) ||
			rel.some(
				( token ) => ! [ 'noopener', 'noreferrer' ].includes( token )
			)
		) {
			return null;
		}
	}

	return attributes;
}

/**
 * Return a safe link and display label for an export-log URL.
 *
 * Only explicit HTTP(S) URLs and root-relative local paths are linkable.
 * Protocol-relative URLs are rejected so an attacker cannot choose the scheme.
 *
 * @param {*} value Candidate URL.
 * @return {{href: string, label: string}|null} Safe URL information.
 */
export function getSafeLogUrl( value ) {
	if ( typeof value !== 'string' ) {
		return null;
	}

	const candidate = value.trim();
	if ( ! candidate ) {
		return null;
	}

	if ( /^\/(?!\/)/.test( candidate ) ) {
		try {
			const localBase = new URL( 'https://simply-static.invalid/' );
			const parsed = new URL( candidate, localBase );

			if ( parsed.origin !== localBase.origin ) {
				return null;
			}

			const localPath = `${ parsed.pathname }${ parsed.search }${ parsed.hash }`;
			return { href: localPath, label: localPath };
		} catch {
			return null;
		}
	}

	if ( ! /^https?:\/\//i.test( candidate ) ) {
		return null;
	}

	try {
		const parsed = new URL( candidate );
		if ( ! HTTP_PROTOCOLS.has( parsed.protocol ) ) {
			return null;
		}
		if ( parsed.username || parsed.password ) {
			return null;
		}

		return {
			href: parsed.href,
			label:
				`${ parsed.pathname }${ parsed.search }${ parsed.hash }` || '/',
		};
	} catch {
		return null;
	}
}

/**
 * HTML Parser
 *
 * Extracts structured data from HTML content including:
 * - Plain text content
 * - Headings (H2/H3) with positions
 * - Images with alt text
 * - Links (internal/external) with anchor text
 * - Paragraphs with word counts
 *
 * @module html-parser
 */

/**
 * Parses HTML content and extracts structured data
 *
 * @param {string} html - The HTML content to parse
 * @return {Object} Parsed data structure
 * @return {string} returns.text - Plain text content
 * @return {Array<Object>} returns.headings - H2/H3 headings with positions
 * @return {Array<Object>} returns.images - Images with alt text
 * @return {Array<Object>} returns.links - Links with anchor text and type
 * @return {Array<Object>} returns.paragraphs - Paragraphs with word counts
 *
 * @example
 * const result = parseHtml('<h2>Title</h2><p>Content here.</p>');
 * // returns {
 * //   text: 'Title Content here.',
 * //   headings: [{ level: 2, text: 'Title', position: 0 }],
 * //   images: [],
 * //   links: [],
 * //   paragraphs: [{ text: 'Content here.', wordCount: 2, position: 6 }]
 * // }
 */
export function parseHtml( html ) {
	if ( ! html || typeof html !== 'string' ) {
		return {
			text: '',
			headings: [],
			images: [],
			links: [],
			paragraphs: [],
		};
	}

	// Create a DOM parser
	const parser = new DOMParser();
	const doc = parser.parseFromString( html, 'text/html' );

	// Extract plain text
	const text = extractText( doc.body );

	// Extract headings (H2 and H3)
	const headings = extractHeadings( doc.body, text );

	// Extract images
	const images = extractImages( doc.body );

	// Extract links
	const links = extractLinks( doc.body );

	// Extract paragraphs
	const paragraphs = extractParagraphs( doc.body, text );

	return {
		text,
		headings,
		images,
		links,
		paragraphs,
	};
}

/**
 * Extracts plain text from HTML element
 *
 * @param {HTMLElement} element - The element to extract text from
 * @return {string} Plain text content
 */
function extractText( element ) {
	if ( ! element ) {
		return '';
	}

	// Clone the element to avoid modifying the original
	const clone = element.cloneNode( true );

	// Add spaces after block-level elements to preserve word boundaries
	const blockElements = clone.querySelectorAll(
		'p, div, h1, h2, h3, h4, h5, h6, li, td, th'
	);
	blockElements.forEach( ( el ) => {
		el.after( document.createTextNode( ' ' ) );
	} );

	// Use textContent to get all text, then normalize whitespace
	const text = clone.textContent || '';
	return text.replace( /\s+/g, ' ' ).trim();
}

/**
 * Extracts H2 and H3 headings with their positions in the text
 *
 * @param {HTMLElement} element  - The element to search
 * @param {string}      fullText - The full plain text for position calculation
 * @return {Array<Object>} Array of heading objects
 */
function extractHeadings( element, fullText ) {
	if ( ! element ) {
		return [];
	}

	const headings = [];
	const h2Elements = element.querySelectorAll( 'h2' );
	const h3Elements = element.querySelectorAll( 'h3' );

	// Process H2 headings
	h2Elements.forEach( ( h2 ) => {
		const text = h2.textContent.trim();
		if ( text ) {
			const position = fullText.indexOf( text );
			headings.push( {
				level: 2,
				text,
				position: position >= 0 ? position : 0,
			} );
		}
	} );

	// Process H3 headings
	h3Elements.forEach( ( h3 ) => {
		const text = h3.textContent.trim();
		if ( text ) {
			const position = fullText.indexOf( text );
			headings.push( {
				level: 3,
				text,
				position: position >= 0 ? position : 0,
			} );
		}
	} );

	// Sort by position
	headings.sort( ( a, b ) => a.position - b.position );

	return headings;
}

/**
 * Extracts images with alt text
 *
 * @param {HTMLElement} element - The element to search
 * @return {Array<Object>} Array of image objects
 */
function extractImages( element ) {
	if ( ! element ) {
		return [];
	}

	const images = [];
	const imgElements = element.querySelectorAll( 'img' );

	imgElements.forEach( ( img ) => {
		const src = img.getAttribute( 'src' ) || '';
		const alt = img.getAttribute( 'alt' ) || '';

		images.push( {
			src,
			alt,
			hasAlt: alt.length > 0,
		} );
	} );

	return images;
}

/**
 * Extracts links with anchor text and classifies as internal/external
 *
 * @param {HTMLElement} element - The element to search
 * @return {Array<Object>} Array of link objects
 */
function extractLinks( element ) {
	if ( ! element ) {
		return [];
	}

	const links = [];
	const linkElements = element.querySelectorAll( 'a' );

	linkElements.forEach( ( link ) => {
		const href = link.getAttribute( 'href' ) || '';
		const anchorText = link.textContent.trim();
		const rel = link.getAttribute( 'rel' ) || '';

		// Determine if link is internal or external
		const isInternal = isInternalLink( href );
		const hasNofollow = rel.includes( 'nofollow' );

		// Check if anchor text is descriptive (not generic)
		const genericAnchors = [
			'click here',
			'read more',
			'here',
			'link',
			'klik di sini',
			'baca selengkapnya',
		];
		const isDescriptive =
			anchorText.length > 0 &&
			! genericAnchors.includes( anchorText.toLowerCase() );

		links.push( {
			href,
			anchorText,
			isInternal,
			isExternal: ! isInternal,
			hasNofollow,
			isDescriptive,
		} );
	} );

	return links;
}

/**
 * Determines if a URL is internal
 *
 * @param {string} href - The URL to check
 * @return {boolean} True if internal link
 */
function isInternalLink( href ) {
	if ( ! href ) {
		return false;
	}

	// Relative URLs are internal
	if (
		href.startsWith( '/' ) ||
		href.startsWith( '#' ) ||
		href.startsWith( '?' )
	) {
		return true;
	}

	// Check if URL starts with current domain
	if ( typeof window !== 'undefined' && window.location ) {
		const currentDomain = window.location.hostname;
		try {
			const url = new URL( href, window.location.origin );
			return url.hostname === currentDomain;
		} catch ( e ) {
			// Invalid URL, treat as internal
			return true;
		}
	}

	// If we can't determine, check if it starts with http/https
	return ! href.startsWith( 'http://' ) && ! href.startsWith( 'https://' );
}

/**
 * Extracts paragraphs with word counts
 *
 * @param {HTMLElement} element  - The element to search
 * @param {string}      fullText - The full plain text for position calculation
 * @return {Array<Object>} Array of paragraph objects
 */
function extractParagraphs( element, fullText ) {
	if ( ! element ) {
		return [];
	}

	const paragraphs = [];
	const pElements = element.querySelectorAll( 'p' );

	pElements.forEach( ( p ) => {
		const text = p.textContent.trim();
		if ( text ) {
			const words = text.split( /\s+/ ).filter( ( w ) => w.length > 0 );
			const wordCount = words.length;
			const position = fullText.indexOf( text );

			paragraphs.push( {
				text,
				wordCount,
				position: position >= 0 ? position : 0,
			} );
		}
	} );

	// Sort by position
	paragraphs.sort( ( a, b ) => a.position - b.position );

	return paragraphs;
}

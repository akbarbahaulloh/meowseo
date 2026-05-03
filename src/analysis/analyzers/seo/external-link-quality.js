/**
 * External Link Quality Analyzer
 *
 * @module analysis/analyzers/seo/external-link-quality
 */

export function analyzeExternalLinkQuality( content, tldBlacklistStr ) {
	if ( ! content || content.trim() === '' ) {
		return {
			id: 'external-link-quality',
			type: 'good',
			message: 'Link Quality: No external links to analyze.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	const linkRegex = /<a[^>]+href=["']([^"']+)["'][^>]*>/gi;
	let match;
	let httpLinks = 0;
	let spamLinks = 0;
	let totalExternal = 0;

	// Parse blacklist
	let blacklist = [];
	if ( tldBlacklistStr ) {
		blacklist = tldBlacklistStr.split(',').map( tld => tld.trim().toLowerCase() ).filter( tld => tld !== '' );
	}
	if ( blacklist.length === 0 ) {
		// Default spam TLDs
		blacklist = ['.xyz', '.top', '.loan', '.click', '.gq', '.cf', '.tk', '.ml'];
	} else {
		// Ensure they start with a dot
		blacklist = blacklist.map( tld => tld.startsWith('.') ? tld : `.${tld}` );
	}

	while ( ( match = linkRegex.exec( content ) ) !== null ) {
		const href = match[1];

		// Only check external links
		if ( href.startsWith('http://') || href.startsWith('https://') ) {
			// Basic external check: we assume if it starts with http/https, it's external or absolute.
			// Ideally we exclude siteUrl, but for now we treat all absolutes as potential external links to check.
			totalExternal++;

			// Check HTTPS
			if ( href.startsWith('http://') ) {
				httpLinks++;
			}

			// Check Spam TLD
			try {
				const urlObj = new URL(href);
				const hostname = urlObj.hostname.toLowerCase();
				for ( const tld of blacklist ) {
					if ( hostname.endsWith( tld ) ) {
						spamLinks++;
						break;
					}
				}
			} catch ( e ) {
				// Invalid URL
			}
		}
	}

	if ( totalExternal === 0 ) {
		return {
			id: 'external-link-quality',
			type: 'good',
			message: 'Link Quality: No external links found.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	let score = 100;
	let type = 'good';
	let messages = [];

	if ( spamLinks > 0 ) {
		score -= 60;
		type = 'problem';
		messages.push(`Found ${spamLinks} link(s) to blacklisted TLDs.`);
	}

	if ( httpLinks > 0 ) {
		score -= 40;
		if ( type !== 'problem' ) type = 'ok';
		messages.push(`Found ${httpLinks} HTTP link(s). Use HTTPS instead.`);
	}

	if ( score < 0 ) score = 0;

	if ( messages.length === 0 ) {
		return {
			id: 'external-link-quality',
			type: 'good',
			message: 'Link Quality: All external links are HTTPS and avoid spam TLDs.',
			score: 100,
			weight: 0.05,
			details: { totalExternal },
		};
	}

	return {
		id: 'external-link-quality',
		type,
		message: `Link Quality: ${messages.join(' ')}`,
		score,
		weight: 0.05,
		details: { httpLinks, spamLinks },
	};
}

export default analyzeExternalLinkQuality;

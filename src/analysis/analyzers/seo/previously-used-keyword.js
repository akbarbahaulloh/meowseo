/**
 * Previously Used Keyphrase Analyzer
 *
 * @module analysis/analyzers/seo/previously-used-keyword
 */

export async function analyzePreviouslyUsedKeyword( keyword, postId, restUrl, nonce ) {
	if ( ! keyword || keyword.trim() === '' ) {
		return {
			id: 'previously-used-keyword',
			type: 'problem',
			message: 'Keyphrase Used Previously: No focus keyphrase was set for this page.',
			score: 0,
			weight: 0.05,
			details: {},
		};
	}

	if ( ! restUrl || ! nonce ) {
		return {
			id: 'previously-used-keyword',
			type: 'good',
			message: 'Keyphrase Used Previously: You haven\'t used this keyphrase before, which is great.',
			score: 100,
			weight: 0.05,
			details: {},
		};
	}

	try {
		const response = await fetch(
			`${ restUrl }/check-cannibalization?post_id=${ postId }&keyword=${ encodeURIComponent( keyword ) }`,
			{
				headers: {
					'X-WP-Nonce': nonce,
				},
			}
		);

		if ( response.ok ) {
			const data = await response.json();
			if ( data.is_used && data.posts && data.posts.length > 0 ) {
				return {
					id: 'previously-used-keyword',
					type: 'problem',
					message: `Keyphrase Used Previously: You've used this keyphrase once before. Do not use your keyphrase more than once.`,
					score: 0,
					weight: 0.05,
					details: { posts: data.posts },
				};
			}
		}
	} catch ( error ) {
		// Ignore error and assume good
	}

	return {
		id: 'previously-used-keyword',
		type: 'good',
		message: 'Keyphrase Used Previously: You haven\'t used this keyphrase before, which is great.',
		score: 100,
		weight: 0.05,
		details: {},
	};
}

export default analyzePreviouslyUsedKeyword;

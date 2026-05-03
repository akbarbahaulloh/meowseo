/**
 * useSchemas Hook
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function useSchemas(postId) {
	const [schemas, setSchemas] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);

	useEffect(() => {
		loadSchemas();
	}, [postId]);

	async function loadSchemas() {
		try {
			setLoading(true);
			setError(null);
			const result = await apiFetch({
				path: `/meowseo/v1/schemas/${postId}`,
			});
			setSchemas(result.data || []);
		} catch (err) {
			setError(err.message);
		} finally {
			setLoading(false);
		}
	}

	async function createSchema(schemaData) {
		const result = await apiFetch({
			path: `/meowseo/v1/schemas/${postId}`,
			method: 'POST',
			data: { schema: schemaData },
		});
		setSchemas([...schemas, result.data]);
		return result.data;
	}

	async function updateSchema(schemaId, schemaData) {
		const result = await apiFetch({
			path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
			method: 'PUT',
			data: { schema: schemaData },
		});
		setSchemas(schemas.map((s) => (s.id === schemaId ? result.data : s)));
		return result.data;
	}

	async function deleteSchema(schemaId) {
		await apiFetch({
			path: `/meowseo/v1/schemas/${postId}/${schemaId}`,
			method: 'DELETE',
		});
		setSchemas(schemas.filter((s) => s.id !== schemaId));
	}

	return {
		schemas,
		loading,
		error,
		createSchema,
		updateSchema,
		deleteSchema,
		reload: loadSchemas,
	};
}

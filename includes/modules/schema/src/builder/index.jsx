/**
 * Schema Builder - Main Entry Point
 *
 * @package MeowSEO
 * @subpackage Modules\Schema
 */

import { render } from '@wordpress/element';
import SchemaBuilder from './SchemaBuilder';

// Mount the app
const mountPoint = document.getElementById('meowseo-schema-builder');

if (mountPoint) {
	const postId = parseInt(mountPoint.dataset.postId, 10);
	render(<SchemaBuilder postId={postId} />, mountPoint);
}

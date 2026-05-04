/**
 * Redirects Entry Point
 *
 * @package MeowSEO
 */

import { render } from '@wordpress/element';
import RedirectsDashboard from './redirects/RedirectsDashboard';
import './redirects/style.css';

const mountPoint = document.getElementById('meowseo-redirects-root');

if (mountPoint) {
	render(<RedirectsDashboard />, mountPoint);
}

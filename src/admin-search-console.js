/**
 * MeowSEO Search Console Page Entry Point
 *
 * Loads the Search Console React app.
 *
 * @package MeowSEO
 */

import { render } from '@wordpress/element';
import GSCDashboard from './gsc/GSCDashboard';
import './gsc/dashboard.css';

const root = document.getElementById( 'meowseo-search-console-root' );

if ( root ) {
	render( <GSCDashboard />, root );
}

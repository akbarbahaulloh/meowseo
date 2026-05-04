/**
 * 404 Monitor Entry Point
 *
 * @package MeowSEO
 */

import { render } from '@wordpress/element';
import MonitorDashboard from './monitor-404/MonitorDashboard';
import './monitor-404/style.css';

const mountPoint = document.getElementById('meowseo-404-monitor-root');

if (mountPoint) {
	render(<MonitorDashboard />, mountPoint);
}

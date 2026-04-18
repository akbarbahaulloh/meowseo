/**
 * SpeakableToggle Component
 *
 * Provides a toggle to mark a block as speakable content for voice assistants.
 * When enabled, adds id="meowseo-direct-answer" to the selected block.
 *
 * Requirements: 20.3, 20.4, 20.5
 */

import { ToggleControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * SpeakableToggle Component
 *
 * Requirements:
 * - 20.3: Provide toggle to mark block as speakable content
 * - 20.4: Add id="meowseo-direct-answer" to marked block
 * - 20.5: Save block ID to _meowseo_speakable_block postmeta
 */
const SpeakableToggle: React.FC = () => {
	const [ speakableBlockId, setSpeakableBlockId ] = useEntityPropBinding(
		'_meowseo_speakable_block'
	);

	// Get the currently selected block
	const { selectedBlockClientId, selectedBlock } = useSelect(
		( select: any ) => {
			const blockEditorSelect = select( 'core/block-editor' );
			const clientId = blockEditorSelect?.getSelectedBlockClientId();
			const block = clientId
				? blockEditorSelect?.getBlock( clientId )
				: null;

			return {
				selectedBlockClientId: clientId,
				selectedBlock: block,
			};
		},
		[]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' ) as any;

	// Check if the currently selected block is marked as speakable
	const isCurrentBlockSpeakable = selectedBlockClientId === speakableBlockId;

	// Handle toggle change
	const handleToggle = useCallback(
		( enabled: boolean ) => {
			if ( enabled && selectedBlockClientId ) {
				// Mark the current block as speakable
				setSpeakableBlockId( selectedBlockClientId );

				// Add the id attribute to the block
				if ( updateBlockAttributes && selectedBlock ) {
					updateBlockAttributes( selectedBlockClientId, {
						anchor: 'meowseo-direct-answer',
					} );
				}
			} else {
				// Remove speakable marking
				setSpeakableBlockId( '' );

				// Remove the id attribute from the previously marked block
				if ( updateBlockAttributes && speakableBlockId ) {
					updateBlockAttributes( speakableBlockId, {
						anchor: '',
					} );
				}
			}
		},
		[
			selectedBlockClientId,
			selectedBlock,
			speakableBlockId,
			setSpeakableBlockId,
			updateBlockAttributes,
		]
	);

	// Sync the anchor attribute when speakableBlockId changes
	useEffect( () => {
		if ( speakableBlockId && updateBlockAttributes ) {
			updateBlockAttributes( speakableBlockId, {
				anchor: 'meowseo-direct-answer',
			} );
		}
	}, [ speakableBlockId, updateBlockAttributes ] );

	return (
		<div className="meowseo-speakable-toggle">
			<ToggleControl
				label={ __( 'Mark as Speakable Content', 'meowseo' ) }
				help={ __(
					'Enable voice assistant support for this block (Google Assistant, Alexa)',
					'meowseo'
				) }
				checked={ isCurrentBlockSpeakable }
				onChange={ handleToggle }
				disabled={ ! selectedBlockClientId }
			/>

			{ ! selectedBlockClientId && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Select a block in the editor to mark it as speakable content.',
						'meowseo'
					) }
				</Notice>
			) }

			{ speakableBlockId && ! isCurrentBlockSpeakable && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'A block is already marked as speakable content. Toggle on to change it to the currently selected block.',
						'meowseo'
					) }
				</Notice>
			) }
		</div>
	);
};

export default SpeakableToggle;

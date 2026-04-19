/**
 * SecondaryKeywordsInput Component
 *
 * Provides UI for managing up to 4 secondary keywords with add/remove/reorder functionality.
 * Secondary keywords are stored in _meowseo_secondary_keywords postmeta as JSON array.
 *
 * Requirements: 2.2, 2.10, 2.11
 */

import { memo, useState, useCallback } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import './SecondaryKeywordsInput.css';

/**
 * SecondaryKeywordsInput Component
 *
 * Displays input fields for up to 4 secondary keywords with drag-and-drop reordering.
 *
 * Requirements:
 * - 2.2: Validate that total keyword count does not exceed 5
 * - 2.10: Remove secondary keywords
 * - 2.11: Reorder secondary keywords
 */
const SecondaryKeywordsInput: React.FC = memo( () => {
	const [ newKeyword, setNewKeyword ] = useState( '' );
	const [ error, setError ] = useState( '' );

	// Get current post type and ID
	const { postType, postId } = useSelect( ( select: any ) => {
		const editorSelect = select( 'core/editor' );
		return {
			postType: editorSelect?.getCurrentPostType() || 'post',
			postId: editorSelect?.getCurrentPostId() || 0,
		};
	}, [] );

	// Get primary keyword to check total count
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta', postId );
	const primaryKeyword = meta?._meowseo_focus_keyword || '';
	
	// Get secondary keywords
	let secondaryKeywords: string[] = [];
	try {
		const rawSecondary = meta?._meowseo_secondary_keywords || '';
		if ( rawSecondary ) {
			secondaryKeywords = JSON.parse( rawSecondary );
			if ( ! Array.isArray( secondaryKeywords ) ) {
				secondaryKeywords = [];
			}
		}
	} catch ( e ) {
		secondaryKeywords = [];
	}

	// Calculate total keyword count
	const totalCount = ( primaryKeyword ? 1 : 0 ) + secondaryKeywords.length;
	const canAddMore = totalCount < 5;

	// Update secondary keywords in postmeta
	const updateSecondaryKeywords = useCallback(
		( keywords: string[] ) => {
			setMeta( {
				...meta,
				_meowseo_secondary_keywords: JSON.stringify( keywords ),
			} );
		},
		[ meta, setMeta ]
	);

	// Add keyword handler
	const handleAddKeyword = useCallback( () => {
		const trimmed = newKeyword.trim();
		
		if ( ! trimmed ) {
			setError( __( 'Keyword cannot be empty.', 'meowseo' ) );
			return;
		}

		// Check if already exists
		if ( primaryKeyword === trimmed ) {
			setError( __( 'This keyword is already set as the primary keyword.', 'meowseo' ) );
			return;
		}

		if ( secondaryKeywords.includes( trimmed ) ) {
			setError( __( 'This keyword already exists in secondary keywords.', 'meowseo' ) );
			return;
		}

		// Check count limit
		if ( ! canAddMore ) {
			setError( __( 'Maximum of 5 keywords allowed.', 'meowseo' ) );
			return;
		}

		// Add keyword
		updateSecondaryKeywords( [ ...secondaryKeywords, trimmed ] );
		setNewKeyword( '' );
		setError( '' );
	}, [ newKeyword, primaryKeyword, secondaryKeywords, canAddMore, updateSecondaryKeywords ] );

	// Remove keyword handler
	const handleRemoveKeyword = useCallback(
		( index: number ) => {
			const updated = secondaryKeywords.filter( ( _, i ) => i !== index );
			updateSecondaryKeywords( updated );
			setError( '' );
		},
		[ secondaryKeywords, updateSecondaryKeywords ]
	);

	// Drag end handler
	const handleDragEnd = useCallback(
		( result: any ) => {
			if ( ! result.destination ) {
				return;
			}

			const items = Array.from( secondaryKeywords );
			const [ reorderedItem ] = items.splice( result.source.index, 1 );
			items.splice( result.destination.index, 0, reorderedItem );

			updateSecondaryKeywords( items );
		},
		[ secondaryKeywords, updateSecondaryKeywords ]
	);

	return (
		<div className="meowseo-secondary-keywords">
			<h3 className="meowseo-secondary-keywords__title">
				{ __( 'Secondary Keywords', 'meowseo' ) }
			</h3>
			<p className="meowseo-secondary-keywords__description">
				{ __( 'Add up to 4 additional keywords to optimize for. Drag to reorder.', 'meowseo' ) }
			</p>

			{ secondaryKeywords.length > 0 && (
				<DragDropContext onDragEnd={ handleDragEnd }>
					<Droppable droppableId="secondary-keywords">
						{ ( provided ) => (
							<div
								{ ...provided.droppableProps }
								ref={ provided.innerRef }
								className="meowseo-secondary-keywords__list"
							>
								{ secondaryKeywords.map( ( keyword, index ) => (
									<Draggable
										key={ keyword }
										draggableId={ keyword }
										index={ index }
									>
										{ ( provided ) => (
											<div
												ref={ provided.innerRef }
												{ ...provided.draggableProps }
												className="meowseo-secondary-keywords__item"
											>
												<span
													{ ...provided.dragHandleProps }
													className="meowseo-secondary-keywords__drag-handle"
													aria-label={ __( 'Drag to reorder', 'meowseo' ) }
												>
													⋮⋮
												</span>
												<span className="meowseo-secondary-keywords__keyword">
													{ keyword }
												</span>
												<Button
													isDestructive
													isSmall
													onClick={ () => handleRemoveKeyword( index ) }
													aria-label={ __( 'Remove keyword', 'meowseo' ) }
												>
													{ __( 'Remove', 'meowseo' ) }
												</Button>
											</div>
										) }
									</Draggable>
								) ) }
								{ provided.placeholder }
							</div>
						) }
					</Droppable>
				</DragDropContext>
			) }

			<div className="meowseo-secondary-keywords__add">
				<TextControl
					value={ newKeyword }
					onChange={ setNewKeyword }
					placeholder={ __( 'Enter a keyword', 'meowseo' ) }
					onKeyPress={ ( e: any ) => {
						if ( e.key === 'Enter' ) {
							e.preventDefault();
							handleAddKeyword();
						}
					} }
				/>
				<Button
					variant="primary"
					onClick={ handleAddKeyword }
					disabled={ ! canAddMore }
				>
					{ __( 'Add Keyword', 'meowseo' ) }
				</Button>
			</div>

			{ error && (
				<div className="meowseo-secondary-keywords__error">
					{ error }
				</div>
			) }

			<div className="meowseo-secondary-keywords__count">
				{ __( 'Keywords:', 'meowseo' ) } { totalCount } / 5
			</div>
		</div>
	);
} );

export default SecondaryKeywordsInput;

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import isEqual from 'lodash/isEqual';
import { TextControl, TextareaControl, DateTimePicker, RadioControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { postType, postId } = context;

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta', postId);

	const { loc_acreage, loc_date_added, loc_location, loc_open_to_the_public, loc_type, loc_lat, loc_long, loc_date_added_notes } = meta || {};


	/** Cannot get this to save appropriately */
	const { isSaving, edited, saved } = useSelect( ( select ) => {
		return {
			isSaving: select( 'core/editor' ).isSavingPost(),
			edited: select( 'core/editor' ).getEditedPostAttribute( 'acf' ),
			saved: select( 'core/editor' ).getCurrentPostAttribute( 'acf' ),
		};
	} );

	// Save the ACF meta when the post is saved
	useEffect( () => {
		if ( isSaving && ! isEqual( edited, saved ) ) {
			apiFetch( {
				path: `/wp/v2/${postType}/${ postId }`,
				method: 'POST',
				data: {
					acf: {
						...meta,
					},
				},
			} ).then( ( response ) => {
				console.log( 'ACF meta saved:', response );
			} ).catch( ( error ) => {
				console.error( 'Error saving ACF meta:', error );
			} );
		}
	}, [ isSaving ] );


	return (
		<div { ...useBlockProps() }>
			<RadioControl
				label="Type"
				selected={ loc_type }
				options={ [
					{ label: 'Easement', value: 'easement' },
					{ label: 'Trust-Assisted', value: 'trust-assisted' },
					{ label: 'Trust-Owned', value: 'trust-owned' },
				] }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_type: value,
					}) }
			/>
			<RadioControl
				label="Open to the Public"
				selected={ loc_open_to_the_public === true || loc_open_to_the_public === "true" ? true : false }
				options={ [
					{ label: 'Yes', value: true },
					{ label: 'No', value: false },
				] }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_open_to_the_public: value,
					}) }
			/>

			<TextControl
				label="Acreage"
				placeholder='100'
				value={ loc_acreage }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_acreage: value,
					}) }
			/>
			<TextControl
				label="Location"
				placeholder='City'
				value={ loc_location }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_location: value,
					}) }
			/>
			<TextControl
				label="Latitude"
				value={ loc_lat }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_lat: value,
					}) }
			/>
			<TextControl
				label="Longitude"
				value={ loc_long }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_long: value,
					}) }
			/>
			<TextControl
				label="Date Added"
				value={ loc_date_added }
				onChange={ ( value ) =>
					setMeta({
						...meta,
						loc_date_added: value,
					}) }
			/>

			<div className='block-notes'>Set Activities in the Location pane to the right.</div>

		</div>
	);
}


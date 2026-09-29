import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';
import {
	InspectorControls,
    useBlockProps
} from "@wordpress/block-editor";
export default function Edit( { attributes, setAttributes, context } ) {
	const blockProps = useBlockProps();
	return (
		 <div {...blockProps}>
			<InspectorControls>
				<PanelBody>
					<TextControl
						label={ __( 'Form ID', 'volunteer-event-management' ) }
						value={ attributes.formID }
						onChange={ ( value ) => setAttributes( { formID: value } ) }
						help={ __( 'Enter the ID of the gravity form to display.', 'volunteer-event-management' ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div className='block-notes'>This will display the event/shift details and the registration form with necessary data from the event/shift.</div>
		</div>
	);
}



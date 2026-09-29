import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';
import {
    useBlockProps
} from "@wordpress/block-editor";
export default function Edit( { attributes, setAttributes, context } ) {
	const blockProps = useBlockProps();
	return (
		 <div {...blockProps}>
			<ServerSideRender
				block="carkeek-site-blocks/location-details"
				attributes={ attributes }
			/>
			<div className='block-notes'>Metadata Placeholder: Set Location Details below the content and Activities in the Location pane to the right.</div>
		</div>
	);
}



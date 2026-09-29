import { registerBlockType, createBlock } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import metadata from './block.json';

import './style.scss';
import './editor.scss';
import {
    useBlockProps
} from "@wordpress/block-editor";

registerBlockType((metadata.name), {
	edit(props ) {
		const blockProps = useBlockProps();
		return (
			<div {...blockProps}>
				{/* Your block edit content goes here */}
				[Map Placeholder]
			</div>
		);
	}

});

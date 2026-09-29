import "./editor.scss";
import "./style.scss";
import icons from './icons';
import edit from "./edit";
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import metadata from './block.json';



registerBlockType(metadata.name, {

    icon: icons.dropdown,

    edit,

    save({ attributes } ) {
        const{ label, labelOpen, uniqueID } = attributes;
        return (
            <div { ...useBlockProps.save() }>
                <div
					id={`content-${uniqueID}`}
					className="cks-expand-inner-blocks"
					role="region"
					aria-labelledby={`button-${uniqueID}`}
					aria-expanded="false">
                    <InnerBlocks.Content />
                </div>
				<button
				className={`cks-expand-button`}
				aria-controls={`content-${uniqueID}`}
				data-label={label}
				data-label-open={labelOpen}
				id={`button-${uniqueID}`}
				><span>{label}</span></button>

            </div>
        );
    }
});

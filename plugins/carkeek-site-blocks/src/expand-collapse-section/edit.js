import { select } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { TextControl } from "@wordpress/components";
import { RichText, InnerBlocks, useBlockProps } from "@wordpress/block-editor";

function SectionEdit ( props ) {
    const { attributes, setAttributes, clientId } = props;
	const { label, labelOpen, uniqueID } = attributes;

	useEffect(() => {
	// Make sure the block gets a unique ID assigned

		setAttributes({ uniqueID: clientId })

	}, [clientId])


	return (
		<div { ...useBlockProps() }>
			<button className="cks-expand-button">
				<RichText
					tagName="span"
					value={label}
					onChange={label => setAttributes({ label })}
					placeholder={__("Read More", "carkeek-blocks")}
					allowedFormats={[]}
				/>
			</button>
			<div className={"cks-expand-inner-blocks"}>

				<InnerBlocks
					defaultBlock={['core/paragraph', {placeholder: "Add hidden content..."}]}
					directInsert
				/>
				<TextControl
						value={labelOpen}
						label={__("Label when expanded", "carkeek-blocks")}
						className={"cks-expand-button"}
						onChange={labelOpen => setAttributes({ labelOpen })}
					/>
			</div>


		</div>
	);
}
export default SectionEdit;

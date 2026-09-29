import { InnerBlocks, useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, RangeControl, SelectControl} from "@wordpress/components";
import { __ } from "@wordpress/i18n";

const BlockEdit = (props) => {
	const { attributes, setAttributes } = props;
	const { imageAspectRatio } = attributes;
	const classes = `ck-link-tiles--${imageAspectRatio} columns-${props.attributes.columns} columns-tablet-${props.attributes.columnsTablet} columns-mobile-${props.attributes.columnsMobile}`;

        return (
            <div { ...useBlockProps({className: classes}) }>
				 <InspectorControls>
					<PanelBody title="Link Tiles Settings">
						<SelectControl
							label="Image Aspect Ratio"
							value={ imageAspectRatio }
							options={[
								{ label: __("Landscape 3:2"), value: "landscape"},
								{ label: __("Landscape 4:3"), value: "landscape-43"},
								{ label: __("Portrait 2:3"), value: "portrait"},
								{ label: __("Portrait 3:4"), value: "portrait-34"},
								{ label: __("Square 1:1"), value: "square"},
							]}
							onChange={value =>
							setAttributes({
								imageAspectRatio: value
							})
						}
						/>

						<RangeControl
							label="Columns"
							value={ props.attributes.columns }
							onChange={ (value) => props.setAttributes({ columns: value }) }
							min={ 2 }
            				max={ 6 }
						/>
						<RangeControl
							label="Columns (Tablet)"
							value={ props.attributes.columnsTablet }
							onChange={ (value) => props.setAttributes({ columnsTablet: value }) }
							min={ 2 }
            				max={ 6 }
						/>
						<RangeControl
							label="Columns (Mobile)"
							value={ props.attributes.columnsMobile }
							onChange={ (value) => props.setAttributes({ columnsMobile: value }) }
							min={ 1 }
            				max={ 6 }
						/>
					</PanelBody>
				</InspectorControls>
                <InnerBlocks
                    allowedBlocks={["carkeek-blocks/link-tile", "carkeek-blocks/link-tile-dynamic"]}
                    template={[
                        ["carkeek-blocks/link-tile"],
                        ["carkeek-blocks/link-tile"]
                    ]}
                    orientation="horizontal"
                />
            </div>
        );
    }
export default BlockEdit;
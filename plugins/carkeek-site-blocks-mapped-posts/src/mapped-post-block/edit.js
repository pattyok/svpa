import { withSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    Spinner,
    Placeholder,
    SelectControl,
    ToggleControl,
	RadioControl
} from "@wordpress/components";
import {
    InspectorControls,
    RichText,
    useBlockProps,
	InnerBlocks
} from "@wordpress/block-editor";

import { useEffect } from "@wordpress/element";

var _ = require("lodash");

function MappedPostsArchiveEdit(props) {
    const {
        taxonomies,
        postTypes,
        attributes,
        setAttributes,
        taxTerms
    } = props;
    const {
        postTypeSelected,
        latFieldSelected,
        lngFieldSelected,
        mapAddFilter,
        taxonomySelected,
        clusterMarkers,
        addressFieldType,
		addressFieldSelected,
        filterItemsByTaxonomy,
        filterItemsTaxonomySelected,
        filterItemsTaxonomyTermsSelected,
        filterLabel
    } = attributes;


    const[latlngfieldOptions, setLatlngfieldOptions] = React.useState([]);

	useEffect(() => {
		let latlngfieldOptions = [];
		if (postTypes && postTypeSelected) {
			const typeObj = postTypes.find(({ slug }) => slug === postTypeSelected);

			if (typeObj.metafields) {
				latlngfieldOptions =
					typeObj.metafields &&
					typeObj.metafields.map(type => ({
						value: type.meta_key,
						label: type.meta_key
                }));
			}
		}
		setLatlngfieldOptions(latlngfieldOptions);
	}, [addressFieldType, postTypes, postTypeSelected]);


    useEffect(() => {
        const tax = _.find(taxonomies, { slug: filterItemsTaxonomySelected });
        if (tax) {
            setAttributes({ filterItemsTaxonomySelectedRest: tax.rest_base });
        } else {
            setAttributes({
                filterItemsTaxonomySelectedRest: filterItemsTaxonomySelected
            });
        }
    }, [filterItemsTaxonomySelected, taxonomies]);

    const postTypeSelect = (
        <>

            <SelectControl
                label={__("Post Type", "carkeek-blocks")}
                onChange={postTypeSelected =>
                    setAttributes({ postTypeSelected })
                }
                options={
                    postTypes &&
                    postTypes.map(type => ({
                        value: type.slug,
                        label: type.name
                    }))
                }
                value={postTypeSelected}
            />

            {postTypeSelected && (
                <>
				<RadioControl
					label="Address Field Type"
					help="Select the type of address field."
					selected={ addressFieldType }
					options={ [
						{ label: 'ACF Address', value: 'acf' },
						{ label: 'LatLng', value: 'latlng' },
					] }
					onChange={addressFieldType =>
						setAttributes({ addressFieldType })
					}
				/>
				{addressFieldType === 'acf' && (
				<SelectControl
                        label={__("Address Field", "carkeek-blocks")}
                        onChange={addressFieldSelected =>
                            setAttributes({ addressFieldSelected })
                        }
                        options={latlngfieldOptions}
                        value={addressFieldSelected}
                    />
				)}
				{addressFieldType === 'latlng' && (
						<>
                    <SelectControl
                        label={__("Lat Field", "carkeek-blocks")}
                        onChange={latFieldSelected =>
                            setAttributes({ latFieldSelected })
                        }
                        options={latlngfieldOptions}
                        value={latFieldSelected}
                    />
                    <SelectControl
                        label={__("Lng Field", "carkeek-blocks")}
                        onChange={lngFieldSelected =>
                            setAttributes({ lngFieldSelected })
                        }
                        options={latlngfieldOptions}
                        value={lngFieldSelected}
                    />
					</>
)}
                </>
            )}
        </>
    );
    const taxonomySelect = (
        <>
            <ToggleControl
                label={__("Filter Items by Taxonomy")}
                checked={filterItemsByTaxonomy}
                onChange={filterItemsByTaxonomy =>
                    setAttributes({ filterItemsByTaxonomy })
                }
            />
            {filterItemsByTaxonomy && (
                <>
                    {taxonomies && taxonomies.length > 0 ? (
                        <>
                            <SelectControl
                                label={__("Select Taxonomy", "carkeek-blocks")}
                                onChange={filterItemsTaxonomySelected =>
                                    setAttributes({
                                        filterItemsTaxonomySelected
                                    })
                                }
                                options={
                                    taxonomies &&
                                    taxonomies.map(type => ({
                                        value: type.slug,
                                        label: type.name
                                    }))
                                }
                                value={filterItemsTaxonomySelected}
                            />
                        </>
                    ) : (
                        <div className="ck-error">
                            {__(
                                "There are no taxonomies assigned this post type.",
                                "carkeek-blocks"
                            )}
                        </div>
                    )}
                    {filterItemsTaxonomySelected && (
                        <>
                            {taxTerms && taxTerms.length > 0 ? (
                                <SelectControl
                                    multiple
                                    label={__("Select Terms", "carkeek-blocks")}
                                    onChange={filterItemsTaxonomyTermsSelected =>
                                        setAttributes({
                                            filterItemsTaxonomyTermsSelected
                                        })
                                    }
                                    options={
                                        taxTerms &&
                                        taxTerms.map(type => ({
                                            value: type.id,
                                            label: type.name
                                        }))
                                    }
                                    value={filterItemsTaxonomyTermsSelected}
                                    help={__(
                                        "To select multiple [shift]-click",
                                        "carkeek-blocks"
                                    )}
                                />
                            ) : (
                                <div className="ck-error">
                                    {__(
                                        "There are no terms assigned to this taxonomy.",
                                        "carkeek-blocks"
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </>
            )}
        </>
    );
    const inspectorControls = (
        <InspectorControls>
            <PanelBody title={__("Posts Settings", "carkeek-blocks")}>
                {postTypeSelect}
                {taxonomySelect}
            </PanelBody>
            <PanelBody title={__("Map Settings", "carkeek-blocks")}>
                <ToggleControl
                    label="Add Taxonomy Filter to Map"
                    checked={mapAddFilter}
                    onChange={value =>
                        setAttributes({
                            mapAddFilter: value
                        })
                    }
                />
                {mapAddFilter && (
                    <SelectControl
                        label={__("Select Taxonomy", "carkeek-blocks")}
                        onChange={value =>
                            setAttributes({ taxonomySelected: value })
                        }
                        options={
                            taxonomies &&
                            taxonomies.map(type => ({
                                value: type.rest_base,
                                label: type.name
                            }))
                        }
                        value={taxonomySelected}
                    />
                )}
                <ToggleControl
                    label="Cluster Markers"
                    checked={clusterMarkers}
                    onChange={value =>
                        setAttributes({
                            clusterMarkers: value
                        })
                    }
                />
            </PanelBody>
        </InspectorControls>
    );
    const blockProps = useBlockProps();
    const noPostMessage = __("Select a Post Type from the Block Settings");
	const innerBlocksTemplate = [
		[ 'core/heading', { placeholder: 'Section Title' } ],
		[ 'core/paragraph', { placeholder: 'Section Summary' } ],
	]
    return (
        <div {...blockProps}>
            {inspectorControls}

            {!postTypeSelected && (
                <Placeholder label={__("Mapped Posts")}>
                    {!Array.isArray(postTypes) ? <Spinner /> : noPostMessage}
                </Placeholder>
            )}
            {postTypeSelected && (
                <div className="archive-map-wrapper">
                    <div className="archive-map-top">
						<div className="archive-map-intro">
						<InnerBlocks
						template={ innerBlocksTemplate }
						/>
						</div>
						<div className="archive-filter-list">
							<RichText
								tagName="div"
								className="archive-map-title"
								placeholder={__("Filter Label", "carkeek-blocks")}
								value={filterLabel}
								onChange={filterLabel => setAttributes({ filterLabel })}
							/>
							[FILTER PLACEHOLDER]
						</div>
                    </div>
                    <div className="archive-map">[MAP PLACEHOLDER]</div>
                </div>
            )}
        </div>
    );
}

export default withSelect((select, props) => {
    const { attributes } = props;
    const { filterItemsTaxonomySelected } = attributes;
    const { getPostTypes, getTaxonomies, getEntityRecords } = select("core");
    const taxonomies = getTaxonomies({ per_page: -1 });

    const taxTerms = getEntityRecords("taxonomy", filterItemsTaxonomySelected, {
        per_page: -1
    });

    return {
        postTypes: getPostTypes({ per_page: -1 }),
        taxonomies: taxonomies,
        taxTerms: taxTerms
    };
})(MappedPostsArchiveEdit);

import { __ } from "@wordpress/i18n";
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from "@wordpress/block-editor";
import {
	Button,
	Disabled,
	PanelBody,
	RangeControl,
	TextControl,
} from "@wordpress/components";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "./block.json";

function GaugeReadingEdit( { attributes, setAttributes } ) {
	const { stationId, heading, linkUrl, mapImageId, markerX, markerY } =
		attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( "Gauge", "carkeek-blocks" ) }>
					<TextControl
						label={ __( "Station ID", "carkeek-blocks" ) }
						help={ __( "e.g. USGS-MF11", "carkeek-blocks" ) }
						value={ stationId }
						onChange={ ( value ) =>
							setAttributes( { stationId: value.trim().toUpperCase() } )
						}
					/>
					<TextControl
						label={ __( "River name", "carkeek-blocks" ) }
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
					/>
					<TextControl
						type="url"
						label={ __( "Floodzilla gauge page URL", "carkeek-blocks" ) }
						value={ linkUrl }
						onChange={ ( value ) => setAttributes( { linkUrl: value } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( "Map", "carkeek-blocks" ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ "image" ] }
							value={ mapImageId }
							onSelect={ ( media ) =>
								setAttributes( { mapImageId: media.id } )
							}
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ mapImageId
										? __( "Replace map image", "carkeek-blocks" )
										: __( "Select map image", "carkeek-blocks" ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ mapImageId && (
						<Button
							variant="link"
							isDestructive
							onClick={ () => setAttributes( { mapImageId: undefined } ) }
						>
							{ __( "Remove map image", "carkeek-blocks" ) }
						</Button>
					) }
					{ mapImageId && (
						<>
							<RangeControl
								label={ __( "Marker position (left %)", "carkeek-blocks" ) }
								value={ markerX }
								min={ 0 }
								max={ 100 }
								step={ 0.5 }
								onChange={ ( value ) => setAttributes( { markerX: value } ) }
							/>
							<RangeControl
								label={ __( "Marker position (top %)", "carkeek-blocks" ) }
								value={ markerY }
								min={ 0 }
								max={ 100 }
								step={ 0.5 }
								onChange={ ( value ) => setAttributes( { markerY: value } ) }
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block={ metadata.name } attributes={ attributes } />
			</Disabled>
		</div>
	);
}

export default GaugeReadingEdit;

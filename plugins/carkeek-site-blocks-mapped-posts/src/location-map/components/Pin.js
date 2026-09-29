import React, { useRef } from "react";
import { Marker, Tooltip } from "react-leaflet";
import L from "leaflet";
import iconsvg from "../../resources/marker";

const Pin = props => {
	const markerRef = useRef(null);
	const {
		center,
		title,
		link,
		icon,
		data,
		image
	} = props;

	const getMyIcon =() => {
		const iconHtml = `${iconsvg.marker}`;
		const divIcon = L.divIcon({
			className: `archive-map-icon icon-${icon}`,
			html: iconHtml,
			iconSize: [25, 35],
			iconAnchor: [15, 30],
			popupAnchor: [0, -30]
		});
		return divIcon;
	}
	return (
		<Marker
			position={center}
			icon={getMyIcon()}
		>
			<Tooltip>
				<span dangerouslySetInnerHTML={{ __html: title }} />
			</Tooltip>
		</Marker>
	);
};

export default Pin;

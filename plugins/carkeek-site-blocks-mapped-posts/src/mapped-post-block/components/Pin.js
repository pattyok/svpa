import React, { useRef } from "react";
import { Marker, Popup, Tooltip } from "react-leaflet";
import L from "leaflet";
import iconsvg from "../../resources/marker";

const Pin = props => {
    const markerRef = useRef(null);
    const {
        center,
        title,
        link,
		access,
		accessLabels,
		data,
		image,
		getLabel
    } = props;

	const getMyIcon =() => {
		let icon = "closed";
		if (access) {
			if (access.includes("featured")) {
				icon = "featured";
			} else if (access.includes("open")) {
				icon = "open";
			}
		}
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

    const popup = (

        <div className={"map-archive-popup"}>
			{image && <div className="popup-image" style={{backgroundImage: `url(${image})`}}></div>}
			<div className="popup-content">
            <a
                className={"popup-link"}
                href={link}
                dangerouslySetInnerHTML={{ __html: title }}
            />
            <ul className="popup-data no-bullets">
				{data.loc_location && <li className="location-data-item"><span className="icon-location" title="Location"></span> {data.loc_location}</li>}
				{data.loc_acreage && <li className="location-data-item"><span className="icon-acreage" title="Acreage"></span> {`${data.loc_acreage} acres`}</li>}
				<li className="location-data-item"><span className="icon-access" title="Access"></span>
				{accessLabels.join(", ")}</li>
				</ul>
			</div>
        </div>
    );
    return (
        <Marker
            position={center}
			icon={getMyIcon()}
        >
            <Popup minWidth={325} ref={markerRef}>{popup}</Popup>
			<Tooltip>
				<span dangerouslySetInnerHTML={{ __html: title + ',<br/>' + data.loc_location }} />
			</Tooltip>
        </Marker>
    );
};

export default Pin;

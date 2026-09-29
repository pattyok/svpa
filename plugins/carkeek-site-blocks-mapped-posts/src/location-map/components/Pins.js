import React, { useRef, useEffect } from "react";
import MarkerClusterGroup from 'react-leaflet-cluster'
import { latLngBounds } from "leaflet";
import { FeatureGroup, useMap } from "react-leaflet";
import _ from "lodash";
import Pin from './Pin.js';

	const Pins = props => {
		const {
				data,
				selectedIndex,
				cluster,
				paddingTopLeft,
				paddingBottomRight,
				fitBounds
		} = props;
		const map = useMap();
		const groupRef = useRef();
		const clusterRef = useRef();
		const bounds = latLngBounds();


		const Markers = [];
		data.forEach(item => {
				bounds.extend(item.position);
				//if (item.cat.slugs.length > 0) {
				const title = _.get(item, 'title.rendered', '');
				console.log('adding marker', item);
				Markers.push(
					<Pin
						key={item.id}
						itemId={item.id}
						title={item.title}
						icon={item.icon}
						center={item.position}
						data={item.acf}
						link={item.link ? item.link : ""}
						groupRef={groupRef}
					/>
				);
				//}
		});

		if (cluster) {
				if (fitBounds) {
						useEffect(() => {
								const layer = groupRef.current; //get leaflet.markercluster instance
								if (data.length > 0 && layer) {
										map.fitBounds(layer.getBounds(), {
												paddingTopLeft: paddingTopLeft,
												paddingBottomRight: [50, 50],
												maxZoom: 12
										}); //zoom to cover visible markers
								}
						}, [data, map, groupRef]);
				}
				return (
						<FeatureGroup ref={groupRef} name="locations">
							<MarkerClusterGroup
										ref={clusterRef}
										showCoverageOnHover={false}
										spiderfyOnMaxZoom={true}
										spiderLegPolylineOptions={{
												weight: 0,
												opacity: 0
										}}
										removeOutsideVisibleBounds={true}
										maxClusterRadius={20}
								>
										{Markers}
								</MarkerClusterGroup>
						</FeatureGroup>
				);
		}

		useEffect(() => {

				if (data.length > 0 && map) {
						if (fitBounds) {
								map.fitBounds(bounds, {
										paddingTopLeft: paddingTopLeft,
										paddingBottomRight: [50, 50],
										maxZoom: 12
								});
						} else {
								map.setView(bounds.getCenter(), map.getZoom());
						}
				}
		}, [data, map]);

		return Markers;
};

export default Pins;

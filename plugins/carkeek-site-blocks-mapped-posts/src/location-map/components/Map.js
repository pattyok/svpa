import React, { useState } from "react";
import { MapContainer, ZoomControl, GeoJSON } from "react-leaflet";
import VectorBasemapLayer from "react-esri-leaflet/plugins/VectorBasemapLayer";
import Pins from "./Pins.js";

function MapCluster(props) {
    const { locations } = props;

    const [map, setMap] = useState();

	const center = locations.markers[0].position;

    return (
                <MapContainer
                    zoom={11}
                    zoomControl={false}
                    scrollWheelZoom={false}
                    whenCreated={setMap}
                    maxZoom={18}
                    minZoom={4}
					center={center}
                >

                    <Pins
                        map={map}
                        data={locations.markers}
                        selectedIndex={"x"}
                        onItemClick={false}
                        cluster={false}
                        paddingTopLeft={[50, 50]}
                        paddingBottomRight={[50, 50]}
                        fitBounds={false}
                    />
                    <VectorBasemapLayer
                        name="ArcGIS:Streets"
                        token={
                            "AAPK88b80ce9c7ff4f3eb890299bc31c25b2zTDKHhtJEjIwRIcRotKXl7uEPBRrTA4EnAgLMjnUlFBy8fHs6g-iVVXYzffah9wX"
                        }
                    />
                    <ZoomControl position="topright" />
                </MapContainer>
    );
}

export default MapCluster;

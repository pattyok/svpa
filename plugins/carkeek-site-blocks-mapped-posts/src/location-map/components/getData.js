

export const getMapData = mapEl => {

    const items = mapEl.querySelectorAll(".data-geo");
    let mapData = {
        markers: [],
        geoJson: {
            type: "FeatureCollection",
            features: []
        },
    };

    items.forEach((item, index) => {
        const type = item.getAttribute("data-geo-type");
        if ("LatLng" == type) {
            let icon = item.getAttribute("data-icon");
            if (!icon) {
                icon = "default";
            }
            const marker = {
				position: [parseFloat(item.getAttribute("data-lat")), parseFloat(item.getAttribute("data-lng"))],
                title: item.getAttribute("data-label"),
                icon: icon,
                id: index
            };
            mapData.markers.push(marker);
        }
		// Future support for GeoJSON points


    });
    return mapData;
};

export default getMapData;

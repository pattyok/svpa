import React, { useRef, useState, useEffect } from "react";
import { MapContainer, ZoomControl, AttributionControl} from "react-leaflet";
import {useNavigate } from 'react-router-dom';
import VectorBasemapLayer from "react-esri-leaflet/plugins/VectorBasemapLayer";

var _ = require('lodash');

import Pins from './Pins.js';
import Filters from './Filters.js';
import Legend from './Legend.js';

// Set to true to enable legend click-to-filter, false to disable
const legendInteractive = true;

function MapCluster(props) {
    const { zoom,
		locations,
		isMapLoading,
		isCatLoading,
		visibleLocations,
		onUpdateLocations,
		visibleBounds,
		categories,
		taxFilter,
		filterLabel,
		cluster,
		allCategories} = props;
    const hideListAtLoad = window.innerWidth > 600 ? false : true;

    // let params = queryString.parse(location.search)
    let paramCats = [];

    const mapRef = useRef(null);
    const [legendFilter, setLegendFilter] = useState([]);

    const handleLegendFilterToggle = (slug) => {
        setLegendFilter(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    const filterByLegend = (locs, slugs) => {
        if (!slugs || slugs.length === 0) return locs;
        return _.filter(locs, loc => {
            if (!loc.acf || !loc.acf.loc_public_access) return false;
            const val = loc.acf.loc_public_access;
            if (Array.isArray(val)) {
                return val.some(v => slugs.includes(v));
            }
            if (typeof val === 'object' && val !== null) {
                return val.value ? slugs.includes(val.value) : false;
            }
            return slugs.includes(val);
        });
    };

    const legendFilteredLocations = legendInteractive && legendFilter.length > 0
        ? filterByLegend(visibleLocations, legendFilter)
        : visibleLocations;


    const mapReady = !isMapLoading && !isCatLoading;




    //todo: tie this to browser resize https://react-cn.github.io/react/tips/dom-event-listeners.html
    let boundsOptions = {padding: [25, 25]}
    if ( window.innerWidth > 599 ) {
        boundsOptions= {paddingTopLeft: [275, 0]}
    }

    let mapProps = {};
    if (mapReady && visibleLocations.length > 0) {

        mapProps = {
            bounds: visibleBounds
        }

    } else {
        mapProps = {
			center: ["48.41789", "-122.323702"],
			boundsOptions: { padding: [50, 100], maxZoom: 12 },
    	}
	}

	const intro = document.getElementById('mapped-posts-map-intro').innerHTML;

	const legendItems  = [
		{ slug: 'featured', label: 'Featured Property' },
		{ slug: 'open', label: 'Open to the Public' },
		{ slug: 'closed', label: 'Closed to the Public' }
	];

	const getLabel = (slug) => {
		const cat = legendItems.find(cat => cat.slug === slug);
		return cat ? cat.label : slug;
	}

	/** this method for getting the baselayer is specific to esri, to get the attribution correct, for other services you can use inside the <Map>
     * <TileLayer
        attribution='<a href="https://www.maptiler.com/copyright/" target="_blank" >&copy; MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">&copy; OpenStreetMap contributors</a>'
        url="https://api.maptiler.com/maps/basic/{z}/{x}/{y}.png?key=yRFimapDOtSxWTi4dx3l"
    />
     */

    return (
        <div className={`archive-map-wrapper`}>
        <div className={'archive-map-top'}>
			<div className={'archive-map-intro'} dangerouslySetInnerHTML={{__html: intro}}></div>
			{mapReady &&
				<>
				<div class="archive-map-filters">
					<Filters
					label={filterLabel}
					locations={locations}
					categories={categories}
					onUpdateLocations={onUpdateLocations}
					/>

				</div>
				</>
			}
		</div>
        <div className={'archive-map'}>


        <MapContainer {...mapProps} zoom={zoom} maxZoom={18} minZoom={8} zoomControl={false} attributionControl={false} ref={mapRef} scrollWheelZoom={false} boundsOptions={boundsOptions}>
		<VectorBasemapLayer
			name="ArcGIS:Streets"
			token={
				"AAPTxy8BH1VEsoebNVZXo8HurDipqhpEp1BTW9kCT8CIbsLhsRKSL8bzWv9KE1VvWJRZH_mMQtfek8WWYOd8X3BajIUD1ZTeWIpL0Voyxxiho5XgmUJlu9yj24UVxvGnBhKtM42sXmkSeukhRL0gQCW3QuyEpjZ7ukBs1qF0UJxTONlNnZgb7_4T__jlaXIJh1xsHgWEmDf7uJ-1kSz10DOQCzF7VAWfhh3C8V5Nj9o6q1o.AT1_ayS4xCp7"
			}
		/>
            {mapReady &&
                <Pins fitBounds={true} paddingTopLeft={[50, 50]} cluster={cluster} data={legendFilteredLocations} getLabel={getLabel} />
            }
            <ZoomControl position="topright" />
        	<AttributionControl position="bottomleft" />
			<Legend
				items={legendItems}
				position="topleft"
				interactive={legendInteractive}
				activeFilters={legendFilter}
				onFilterChange={handleLegendFilterToggle}
			/>
        </MapContainer>

        </div>

        </div>
    );
}

export default MapCluster;

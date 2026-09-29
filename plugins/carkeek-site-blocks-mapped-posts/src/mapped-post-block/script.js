import { useEffect, useState } from 'react';
//import { render } from 'react-dom';
import "leaflet/dist/leaflet.css";
import "./shared.scss";
import L from 'leaflet';
import { render } from 'react-dom';
import { getMarkerData, getCategoryData } from './components/getData';
import Map from './components/Map';
import {
  BrowserRouter as Router,
} from "react-router-dom";

import _ from 'lodash';
//with help from https://www.smashingmagazine.com/2020/06/rest-api-react-fetch-axios/

//http://genuine-skagit-valley.local/wp-json/wp/v2/ck_members?per_page=100&_fields=id,title,excerpt,ck_business_type,acf.member_address
function App(props){
  const { dataUrl, taxUrl, tax, cluster, address, taxonomy, filterLabel } = props;
    const [markersState, setMarkersState] = useState({
      loadingMarkers: true,
      markers: null,
      visible: null,
      bounds: []
    });
    const [catState, setCatState] = useState({
      loadingCategories: true,
      categories: null,
			ids: null,
    });


	let latField, lngField;

	const latlng = address.split('|');
	latField = latlng[0];
	lngField = latlng[1];
	const isLatitude = num => isFinite(num) && Math.abs(num) <= 90;
	const isLongitude = num => isFinite(num) && Math.abs(num) <= 180;

    const resolveMarkers = (markers) =>  {
        let usable = [];
        //only use if have lat lng
        markers.forEach( (marker) => {
			//marker.acf.member_address.lat
			const lat = parseFloat(_.get(marker, `acf.${latField}`));
			const lng = parseFloat(_.get(marker, `acf.${lngField}`));
			if (lat && lng && isLatitude(lat) && isLongitude(lng)) {
				marker.position = [lat, lng];
				marker.cats = marker.skgt_location_cat ? marker.skgt_location_cat : [];
				usable.push(marker);
			} else {
				console.log('Skipping marker with invalid lat lng:', marker);
			}
        });
        updateVisibleMarkers(usable);
        setMarkersState( (prevState) => {
          return {
            ...prevState,
            loadingMarkers: false,
            markers: usable,
            visible: usable,
            bounds: setBounds(usable)
          }
        });

    }

    const updateVisibleMarkers = (markers) => {
		setMarkersState( (prevState) => {
			if (_.isEqual(prevState.visible, markers)) {
				return prevState;
			}
			return {
				...prevState,
				visible: markers,
				bounds: setBounds(markers)
			}
		});
    }

    const setBounds = (markers) => {
        const bounds = L.latLngBounds();
        markers.forEach( (data) => {
            bounds.extend(data.position);
        })
        return bounds;
    }

    const sortCategoriesHierarchically = ((cats, into, parent=0 ) => {
      //copy the array into a new array that we pass back into the function
      let catsCopy = _.clone(cats);
      _.forEach( cats, function(item) {
          if (item.parent == parent){

              into.push(item);
              _.remove(catsCopy, cat => cat.id === item.id);
          }
      });

      into.forEach( (topCat) => {
          topCat.children = [];
          sortCategoriesHierarchically(catsCopy, topCat.children, topCat.id)
      })
  })



  const resolveCategories = (categories) => {
	let hierarchy = [];
    sortCategoriesHierarchically(categories, hierarchy, 0);
    setCatState( (prevState) => {
      return {
        ...prevState,
        loadingCategories: false,
        categories: hierarchy,
		ids: _.map(categories, "id")
      }
    });
  }

  useEffect(() => {
    getMarkerData(dataUrl, resolveMarkers);
  }, [setMarkersState]);

  useEffect(() => {
    getCategoryData(taxUrl, resolveCategories);
  }, [setCatState]);

  return(
    <Map
    isMapLoading={markersState.loadingMarkers}
    isCatLoading={catState.loadingCategories}
    categories={catState.categories}
	//allCategories={catState.ids} // setting this to all categories causes all to be selected at start
	allCategories={[]}
    locations={markersState.markers}
    visibleLocations={markersState.visible}
    visibleBounds={markersState.bounds}
    onUpdateLocations={updateVisibleMarkers}
    taxFilter={tax}
	filterLabel={filterLabel}
	cluster={cluster}
    zoom="6"
    />
  )
}

if (document.getElementById('mapped-posts-map')){
    const mapEl = document.getElementById('mapped-posts-map');
	const filterLabel = mapEl.getAttribute('data-filter-label');
    const dataUrl = mapEl.getAttribute('data-items');
    const taxUrl = mapEl.getAttribute('data-taxurl');
    const taxonomy = mapEl.getAttribute('data-taxonomy');
	const cluster = mapEl.getAttribute('data-cluster');
	const addressType = mapEl.getAttribute('data-addresstype');
	const address = mapEl.getAttribute('data-address');

    render(<Router><App filterLabel={filterLabel} dataUrl={dataUrl} cluster={cluster} taxUrl={taxUrl} tax={taxonomy} address={address} addressType={addressType} /></Router>, document.getElementById('mapped-posts-map'));
}

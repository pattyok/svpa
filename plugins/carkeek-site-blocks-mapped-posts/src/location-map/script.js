//import React, { Component } from 'react';
//import { render } from 'react-dom';
import "leaflet/dist/leaflet.css";
import { render } from 'react-dom';
import Map from "./components/Map";
import getMapData from "./components/getData";


function App(props) {
    return <Map zoom={18} locations={props.locations} />;
}

const mapElements = document.querySelectorAll(".location-map-wrapper");
mapElements.forEach(mapEl => {
    const locations = getMapData(mapEl);
	//console.log(locations);
    render(<App locations={locations} />, mapEl);
});

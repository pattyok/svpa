var _ = require("lodash");
import React, { useRef, useState, useEffect, use } from "react";
import FilterDropdown from './filters/FilterDropdown.js';
import FilterCheckboxTax from "./filters/FilterCheckboxTax.js";
import FilterCheckbox from "./filters/FilterCheckbox.js";
import FilterRadio from "./filters/FilterRadio.js";

const FilterList = ({ locations, categories, onUpdateLocations, label }) => {
	const [selectedSearch, setSelectedSearch] = useState(null);
	const [selectedCats, setSelectedCats] = useState(null);
	const [selectedType, setSelectedType] = useState(null);
	const [selectedAccess, setSelectedAccess] = useState(null);
	const [viewMode, setViewMode] = useState('search');
	const [filterActive, setFilterActive] = useState(false);

	const [typeOptions, setTypeOptions] = useState([]);
	const [accessOptions, setAccessOptions] = useState([]);

	function checkFilterActive() {
		if (selectedCats && selectedCats.length > 0) {
			return true;
		}
		if (selectedType && selectedType.length > 0) {
			return true;
		}
		if (selectedAccess && selectedAccess.length > 0) {
			return true;
		}
		if (selectedSearch) {
			return true;
		}
		return false;
	}

	//Actions to run when filters change all based on the state changes.
	useEffect(() => {
		if (locations && locations.length > 0){
			filterLocationsCat();
		}
	}, [selectedCats, locations]);


	useEffect(() => {
		if (locations && locations.length > 0){
			filterLocationsACF('loc_type', selectedType);
		}
	}, [selectedType, locations]);


	useEffect(() => {
		if (locations && locations.length > 0){
			filterLocationsACF('loc_public_access', selectedAccess);
		}
	}, [selectedAccess, locations]);

	useEffect(() => {
		if (locations && locations.length > 0){
			filterLocationsVal(selectedSearch?.value, 'id');
		}
	}, [selectedSearch, locations]);

	//Any time any filter changes, check if any are active
	useEffect(() => {
		const active = checkFilterActive();
		setFilterActive(active);
	}, [selectedCats, selectedType, selectedSearch, selectedAccess]);

	//Set options for type filter
	useEffect(() => {
		if (locations && locations.length > 0){
			const options = getFilterOptions(locations, 'loc_type');
			setTypeOptions(options);

		}
	}, [locations]);

	//Set options for access filter
	useEffect(() => {
		if (locations && locations.length > 0){
			const options = getFilterOptions(locations, 'loc_public_access');
			setAccessOptions(options);

		}
	}, [locations]);

	/** Reset all filters when view mode changes */
	useEffect(() => {
		resetAll();
	}, [viewMode]);


    const arrayContains = (arr1, arr2) => {
        return _.intersection(arr1, arr2).length > 0;
    }

	function filterLocationsVal( value, meta_key ) {
		if (!value) {
			onUpdateLocations(locations);
			return;
		}
		const filtered = _.filter(locations, loc => {
			if (loc[meta_key]) {
				return loc[meta_key] === value;
			}
			return false;
		});
		onUpdateLocations(filtered);
	}


	const filterLocationsCat = () => {
		let toFilter = [];
		toFilter = _.clone(locations);

		if (selectedCats && selectedCats.length > 0 ) {
			const visible = []
			toFilter.map((item) => {
				if (arrayContains(selectedCats, item.cats)) {
					visible.push(item);
				}
			})
			onUpdateLocations(visible);
		} else {
			onUpdateLocations(toFilter);
		}
	}

	//Filter by ACF field value
	function filterLocationsACF( meta_key, selected ) {
		if (!selected || selected.length === 0) {
			onUpdateLocations(locations);
			return;
		}
		const filtered = _.filter(locations, loc => {
			if (loc.acf && loc.acf[meta_key]) {
				//check if loc.acf[meta_key] is an object or single value.
				if (typeof loc.acf[meta_key] === 'object' && loc.acf[meta_key] !== null) {
					//If it's an object, check if it has a value property and compare that to selected
					if (loc.acf[meta_key].value) {
						return arrayContains(selected, [loc.acf[meta_key].value]);
					} else {
						//If it's an object but doesn't have a value property, check if it's an array and compare to selected
						if (Array.isArray(loc.acf[meta_key])) {
							const matchingValue = loc.acf[meta_key].find(value => selected.includes(value));
							return matchingValue !== undefined;
						}
					}
					return arrayContains(selected, [loc.acf[meta_key].value]);
				}
				return arrayContains(selected, [loc.acf[meta_key]]);
			}
			return false;
		});
		onUpdateLocations(filtered);
	}



	function getFilterOptions(data, key1) {
		let options = [];
		//manuallly doing this because acf returns values only - could programmatically get labels if needed
		if (key1 === 'loc_type') {
			options = [
				{ label: 'Easement', value: 'easement' },
				{ label: 'Trust-Assisted', value: 'trust-assisted' },
				{ label: 'Trust-Owned', value: 'trust-owned' }
			];
			return options;
		} else if (key1 === 'loc_public_access') {
			options = [
				{ label: 'Featured Property', value: 'featured' },
				{ label: 'Open to the Public', value: 'open' },
				{ label: 'Closed to the Public', value: 'closed' }
			];
			return options;
		}
		return options;

	}

	const resetAll = () => {
		setSelectedSearch(null);
		setSelectedCats(null);
		setSelectedType(null);
		setFilterActive(false);
		setSelectedAccess(null);
		//Reset locations to all
		onUpdateLocations(locations);
	}


    return (
			<>
			<div class="archive-filter-header">
				<FilterRadio
					options={[
						{ label: 'Property Name', value: 'search' },
						{ label: 'Access', value: 'loc_public_access' },
						{ label: 'Property Type', value: 'loc_type' },
						{ label: 'Activities', value: 'category' }
					]}
					label={label}
					selectedOption={viewMode}
					setSelectedOption={setViewMode}
					groupName="view-mode"
				/>

			</div>
			<div class="archive-filters-content">
			{viewMode === 'search' &&
				<FilterDropdown
					title="Search by Property"
					options={locations}
					setSelectedOption={setSelectedSearch}
					selected={selectedSearch}
				/>
			}
			{viewMode === 'category' &&
				<FilterCheckboxTax
					options={categories}
					setSelectedOptions={setSelectedCats}
					selectedOptions={selectedCats ? selectedCats : []}
				/>
			}
			{viewMode === 'loc_public_access' &&
				<FilterCheckbox
					options={accessOptions}
					setSelectedOptions={setSelectedAccess}
					selectedOptions={selectedAccess ? selectedAccess : []}
				/>
			}
			{viewMode === 'loc_type' &&
				<FilterCheckbox
					options={typeOptions}
					setSelectedOptions={setSelectedType}
					selectedOptions={selectedType ? selectedType : []}
				/>
			}
			{filterActive &&
			<button className="archive-filter-reset" onClick={resetAll}>
				Reset Filter
			</button>
			}
			</div>
			</>
    );
};

export default FilterList;

import axios from 'axios'

const getData = (page, url, data, resolve, reject) => {
	const query = `${url}&page=${page}`
    axios.get(query)
    .then(response => {
        const retrievedData = data.concat(response.data);
        if (response.headers['x-wp-totalpages'] > page) {
            getData(page+1, url, retrievedData, resolve, reject)
        } else {
            resolve(retrievedData)
        }
    }).catch(error => {
        console.log(error)
    })
}

export const getMarkerData = (url, resolve, reject) => {
    return getData(1, url, [], resolve, reject);
}

export const getCategoryData = (url, resolve, reject) => {
    return getData(1, url, [], resolve, reject);
}

export const getDetails = (url, resolve, reject) => {
    return getData(null, url, [], resolve, reject);
};



export default getMarkerData;

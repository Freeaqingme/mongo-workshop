<?php
$lat = 51.2221;
$lon = 4.3977;
if ( isset( $_GET['lat'] ) )
{
	$lat = (float) $_GET['lat'];
}
if ( isset( $_GET['lon'] ) )
{
	$lon = (float) $_GET['lon'];
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>OpenStreetMap in MongoDB example</title>
	<meta charset="utf-8" />
    <style>
body {
    padding: 0;
    margin: 0;
}
html, body, #map {
    height: 100%;
}
#search {
	z-index: 1000;
	position: absolute;
	top: 10px;
	right: 30px;
	width: 250px;
	background-color: white;
	border: 1px solid black;
	padding: 4px;
}
    </style>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="stylesheet" href="leaflet.css" />
	<!--[if lte IE 8]><link rel="stylesheet" href="leaflet.ie.css" /><![endif]-->
</head>

<body onLoad="changeLocation(false);">
	<div id="map"></div>

	<script src="leaflet.js"></script>
	<script type="text/javascript" src="jquery-1.7.2.min.js"></script>

	<script>
		var map = new L.Map('map');

		var OpenStreetMapUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			OpenStreetMapAttribution = 'Map data &copy; 2011 OpenStreetMap contributors',
			OpenStreetMap = new L.TileLayer(OpenStreetMapUrl, {maxZoom: 18, attribution: OpenStreetMapAttribution});

		map.setView(new L.LatLng(<?php echo $lat; ?>, <?php echo $lon; ?>), 17).addLayer(OpenStreetMap);

		var geojsonMarkerOptions = {
			radius: 12,
			fillColor: "#8888ff",
			color: "#33f",
			weight: 1,
			opacity: 1,
			fillOpacity: 0.6
		};
		var geojsonMarkerOptionsC = {
			radius: 12,
			fillColor: "#ff0000",
			color: "#33f",
			weight: 1,
			opacity: 1,
			fillOpacity: 0.6
		};
		var geojsonLineOptions = {
			fillColor: "#8888ff",
			color: "#33f",
			weight: 3,
			opacity: 1,
			width: 3,
			fillOpacity: 0.6
		};
		var geojsonAreaOptions = {
			radius: 8,
			fillColor: "#8888ff",
			color: "#33f",
			weight: 1,
			opacity: 1,
			fillOpacity: 0.2
		};

		var geoJsonOptions = {
			pointToLayer: function (latlng) {
				myOptions = geojsonMarkerOptions;
				myOptions.radius = calcCircleSize();
				return new L.CircleMarker(latlng, myOptions);
			}
		}

		var geojsonLayer = new L.GeoJSON(null, geoJsonOptions);
		map.addLayer(geojsonLayer);
		geojsonLayer.on("featureparse", function (e) {
			if (e.properties && e.properties.popupContent){
				e.layer.bindPopup(e.properties.popupContent);
			}
			if (e.properties && e.properties.style && e.layer.setStyle) {
				e.layer.setStyle(e.properties.style);
			} else if (e.geometryType == 'Polygon' && map.getZoom() >= 16) {
				e.layer.setStyle(geojsonAreaOptions);
			} else if (e.geometryType == 'LineString') {
				e.layer.setStyle(geojsonLineOptions);
			} else {
				if (e.properties.changed) {
					e.layer.setStyle(geojsonMarkerOptionsC);
				} else {
					e.layer.setStyle(geojsonMarkerOptions);
				}
			}
		});

		map.on('moveend', changeLocation);

		var popup = new L.Popup();

		function calcCircleSize() {
			z = map.getZoom();
			return Math.min(12, Math.max(2, 15 - ((15-z) * 3)));
		}

		function changeLocation(event) {
			center = map.getCenter();

			$.ajax({
			  url: "fetch-poi.php" + '?lat=' + center.lat + '&lon=' + center.lng + '&d=1',
			  beforeSend: function ( xhr ) {
				xhr.overrideMimeType("text/plain; charset=x-user-defined");
			  }
			}).done(function ( data ) {
				geojsonLayer.clearLayers();
				res = jQuery.parseJSON(data);
				res.forEach( function(value) {
					if (map.getZoom() < 16 && value.geometry.type == 'Polygon') {
						value.geometry.type = 'Point';
						value.geometry.coordinates = value.geometry.coordinates[0][0];
					}
					geojsonLayer.addGeoJSON(value);
				
				} );
			});
		}
	</script>
</body>
</html>

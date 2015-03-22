<?php
/*
 * Plugin Name: mapper
 * Version: 1.0
 * Plugin URI: http://ingmmo.com
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Marco Montanari
 * Author URI: http://ingmmo.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: mapper
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-mapper.php' );
require_once( 'includes/class-mapper-settings.php' );	

// Load plugin libraries
require_once( 'includes/lib/class-mapper-admin-api.php' );
require_once( 'includes/lib/class-mapper-post-type.php' );
require_once( 'includes/lib/class-mapper-taxonomy.php' );

/**
 * Returns the main instance of mapper to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object mapper
 */
function mapper () {
	$instance = mapper::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = mapper_Settings::instance( $instance );
	}

	return $instance;
}

mapper();


add_filter( 'rwmb_meta_boxes', 'your_prefix_register_meta_boxes' );

function your_prefix_register_meta_boxes( $meta_boxes )
{
	$prefix = 'unicatt_';
	
	$meta_boxes[] = array(
		'title'  => __( 'Mappa', 'meta-box' ),
		'post_types' => array( 'post', 'page' ),
		'fields' => array(
			array(
				'id'            => 'address',
				'name'          => __( 'Indirizzo', 'meta-box' ),
				'type'          => 'text',
			),
			array(
				'name' => __( 'Mappa', 'meta-box' ),
				'id'   => "{$prefix}map",
				'type' => 'map',
				'style'         => 'width: 500px; height: 500px',
				'address_field' => 'address',  
			),
			array(
				'name' => __( 'Color picker', 'meta-box' ),
				'id'   => "{$prefix}color",
				'type' => 'color',
				'std'  => '#ffffff'
			),
		)
	);
	return $meta_boxes;
}



//[map bg="SpaceStationEarth" height="500px" center="45.4671,9.1646" cat="storie"]
function map_func( $ratts ){
	$atts = shortcode_atts(
		array(
			"cat"=>"storie",
			"center" => "45.4671,9.1646",
			"height" => "500px"
		), $ratts);
	wp_enqueue_script("mbljs", 'https://api.tiles.mapbox.com/mapbox.js/v2.1.6/mapbox.js');
	wp_enqueue_style("mbls", "https://api.tiles.mapbox.com/mapbox.js/v2.1.6/mapbox.css");
	
	$posts = get_posts(array("posts_per_page"=>1000, "category_name"=>$atts["cat"]));
	$val = array();
	foreach ($posts as $post) {
		if (get_post_meta($post->ID,"unicatt_map", true) != "")
			$val[] = array("text"=>$post->post_excerpt, "title"=>$post->post_title, "id"=>$post->ID, "coords"=>explode(",", get_post_meta($post->ID,"unicatt_map", true)), "color"=>get_post_meta($post->ID, "unicatt_color", true), "url"=>get_permalink( $post->ID));
	}
	ob_start();
	?>
	<div id="map" style="width:100%; height:<?php echo $atts["height"];?>" ></div>
	<script>
	var data = <?php echo json_encode($val);?>;

	jQuery(function(){
		L.mapbox.accessToken = "pk.eyJ1Ijoic2lybW1vLXVuaWNhdHQiLCJhIjoiTVA3N0x4USJ9.ccVpbxWlHQe7ovfPoEBYkg";
		var mapboxTiles = L.tileLayer('https://{s}.tiles.mapbox.com/v3/examples.3hqcl3di/{z}/{x}/{y}.png?access_token=' + L.mapbox.accessToken, {
	    	attribution: '<a href="http://www.mapbox.com/about/maps/" target="_blank">Terms &amp; Feedback</a>'
		});
		var map = L.map('map')
		    .addLayer(mapboxTiles)
		    .setView(<?php echo json_encode(explode(",", $atts["center"]));?>, 15);

		function create_marker(data){
			var icon = L.mapbox.marker.icon({"marker-size":"large", "marker-color":data.color});
			L.marker(data.coords, {icon: icon}).bindPopup("<h2><a href='"+data.url+"'>"+data.title+"</a></h2><p>"+data.text+"</p>").addTo(map);
		}

		for (var x in data){
			create_marker(data[x]);
		}


	});
	</script>


	<?php
	return ob_get_clean();
}
add_shortcode( 'map', 'map_func' );
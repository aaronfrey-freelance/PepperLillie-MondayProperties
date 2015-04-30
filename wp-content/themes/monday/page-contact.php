<?php while (have_posts()) : the_post(); ?>
 
	<div id="slides" class="hidden-xs">
		<ul class="slides-container">    
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/contact.jpg"></li>
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/contact.jpg"></li>
		</ul>
	</div>

	<img class="mobile-image-header img-responsive visible-xs" src="<?php bloginfo('template_url'); ?>/dist/images/contact.jpg">

	<div class="mainwhite" align="center">

		<div class="maincontent">

			<div class="container">

				<div class="row">

					<div class="col-md-12">

						<?php get_template_part('templates/page', 'header'); ?>
						<?php get_template_part('templates/content', 'page'); ?>

					</div>

				</div>
				
				<div class="row">	

					<?php $args = array(
						'posts_per_page'   => 10,
						'category_name'    => 'location',
						//'orderby'          => 'post_date',
						//'order'            => 'DESC'
					);
					$posts_array = get_posts( $args );
					foreach ($posts_array as $post) : setup_postdata( $post ); ?>
						
						<div class="col-sm-6 contactlocation">

							<strong><?php echo the_title(); ?></strong></br>
							<?php echo the_content(); ?>
							<?php 
							$location = get_field('location_map');

							if( !empty($location) ): ?>
							<div class="location-map">
								<a class="view-larger rounded" href="https://www.google.com/maps?saddr=My+Location&daddr=<?php $location = get_field('location_map'); echo $location['lat'] . ',' . $location['lng']; ?>" target="_blank">
									<div>View larger map</div>
								</a>
								<div class="acf-map">
									<div class="marker" data-lat="<?php echo $location['lat']; ?>" data-lng="<?php echo $location['lng']; ?>"></div>
								</div>
							</div>
							<?php endif; ?>
						</div>

					<?php endforeach; wp_reset_postdata(); ?>


<!-- 						<div class="col-sm-6 contactlocation">
						<strong>New York</strong></br>
						230 Park Avenue</br>
						New York, NY 10169</br>
						Phone: 212-490-7100 &nbsp; | &nbsp; Fax: 212-490-7266
						<br><br>
						<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.3422957114076!2d-73.97585680000002!3d40.75449559999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c258fdf6b7fa77%3A0xbaacdcfdaeae65f4!2s230+Park+Ave%2C+New+York%2C+NY+10169!5e0!3m2!1sen!2sus!4v1420825518833" width="368" height="166" frameborder="0" style="border:solid;border-color:#c8c8c9;border-width:1px;"></iframe>
					</div>

					<div class="col-sm-6 contactlocation">
						<strong>Washington, D.C.</strong></br>
						1000 Wilson Boulevard, Suite 700</br>
						Arlington, VA 22209 </br>
						Phone: 703-284-0200 &nbsp; | &nbsp; Fax: 703-524-7667 
						<br><br>
						<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3105.324767122013!2d-77.068624!3d38.893688000000004!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89b7b659e547cec7%3A0xa82b8f135173d81!2sMonday+Properties!5e0!3m2!1sen!2sus!4v1420825630730" width="368" height="166" frameborder="0" style="border:solid;border-color:#c8c8c9;border-width:1px;"></iframe>
					</div> -->

				</div>

			</div>

		</div>

		<?php
		  get_template_part('templates/footer');
		  wp_footer();
		?>

	</div>

<?php endwhile; ?>

<style type="text/css">

.acf-map {
	width: 100%;
	height: 166px;
	border: #ccc solid 1px;
	margin: 20px 0;
}

</style>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
<script type="text/javascript">
(function($) {

function render_map( $el ) {

	// var
	var $markers = $el.find('.marker');

	// vars
	var args = {
		zoom		: 15,
		center		: new google.maps.LatLng(0, 0),
		mapTypeId	: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: true
	};

	// create map	        	
	var map = new google.maps.Map( $el[0], args);

	// add a markers reference
	map.markers = [];

	// add markers
	$markers.each(function(){
    	add_marker( $(this), map );
	});

	// center map
	center_map( map );
}

function add_marker( $marker, map ) {

	// var
	var latlng = new google.maps.LatLng( $marker.attr('data-lat'), $marker.attr('data-lng') );

	// create marker
	var marker = new google.maps.Marker({
		position	: latlng,
		map			: map
	});

	// add to array
	map.markers.push( marker );
}

function center_map( map ) {

	// vars
	var bounds = new google.maps.LatLngBounds();

	// loop through all markers and create bounds
	$.each( map.markers, function( i, marker ){

		var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );

		bounds.extend( latlng );

	});

	// only 1 marker?
	if( map.markers.length == 1 )
	{
		// set center of map
	    map.setCenter( bounds.getCenter() );
	    map.setZoom( 15 );
	}
	else
	{
		// fit to bounds
		map.fitBounds( bounds );
	}

}

/*
*  document ready
*
*  This function will render each map when the document is ready (page has loaded)
*
*  @type	function
*  @date	8/11/2013
*  @since	5.0.0
*
*  @param	n/a
*  @return	n/a
*/

$(document).ready(function(){

	$('.acf-map').each(function(){

		render_map( $(this) );

	});

});

})(jQuery);
</script>
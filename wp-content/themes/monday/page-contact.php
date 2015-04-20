<?php while (have_posts()) : the_post(); ?>
 
	<div class="mainwhite" align="center">

		<div class="maincontent">

			<?php get_template_part('templates/page', 'header'); ?>
			<?php get_template_part('templates/content', 'page'); ?>

			<div class="contactrow">

				<div class="contactlocation">
					<strong>New York</strong></br>
					230 Park Avenue</br>
					New York, NY 10169</br>
					Phone: 212-490-7100 &nbsp; | &nbsp; Fax: 212-490-7266
					<br><br>
					<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.3422957114076!2d-73.97585680000002!3d40.75449559999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c258fdf6b7fa77%3A0xbaacdcfdaeae65f4!2s230+Park+Ave%2C+New+York%2C+NY+10169!5e0!3m2!1sen!2sus!4v1420825518833" width="368" height="166" frameborder="0" style="border:solid;border-color:#c8c8c9;border-width:1px;"></iframe>

				</div>

				<div class="contactlocation">
					<strong>Washington, D.C.</strong></br>
					1000 Wilson Boulevard, Suite 700</br>
					Arlington, VA 22209 </br>
					Phone: 703-284-0200 &nbsp; | &nbsp; Fax: 703-524-7667 
					<br><br>
					<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3105.324767122013!2d-77.068624!3d38.893688000000004!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89b7b659e547cec7%3A0xa82b8f135173d81!2sMonday+Properties!5e0!3m2!1sen!2sus!4v1420825630730" width="368" height="166" frameborder="0" style="border:solid;border-color:#c8c8c9;border-width:1px;"></iframe>
				</div>

			</div>

		</div>

	</div>

	<div id="slides">
		<ul class="slides-container">    
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/contact.jpg" width="1680" height="1200"></li>
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/contact.jpg" width="1680" height="1200"></li>
		</ul>
	</div>

<?php endwhile; ?>

<div id="slides" class="hidden-xs">
	<ul class="slides-container">    
		<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
		<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
	</ul>
</div>

<img class="mobile-image-header img-responsive visible-xs" src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg">

<div class="mainwhite" align="center">

	<div class="maincontent">

		<div class="container">
			
			<div class="row">
				
				<div class="col-md-12">
					<?php get_template_part('templates/page', 'header'); ?>
				</div>

			</div>

			<?php global $query_string; query_posts( $query_string . '&orderby=title&order=ASC' ); ?>

			<?php if (have_posts()) : while (have_posts()) : the_post(); $count = $wp_query->current_post + 1; ?>

			<?php if($count % 4 === 1) : ?>
			<div class="row tenantrow">
			<?php endif; ?>

				<div class="col-xs-12 col-sm-3 col-md-3 tenant">

					<div class="inner-tenant">
						<?php 
						if ( has_post_thumbnail() ) {
							the_post_thumbnail('property');
						} 
						?>
						<div><?php the_title(); ?></div>
						<a href="<?php the_field('property_handbook_link'); ?>" target="_blank"> > TENANT HANDBOOK</a>
					</div>

				</div>
			
			<?php if($count % 4 === 0) : ?>
			</div>
			<?php endif; ?>

			<?php endwhile; endif; ?>

		</div>

	</div>

	<?php
	  get_template_part('templates/footer');
	  wp_footer();
	?>

</div>
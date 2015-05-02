<?php while (have_posts()) : the_post(); ?>

<div id="slides" class="hidden-xs">
	<ul class="slides-container">    
		<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
		<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
	</ul>
</div>

<img class="mobile-image-header img-responsive visible-xs" src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg">

<div class="mainwhite team-single" align="center">

	<div class="maincontent">

		<div class="container">
			
			<div class="row">
				
				<div class="col-sm-4">
					<h1>Team</h1>
					<h3><?php echo the_title(); ?></h3>
					<p class="job-title"><?php echo the_field('job_title'); ?></p>
					<?php 
					if ( has_post_thumbnail() ) {
						the_post_thumbnail();
					} 
					?>
					<a class="btn btn-default btn-block v-card">
						Download v-card
						<i class="pull-right fa fa-angle-down"></i>
					</a>
				</div>

				<div class="col-sm-8">
					<a href="<?php bloginfo('url'); ?>/team" class="hidden-xs btn btn-default back pull-right">>Back to all team members</a>
					<div class="clearfix"><?php echo the_content(); ?></div>
					<a href="<?php bloginfo('url'); ?>/team" class="visible-xs btn btn-default btn-block back pull-right">>Back to all team members</a>
				</div>

			</div>

		</div>

	</div>

	<?php
	  get_template_part('templates/footer');
	  wp_footer();
	?>

</div>

<?php endwhile; ?>

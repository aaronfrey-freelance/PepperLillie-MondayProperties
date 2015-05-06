<?php while (have_posts()) : the_post(); ?>

<?php cfi_featured_image( array('size' => 'full', 'class' => 'img-responsive', 'cat_id' => '4')); ?>

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

					<?php if(get_field('vcard')) : ?>
					<a href="<?php the_field('vcard'); ?>" class="btn btn-default btn-block v-card">
						Download v-card
						<i class="pull-right fa fa-angle-down"></i>
					</a>
					<?php endif; ?>

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

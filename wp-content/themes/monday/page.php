<?php while (have_posts()) : the_post(); ?>

	<?php 
	if ( has_post_thumbnail() ) {
		the_post_thumbnail('full', ['class' => 'img-responsive']);
	} 
	?>

	<div class="mainwhite" align="center">

		<div class="maincontent">

			<div class="container">

				<div class="row">

					<div class="col-md-12">
						<?php get_template_part('templates/page', 'header'); ?>
						<?php get_template_part('templates/content', 'page'); ?>
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

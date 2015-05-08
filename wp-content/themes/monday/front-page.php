<?php while (have_posts()) : the_post(); ?>

	<?php echo do_shortcode('[nggallery id=2 template=home]'); ?>

  	<?php get_template_part('templates/content', 'page'); ?>

	<?php
	  get_template_part('templates/footer');
	  wp_footer();
	?>

<?php endwhile; ?>

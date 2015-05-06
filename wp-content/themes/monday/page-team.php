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
						<h3>Leadership</h3>
						<?php get_template_part('templates/content', 'page'); ?>
						<h5>Executive Biographies</h5>
						<br>

					</div>

				</div>

			</div>

			<div class="container team-container">

				<div class="row">

					<?php $args = array(
						'posts_per_page'   => 100,
						'category_name'    => 'team'
					);
					$posts_array = get_posts( $args );
					foreach ($posts_array as $post) : setup_postdata( $post ); ?>
						<a
							href="<?php the_permalink(); ?>"
							class="team-member col-sm-3 col-xs-6">
							<?php 
							if ( has_post_thumbnail() ) {
								the_post_thumbnail();
							} 
							?>
							<div class="team-title text-center">
								<?php the_title(); ?>
							</div>
						</a>

					<?php endforeach; wp_reset_postdata(); ?>

				</div>

			</div>

		</div>

		<?php
		  get_template_part('templates/footer');
		  wp_footer();
		?>

	</div>

<?php endwhile; ?>

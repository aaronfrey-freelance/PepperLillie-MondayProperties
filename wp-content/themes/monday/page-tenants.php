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

				<?php 
					$args = array(
						'posts_per_page'   	=> 100,
						'category_name'    	=> 'tenants',
						'orderby' 			=> 'title',
						'order' 			=> 'ASC'
					);

					$posts_array = get_posts( $args );
					$count = 0;
				?>

				<?php foreach ($posts_array as $post) : setup_postdata( $post ); ?>

				<?php $count = $count + 1; if($count % 4 === 1) : ?>

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

				<?php endforeach; wp_reset_postdata(); ?>

			</div>

		</div>

		<?php
		  get_template_part('templates/footer');
		  wp_footer();
		?>

	</div>

<?php endwhile; ?>

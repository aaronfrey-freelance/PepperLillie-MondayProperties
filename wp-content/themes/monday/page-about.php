<?php while (have_posts()) : the_post(); ?>
 
	<div id="slides" class="hidden-xs">
		<ul class="slides-container">    
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/about.jpg"></li>
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/about.jpg"></li>
		</ul>
	</div>

	<img class="mobile-image-header img-responsive visible-xs" src="<?php bloginfo('template_url'); ?>/dist/images/about.jpg">

	<div class="mainwhite" align="center">

		<div class="maincontent">

			<div class="container">

				<div class="row">

					<div class="col-md-12">
						<?php get_template_part('templates/page', 'header'); ?>

						<div class="nav-container">

							<ul class="hidden-xs nav nav-tabs sub-nav">
								<li role="presentation" class="active">
									<a href="<?php bloginfo('url'); ?>/about">Overview</a>
								</li>
								<li role="presentation">
									<?php $postslist = get_posts('category=7&numberposts=1&order=DESC&orderby=post_date');
									     foreach ($postslist as $post) :
									     setup_postdata($post); ?>
									   <a href="<?php the_permalink() ?>">Case Studies</a>
									<?php endforeach; ?>
								</li>
							</ul>

						</div>

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

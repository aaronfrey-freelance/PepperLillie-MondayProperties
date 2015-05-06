<?php while (have_posts()) : the_post(); $postid = get_the_ID(); ?>
 
	<?php cfi_featured_image( array('size' => 'full', 'class' => 'img-responsive', 'cat_id' => '7')); ?>

	<div class="mainwhite" align="center">

		<div class="maincontent">

			<div class="container">

				<div class="row">

					<div class="col-md-12">

						<h1>About</h1>

						<p class="visible-xs success">Success Stories</p>

						<div class="hidden-xs nav-container">

							<ul class="nav nav-tabs sub-nav">
								<li role="presentation">
									<a href="<?php bloginfo('url'); ?>/about">Overview</a>
								</li>
								<li role="presentation" class="active">
									<?php $postslist = get_posts('category=7&numberposts=1&order=DESC&orderby=post_date');
									     foreach ($postslist as $post) :
									     setup_postdata($post); ?>
									   <a href="<?php the_permalink() ?>">Case Studies</a>
									<?php endforeach; ?>
								</li>
							</ul>

							<ul class="nav nav-pills case-study-nav">

								<?php $args = array(
									'posts_per_page'   => 10,
									'category_name'    => 'case_study',
									//'orderby'          => 'post_date',
									//'order'            => 'DESC'
								);
								$posts_array = get_posts( $args );
								foreach ($posts_array as $post) : setup_postdata( $post ); ?>

									<li role="presentation" class="<?php echo $postid == $post->ID ? 'active' : ''; ?>">
										<a href="<?php echo the_permalink(); ?>">
											<?php echo the_title(); ?>
										</a>
									</li>

								<?php endforeach; wp_reset_postdata(); ?>

							</ul>

						</div>

						<h3><?php echo the_title(); ?></h3>

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

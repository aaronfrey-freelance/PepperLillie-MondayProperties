<?php while (have_posts()) : the_post(); ?>

	<div id="slides">
    
		<ul class="slides-container">   

			<li>

				<img src="<?php bloginfo('template_url'); ?>/dist/images/home1.jpg" width="1680" height="1200">
				<div class="headlinecontainer">
					Real Estate Investor and Operator Aiming to Deliver Superior Risk-Adjusted Performance through Targeted U.S. and Select International Investments.
					<p></p>
				</div>
			</li>

			<li>
				<img src="<?php bloginfo('template_url'); ?>/dist/images/home2.jpg" width="1680" height="1200">
				<div class="headlinecontainer">
					Dedicated to Pursuing Differentiated Investment and Competitive Strategies with Emphasis on Capital Preservation.
				<p></p>
				</div>
			</li>

			<li>
				<img src="<?php bloginfo('template_url'); ?>/dist/images/home3.jpg" width="1680" height="1200">
				<div class="headlinecontainer">
					Committed to the Values of Integrity, Excellence, Investor Alignment and Service.
					<p></p>
				</div>
			</li>

			<li>
				<img src="<?php bloginfo('template_url'); ?>/dist/images/home4.jpg" width="1680" height="1200">
				<div class="headlinecontainer">
					Tailored Investment Offerings to Provide Investors with Selection and to Optimize Capital Partnership Structures.
					<p></p>
				</div>
			</li>

		</ul>

	</div>

  	<?php get_template_part('templates/content', 'page'); ?>

<?php endwhile; ?>

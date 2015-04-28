<?php while (have_posts()) : the_post(); ?>

	<div id="slides">
		<ul class="slides-container">    
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/tenants.jpg"></li>
		</ul>
	</div>

	<div class="mainwhite" align="center">

		<div class="maincontent">

			<div class="container">
				
				<div class="row">
					
					<div class="col-md-12">
						
						<?php get_template_part('templates/page', 'header'); ?>
						<?php get_template_part('templates/content', 'page'); ?>

					</div>

				</div>

				<div class="row tenantrow">

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<div class="inner-tenant">
							<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1000wilson.jpg" width="47" height="63">
							<div>1000 Wilson Boulevard</div>
							<a href="http://www.1000wilsonblvd.info/toc.cfm" target="_blank"> > TENANT HANDBOOK</a>
						</div>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1100wilson.jpg" width="47" height="63">
						<div>1100 Wilson Boulevard</div>
						<a href="http://www.1100wilsonblvd.info/toc.cfm?CFID=635595&CFTOKEN=82001888" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1101wilson.jpg" width="47" height="63">
						<div>1101 Wilson Boulevard</div>
						<a href="http://www.1101wilsonblvd.info/toc.cfm?CFID=635733&CFTOKEN=76766645" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1200wilson.jpg" width="47" height="63">
						<div>1200 Wilson Boulevard</div>
						<a href="http://www.1200wilsonblvd.info/coming_soon.cfm" target="_blank"> > TENANT HANDBOOK</a>
					</div>
				
				</div>

				<div class="row tenantrow">

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1400key.jpg" width="47" height="63">
						<div>1400 Key Boulevard</div>
						<a href="http://www.1400keyblvd.info/toc.cfm?CFID=635834&CFTOKEN=10435528" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1401wilson.jpg" width="47" height="63">
						<div>1401 Wilson Boulevard</div>
						<a href="http://www.1401wilsonblvd.info/toc.cfm?CFID=635967&CFTOKEN=74123853" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1501wilson.jpg" width="47" height="63">
						<div>1501 Wilson Boulevard</div>
						<a href="http://www.1501wilsonblvd.info/toc.cfm?CFID=635887&CFTOKEN=75283438" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1515wilson.jpg" width="47" height="63">
						<div>1515 Wilson Boulevard</div>
						<a href="http://www.1515wilsonblvd.info/toc.cfm?CFID=635960&CFTOKEN=31901447" target="_blank"> > TENANT HANDBOOK</a>
					</div>

				</div>

				<div class="row tenantrow">

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1701northfort.jpg" width="47" height="63">
						<div>1701 North Fort Myer Drive</div>
						<a href="http://www.1701nfortmyerdr.info/toc.cfm?CFID=636041&CFTOKEN=32830598" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/1812north.jpg" width="47" height="63">
						<div>1812 North Moore</div>
						<a href="http://1812northmoore.com/#firstPage" target="_blank"> > TENANT HANDBOOK</a>
					</div>

					<div class="col-xs-12 col-sm-3 col-md-3 tenant">
						<img src="<?php bloginfo('template_url'); ?>/dist/images/thumbs/21002nd.jpg" width="47" height="63">
						<div>2100 2nd Street</div>
						<a href="http://21002ndstreetsw.info/toc.cfm" target="_blank"> > TENANT HANDBOOK</a>
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

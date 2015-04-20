<?php while (have_posts()) : the_post(); ?>
 
	<div class="mainwhite" align="center">

		<div class="maincontent">

			<?php get_template_part('templates/page', 'header'); ?>
			<?php get_template_part('templates/content', 'page'); ?>

			Monday Properties was founded in 1998 and is a leading real estate investment firm with fully developed value-creation and operational capabilities.  We aim to deliver superior risk-adjusted performance for our investors by initiating, developing and executing differentiated investment and competitive strategies in highly targeted, attractive growth and distressed sectors and markets, principally in the United States and in select international locations.  We seek differentiation and sustained competitive advantages for our investment strategies.  <br>
			We emphasize risk mitigation and capital preservation.<br><br>

			<div class="aboutheadline">We are dedicated to pursuing purpose, excellence, investor alignment, transparency <br>
			and service as core long-term values.</div>

			We form our investment strategies by identifying and triangulating nascent and dynamic macroeconomic, demographic, monetary, consumer, industry, government, regional and/or capital markets trends and seek to capitalize on the resulting opportunities.<br><br>
			The firm tailors its investment offerings to investors through various formats appropriate for the strategy at hand, and for investor needs and requirements.  Monday Properties aims to enhance its investments by procuring prudent leverage under optimal terms through its knowledge of, and relationships with, debt sources in the private and public markets.  Careful attention is paid to downside analysis, proactive value creation and risk management, and timely exits.<br><br>
			Our current and past investors include leading institutional investors, including real estate investment management firms (core open-end funds), real estate opportunity funds, sovereign wealth funds, and insurance companies, as well as high net worth individual investors investing either directly or through commingled vehicles.  Our principals have extensive experience as fiduciaries of large, and in some instances global, closed-end real estate private equity funds.<br><br>
			Monday Properties possesses in-house execution capabilities in a vertically integrated format including finance and investment management, asset management, financial and tax reporting, as well as specialized real estate capabilities in leasing, property management, construction, and development.  These in-house capabilities are critical to the value creation process. Where appropriate, Monday Properties augments such capabilities with third party partners and relationships.<br><br>
			We are dedicated to pursuing purpose, excellence, investor alignment, transparency and service as core long-term values.  The firm seeks to achieve its objectives through an ethos of leadership, teamwork, creativity, communication, and a strong work ethic.

		</div>

	</div>

	<div id="slides">
		<ul class="slides-container">    
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/about.jpg" width="1680" height="1200"></li>
			<li><img src="<?php bloginfo('template_url'); ?>/dist/images/about.jpg" width="1680" height="1200"></li>
		</ul>
	</div>

<?php endwhile; ?>

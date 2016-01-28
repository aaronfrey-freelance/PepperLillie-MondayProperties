<footer class="content-info" role="contentinfo">

	<div class="footertext">
		&copy; <?= date('Y'); ?> Monday Properties.  All Rights Reserved  &nbsp; | &nbsp;<a href="<?php bloginfo('url'); ?>/contact">Contact Us</a>
		<span class="hidden-xs">&nbsp; | &nbsp;<a href="<?php bloginfo('url'); ?>/careers">Careers</a></span>
	</div>

	<div class="socialmedia">

	<?php

	$term = get_term_by('slug', 'social-links', 'nav_menu');
	$items = wp_get_nav_menu_items($term->term_id);

	foreach ($items as $key => $menuitem) : ?>
		<!-- <a href="<?php echo $menuitem->url; ?>" target="_blank">
			<i class="<?php echo implode(' ', $menuitem->classes) ?>"></i>
		</a> -->
	<?php endforeach; ?>

	</div>

	<?php dynamic_sidebar('sidebar-footer');?>

</footer>
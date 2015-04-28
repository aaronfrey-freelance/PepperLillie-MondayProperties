<?php

use Roots\Sage\Config;
use Roots\Sage\Wrapper;

?>

<?php get_template_part('templates/head'); ?>

  <body <?php body_class(); ?>>

    <?php
      do_action('get_header');
      get_template_part('templates/header');
    ?>

    <div class="wrap container-fluid" role="document">
      <div class="content row">
        <?php include Wrapper\template_path(); ?>
      </div><!-- /.content -->
    </div><!-- /.wrap -->

    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.easing.1.3.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.animate-enhanced.min.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.superslides.js" type="text/javascript" charset="utf-8"></script>
    <script>
      if($('#slides img').length > 1) {
        $('#slides').superslides({
          animation: 'fade'
        });
      }
    </script>

  </body>
</html>

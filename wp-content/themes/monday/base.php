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
    
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/image-scale.min.js" type="text/javascript"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.easing.1.3.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.animate-enhanced.min.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/jquery.superslides.js" type="text/javascript" charset="utf-8"></script>
    <script src="<?php bloginfo('template_url'); ?>/assets/scripts/fancybox/jquery.fancybox.pack.js?v=2.1.5" type="text/javascript"></script>
    <script>

      jQuery(document).ready(function($) {

        $('.fancybox.iframe').on('click', function(e) {
          e.preventDefault();
          $.fancybox.open({
              padding : 0,
              href:'<?php echo get_home_url(); ?>/investors.html',
              type: 'iframe'
          });
        });

        function equalCols() {
          if($(window).width() > 479) {
            $('.case-image').height($('.case-description-col').height());
            $('.image-pagination').css('top', 0);
          } else {
            $('.case-image').height('auto');
            $('.image-pagination').css('top', $('.case-image img').height());
          }
        }

        var caseStudy = $('.ngg-casestudy .case-study').first();
        var csHeight = $(caseStudy).height();
        $('.ngg-casestudy').height(csHeight);
        $(caseStudy).css('visibility', 'visible').addClass('active');

        $('.image-pagination a').on('click', function(e) {
          e.preventDefault();
          $('.image-pagination a').removeClass('active');
          $(this).addClass('active');
          
          var idx = parseInt($(this).text()) - 1;
          caseStudy = $('.ngg-casestudy .case-study')
            .removeClass('active')
            .css('visibility', 'hidden')
            .eq(idx);
          var csHeight = $(caseStudy).height();

          $('.ngg-casestudy').height(csHeight);
          $(caseStudy).css('visibility', 'visible')
            .addClass('active')
            .find('img')
            .imageScale('scale');
        });

        equalCols();

        $( window ).resize(function() {
          equalCols();
          $('.ngg-casestudy').height($('.case-study.active').height());
        });

        $("img.scale").imageScale({
          fadeInDuration: 1000,
          rescaleOnResize: true
        });

        $('#slides').superslides({
          animation: 'fade'
        });

        $(document).on('click', '#mobile-nav-close', function() {
          $('.navbar-collapse').collapse('hide');
        });
      });


    </script>

  </body>

</html>

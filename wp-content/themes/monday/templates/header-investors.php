<?php
    $current_user = wp_get_current_user();
?>

<header>

    <a class="logo" href="<?=esc_url(home_url('/'));?>">
        <img src="<?php bloginfo('template_url');?>/dist/images/logo.png">
    </a>

</header>

<div class="container welcome">
    
    <div class="row">
        
        <div class="col-md-12">
            
            <div>
                Welcome, <?php echo $current_user->user_login; ?> | <a href="<?php echo wp_logout_url(esc_url(home_url('/'))); ?>">Logout</a>
            </div>

        </div>

    </div>

</div>
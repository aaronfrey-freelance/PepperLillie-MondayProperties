<?php while (have_posts()) : the_post(); ?>
	
	<?php get_template_part('templates/content', 'page'); ?>

<?php endwhile; ?>

<!-- <div class="investormain text-center">
	
	<a href="//localhost:3000/Freelance/PepperLillie-Monday/wp-content/uploads/2015/04/logo.jpg">
		<img src="//localhost:3000/Freelance/PepperLillie-Monday/wp-content/uploads/2015/04/logo.jpg" alt="Logo" width="256" height="180" class="aligncenter size-full wp-image-33" />
	</a>
	
	<p class="headline">Investor Log-In</p>

	<form>

		<p>Please log-in.</p>

		<div class="form-group">
    		<label for="username">Username:</label>
    		<input type="text" class="form-control" id="username">
  		</div>

		<div class="form-group">
    		<label for="password">Password:</label>
    		<input type="password" class="form-control" id="password">
  		</div>

  		<div class="checkbox">
    		<label>
      			<input name="remember" type="checkbox"> Remember me
    		</label>
  		</div>

  		<input class="btn btn-default" type="submit" value="Log-In" />

  		<a href="#">Forgot your password?</a>

	</form>

</div> -->
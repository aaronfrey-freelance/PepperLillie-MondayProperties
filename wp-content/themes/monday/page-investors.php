<?php
	global $wpdb;
	$cats = $wpdb->get_results('SELECT cat_id, cat_name FROM wp_wpfb_cats ORDER BY cat_name ASC', OBJECT);
	$years = $wpdb->get_results('SELECT DISTINCT YEAR(file_date) AS year FROM wp_wpfb_files ORDER BY year ASC', OBJECT);
?>

<?php while (have_posts()) : the_post(); ?>

	<div class="container">

		<div class="row">

			<div class="col-md-12">

				<h2><?php echo the_title(); ?></h2>

				<hr>

				<h3>Document Library</h3>

				<div class="dropdown-group">

					<span class="pull-left">View:</span>

					<div class="dropdown pull-left">
						<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
							All Documents
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li><a href="0">All Documents</a></li>
							<?php foreach($cats as $cat) : ?>
							<li><a href="<?php echo $cat->cat_id;?>"><?php echo $cat->cat_name;?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>

				</div>

				<div class="dropdown-group">

					<span class="pull-left">Year:</span>

					<div class="dropdown pull-left">
						<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
							All Years
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li><a href="0">All Years</a></li>
							<?php foreach($years as $year) : ?>
							<li><a href="<?php echo $year->year;?>"><?php echo $year->year;?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>

				</div>

				<div class="clearfix"></div>

				<?php
					get_template_part('templates/content', 'page');
				?>

				<br>

				<div class="file-list">
					<div class="col-sm-12">
						<div class="row title-row">
							<div class="col-xs-8">
								<strong>Title</strong>
							</div>
							<div class="col-xs-4">
								<strong>Date</strong>
							</div>
						</div>
						<div class="row file">
							<div class="panel-group" id="accordion" role="tablist">
								<div class="panel panel-default">
									<div class="panel-heading odd" role="tab">
										<h4 class="panel-title">
											<a role="button" data-toggle="collapse" data-parent="#accordion1" href="#collapseOne">
												<div class="col-xs-8">
													<i class="arrow pull-left"></i>
													<p class="title">Property / Project Name</p>
												</div>
												<div class="col-xs-4">
													<p>06/01/2015</p>
												</div>
											</a>
										</h4>
									</div>
									<div id="collapseOne" class="panel-collapse collapse">
										<div class="panel-body">
											<div class="col-xs-8">
												<p class="file-name">- Document_Name.pdf</p>
											</div>
											<div class="col-xs-4">
												<p>06/01/2015</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row file">
							<div class="panel-group" id="accordion2" role="tablist">
								<div class="panel panel-default">
									<div class="panel-heading" role="tab">
										<h4 class="panel-title">
											<a role="button" data-toggle="collapse" data-parent="#accordion2" href="#collapseTwo">
												<div class="col-xs-8">
													<i class="arrow pull-left"></i>
													<p class="title">Property / Project Name</p>
												</div>
												<div class="col-xs-4">
													<p>06/01/2015</p>
												</div>
											</a>
										</h4>
									</div>
									<div id="collapseTwo" class="panel-collapse collapse">
										<div class="panel-body">
											<div class="col-xs-8">
												<p class="file-name">- Document_Name.pdf</p>
											</div>
											<div class="col-xs-4">
												<p>06/01/2015</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<br>

				<div class="dropdown-group">

					<span class="pull-left">View:</span>

					<div class="dropdown pull-left">
						<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
							10 Results per page
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li><a href="#">10 Results per page</a></li>
							<li><a href="#">25 Results per page</a></li>
							<li><a href="#">50 Results per page</a></li>
							<li><a href="#">75 Results per page</a></li>
						</ul>
					</div>

				</div>

				<div class="clearfix"></div>

				<?php
	  				get_template_part('templates/footer');
	  				wp_footer();
				?>
				
			</div>

		</div>
 	
 	</div>

<?php endwhile; ?>

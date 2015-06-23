<?php
	global $wpdb;

	$cats = $wpdb->get_results(
		'SELECT cat_id, cat_name
		FROM wp_wpfb_cats
		JOIN wp_wpfb_files ON wp_wpfb_files.file_category = wp_wpfb_cats.cat_id
		ORDER BY cat_name ASC',
	OBJECT);

	$years = $wpdb->get_results(
		'SELECT DISTINCT YEAR(file_date) AS year
		FROM wp_wpfb_files
		ORDER BY year ASC',
	OBJECT);

	$files = $wpdb->get_results(
		'SELECT
			wp_wpfb_files.file_name,
			wp_wpfb_files.file_path,
			wp_wpfb_files.file_date,
			wp_wpfb_files.file_post_id,
			wp_posts.post_title,
			wp_posts.post_date
		FROM wp_wpfb_files
		LEFT JOIN wp_posts ON wp_posts.ID = wp_wpfb_files.file_post_id
		ORDER BY
			wp_posts.post_title ASC,
			wp_wpfb_files.file_name ASC', 
	OBJECT);

	$sorted_files = [];
	$sorted_post_files = [];

	foreach ($files as $file) {
		if (!$file->file_post_id) {
			$sorted_files[] = $file;
		} else {
			$sorted_post_files[$file->post_title][] = $file;
		}
	}

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
							<span class="handle"></span>
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
							<span class="handle"></span>
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
					//get_template_part('templates/content', 'page');
				?>

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

						<?php foreach($sorted_post_files as $post_title => $post) : ?>
							
							<div class="row file">
								<div class="panel-group" id="accordion_<?php echo $post[0]->file_post_id;?>" role="tablist">
									<div class="panel panel-default">
										<div class="panel-heading odd" role="tab">
											<h4 class="panel-title">
												<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion_<?php echo $post[0]->file_post_id;?>" href="#collapse_<?php echo $post[0]->file_post_id;?>">
													<div class="col-xs-8">
														<i class="arrow pull-left"></i>
														<p class="title"><?php echo $post_title; ?></p>
													</div>
													<div class="col-xs-4">
														<p><?php echo date('m/d/Y', strtotime($post[0]->post_date)); ?></p>
													</div>
												</a>
											</h4>
										</div>
										<div id="collapse_<?php echo $post[0]->file_post_id;?>" class="panel-collapse collapse">
											<div class="panel-body">
												<?php foreach($post as $file) : ?>
												<div class="col-xs-8">
													<p class="file-name">- <?php echo $file->file_name; ?></p>
												</div>
												<div class="col-xs-4">
													<p><?php echo date('m/d/Y', strtotime($file->file_date)); ?></p>
												</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>

						<?php endforeach; ?>

					</div>

				</div>

				<div class="dropdown-group">

					<span class="pull-left">View:</span>

					<div class="dropdown pull-left">
						<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
							10 Results per page
							<span class="handle"></span>
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

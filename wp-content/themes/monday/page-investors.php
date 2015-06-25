<?php
	$category 	= isset($_GET['category']) ? $_GET['category'] : 0;
	$year 		= isset($_GET['fileyear']) ? $_GET['fileyear'] : 0;
	$perpage 	= isset($_GET['perpage']) ? $_GET['perpage'] : 10;
	
	// Get the category name
	$file_cats = file_cats();
	$cat_name = 'All Documents';
	foreach ($file_cats as $cat) {
		if($cat->cat_id == $category) {
			$cat_name = $cat->cat_name;
			break;
		}
	}

	$resuls_array = [
		"10" => "10 Results per page",
		"25" => "25 Results per page",
		"50" => "50 Results per page",
		"75" => "75 Results per page"
	];

	// var_dump($category);
	// var_dump($year);
	// var_dump($perpage);
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
						<button
							class="btn btn-default dropdown-toggle"
							type="button"
							data-toggle="dropdown">
							<?php echo $cat_name; ?>
							<span class="handle"></span>
						</button>
						<input type="hidden" value="<?php echo $category; ?>" data-filter-option="category">
						<ul class="dropdown-menu">
							<li><a href="0">All Documents</a></li>
							<?php foreach($file_cats as $cat) : ?>
							<li><a href="<?php echo $cat->cat_id;?>"><?php echo $cat->cat_name;?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>

				</div>

				<div class="dropdown-group">

					<span class="pull-left">Year:</span>

					<div class="dropdown pull-left">
						<button
							class="btn btn-default dropdown-toggle"
							type="button"
							data-toggle="dropdown">
							<?php echo $year != 0 ? $year : 'All Years'; ?>
							<span class="handle"></span>
						</button>
						<input type="hidden" value="<?php echo $year; ?>" data-filter-option="year">
						<ul class="dropdown-menu">
							<li><a href="0">All Years</a></li>
							<?php foreach(file_years() as $y) : ?>
							<li><a href="<?php echo $y->year;?>"><?php echo $y->year;?></a></li>
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
						
						<?php $user_files = get_user_files($category, $year, $perpage); if(!empty($user_files)) : ?>

						<div class="row title-row">
							<div class="col-xs-8 col-sm-8">
								<strong>Title</strong>
							</div>
							<div class="hidden-xs col-sm-2">
								<strong>Size</strong>
							</div>
							<div class="col-xs-4 col-sm-2">
								<strong>Date</strong>
							</div>
						</div>

						<?php $index = 0; foreach($user_files as $post_title => $post) : ?>

							<div class="row file">
								<div class="panel-group" id="accordion_<?php echo $post['files'][0]->file_post_id;?>" role="tablist">
									<div class="panel panel-default">
										<div class="panel-heading <?php echo $index % 2 != 0 ? '' : 'odd'; ?>" role="tab">
											<h4 class="panel-title">
												<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion_<?php echo $post['files'][0]->file_post_id;?>" href="#collapse_<?php echo $post['files'][0]->file_post_id;?>">
													<div class="col-xs-8 col-sm-8">
														<i class="arrow pull-left"></i>
														<p class="title"><?php echo $post_title; ?></p>
													</div>
													<div class="hidden-xs col-sm-2">
														<strong><?php echo human_filesize($post['size']); ?></strong>
													</div>
													<div class="col-xs-4 col-sm-2">
														<p><?php echo date('m/d/Y', strtotime($post['modified'])); ?></p>
													</div>
												</a>
											</h4>
										</div>
										<div id="collapse_<?php echo $post['files'][0]->file_post_id;?>" class="panel-collapse collapse">
											<div class="panel-body <?php echo $index % 2 != 0 ? '' : 'odd'; ?>">
												<?php foreach($post['files'] as $file) : ?>
												<div class="col-xs-8 col-sm-8">
													<p class="file-name">- <?php echo $file->file_name; ?></p>
												</div>
												<div class="hidden-xs col-sm-2">

													<strong><?php echo human_filesize($file->file_size); ?></strong>
												</div>
												<div class="col-xs-4 col-sm-2">
													<p><?php echo date('m/d/Y', strtotime($file->file_date)); ?></p>
												</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>

						<?php $index++; endforeach; ?>

						<?php else : ?>
							<h3>No files match this criteria.</h3>
						<?php endif; ?>

					</div>

				</div>

				<div class="dropdown-group">

					<span class="pull-left">View:</span>

					<div class="dropdown pull-left">
						<button
							class="btn btn-default dropdown-toggle"
							type="button"
							data-toggle="dropdown">
							<?php echo $resuls_array[$perpage]; ?>
							<span class="handle"></span>
						</button>
						<input type="hidden" value="<?php echo $perpage; ?>" data-filter-option="perpage">
						<ul class="dropdown-menu">
							<?php foreach($resuls_array as $idx => $result) : ?>
							<li><a href="<?php echo $idx; ?>"><?php echo $result; ?></a></li>
							<?php endforeach; ?>
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

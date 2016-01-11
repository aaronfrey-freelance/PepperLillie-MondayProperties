<?php

	$category 	= isset($_GET['category']) ? $_GET['category'] : 0;
	$year 		= isset($_GET['fileyear']) ? $_GET['fileyear'] : 0;
	$perpage 	= isset($_GET['perpage']) ? $_GET['perpage'] : 10;
	$curr_page 	= isset($_GET['filepage']) ? $_GET['filepage'] : 1;
	$upload_dir = wp_upload_dir();
	
	$user_files_array = get_user_files($category, $year, $perpage, $curr_page);
	$user_files = $user_files_array['files'];
	$total_projects = $user_files_array['total'];
	
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
		"1"  => "1 Result per page",
		"10" => "10 Results per page",
		"25" => "25 Results per page",
		"50" => "50 Results per page",
		"75" => "75 Results per page"
	];

	// var_dump($category);
	// var_dump($year);
	// var_dump($perpage);

	if($curr_page != 0) {
		$arr_params = array('filepage' => $curr_page);
		$new_query = add_query_arg(['filepage' => $curr_page]);
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
						
						<?php if(!empty($user_files)) : ?>

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
												<a href="<?php echo site_url() . '/download/' . $file->file_path; ?>">
													<div class="col-xs-8 col-sm-8">
														<p class="file-name">- <?php echo $file->file_name; ?></p>
													</div>
													<div class="hidden-xs col-sm-2">
														<strong><?php echo human_filesize($file->file_size); ?></strong>
													</div>
													<div class="col-xs-4 col-sm-2">
														<p><?php echo date('m/d/Y', strtotime($file->file_date)); ?></p>
													</div>
												</a>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								</div>
							</div>

						<?php $index++; endforeach; ?>

						<!-- Call our function from above -->
						<?php $paging_info = get_paging_info($total_projects, $perpage, $curr_page); ?>

						<p class="file-pagination">
						    <!-- If the current page is more than 1, show the First and Previous links -->
						    <?php if($paging_info['curr_page'] > 1) : ?>
						        <a class="nextlast" href='<?php echo add_query_arg(["filepage" => 1]);?>' title='Page 1'>First<span>|</span></a>
						        <a href='<?php echo add_query_arg(["filepage" => $paging_info["curr_page"]-1]);?>' title='Page <?php echo ($paging_info['curr_page'] - 1); ?>'>Prev</a>
						    <?php endif; ?>

						    <?php
						        // $max is equal to number of links shown
						        $max = 6;
						        if($paging_info['curr_page'] < $max)
						            $sp = 1;
						        elseif($paging_info['curr_page'] >= ($paging_info['pages'] - floor($max / 2)) )
						            $sp = $paging_info['pages'] - $max + 1;
						        elseif($paging_info['curr_page'] >= $max)
						            $sp = $paging_info['curr_page']  - floor($max/2);
						    ?>

						    <!-- If the current page >= $max then show link to 1st page -->
						    <?php if($paging_info['curr_page'] >= $max) : ?>
						        <a href='<?php echo add_query_arg(["filepage" => 1]);?>' title='Page 1'>1</a>
						        ..
						    <?php endif; ?>

						    <!-- Loop though max number of pages shown and show links either side equal to $max / 2 -->
						    <?php for($i = $sp; $i <= ($sp + $max -1);$i++) : ?>

						        <?php
						            if($i > $paging_info['pages'])
						                continue;
						        ?>

						        <?php if($paging_info['curr_page'] == $i) : ?>

						        	<?php if($paging_info["pages"] != 1) : ?>
						            <span class='active'><?php echo $i; ?></span>
						        	<?php endif; ?>
						        <?php else : ?>

						            <a href='<?php echo add_query_arg(["filepage" => $i]);?>' title='Page <?php echo $i; ?>'><?php echo $i; ?></a>

						        <?php endif; ?>

						    <?php endfor; ?>

						    <!-- If the current page is less than say the last page minus $max pages divided by 2-->
						    <?php if($paging_info['curr_page'] < ($paging_info['pages'] - floor($max / 2))) : ?>
						        ..
						        <a href='<?php echo add_query_arg(["filepage" => $paging_info["pages"]]); ?>' title='Page <?php echo $paging_info['pages']; ?>'><?php echo $paging_info['pages']; ?></a>
						    <?php endif; ?>

						    <!-- Show last two pages if we're not near them -->
						    <?php if($paging_info['curr_page'] < $paging_info['pages']) : ?>

								<a 
									class="nextlast"
						        	href='<?php echo add_query_arg(["filepage" => $paging_info["curr_page"] + 1]); ?>'
						        	title='Page <?php echo ($paging_info['curr_page'] + 1); ?>'>Next<span>|</span></a>

						        <a 
						        	href='<?php echo add_query_arg(["filepage" => $paging_info["pages"]]); ?>'
						        	title='Page <?php echo $paging_info['pages']; ?>'>Last</a>

						    <?php endif; ?>
						</p>

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

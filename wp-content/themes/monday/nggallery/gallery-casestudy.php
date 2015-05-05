<?php 
/**
Template Page for the gallery overview

Follow variables are useable :

	$gallery     : Contain all about the gallery
	$images      : Contain all images, path, title
	$pagination  : Contain the pagination content

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/
?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

<div class="ngg-casestudy" id="<?php echo $gallery->anchor ?>">

	<!-- Pagination -->
 	<div class="image-pagination">
 		<?php $counter = 0; ?>
 		<?php foreach ( $images as $idx => $image ) : ?>
			<?php if ( !$image->hidden ) : $counter++; ?>
			<a href="#" class="text-center"><?php echo $counter; ?></a>
			<?php endif; ?>
 		<?php endforeach; ?>
 	</div>

	<!-- Thumbnails -->
	<?php foreach ( $images as $idx => $image ) : ?>

	<?php if ($idx === 0) : ?>

	<div class="row">
	
		<div class="col-sm-6 case-image">	

			<?php if ( !$image->hidden ) { ?>
			<img
				class="scale" data-scale="best-fill" data-align="center"
				title="<?php echo esc_attr($image->alttext) ?>"
				alt="<?php echo esc_attr($image->alttext) ?>"
				src="<?php echo nextgen_esc_url($image->imageURL) ?>" />
			<?php } ?>

		</div>

		<div class="col-sm-6 case-description-col">
			<div class="case-description">
				<span>
					<strong><?php echo esc_attr($image->alttext) ?></strong>
					<br><br>
					<p><?php echo esc_attr($image->description) ?></p>
				</span>
			</div>
		</div>

	</div>

	<?php endif; ?>

 	<?php endforeach; ?>
 	
</div>

<?php endif; ?>

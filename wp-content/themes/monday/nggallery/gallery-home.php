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
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<?php if (!empty ($gallery)) : ?>

	<div id="slides">
    
		<ul class="slides-container">  

		<?php foreach ( $images as $idx => $image ) : ?>

			<?php if ( !$image->hidden ) { ?>
			<li>
				<img
					title="<?php echo esc_attr($image->alttext) ?>"
					alt="<?php echo esc_attr($image->alttext) ?>"
					src="<?php echo nextgen_esc_url($image->imageURL) ?>" />
				<div class="headlinecontainer">
					<?php echo esc_attr($image->description) ?>
				</div>
			</li>
			<?php } ?>

	 	<?php endforeach; ?>

	 	</ul>

	</div>

<?php endif; ?>

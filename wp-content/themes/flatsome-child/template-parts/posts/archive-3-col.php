<?php
/**
 * Posts archive 3 column - Luxury Layout
 *
 * @package Flatsome
 */

if ( have_posts() ) : ?>
	<div id="post-list" class="luxury-post-grid">
		<?php
		$ids = array();
		while ( have_posts() ) : the_post();
			array_push( $ids, get_the_ID() );
		endwhile;
		$ids = implode( ',', $ids );

		echo flatsome_apply_shortcode( 'blog_posts', array(
			'type'           => 'row',
			'columns'        => '3',
			'columns__md'    => '2',
			'columns__sm'    => '1',
			'col_spacing'    => 'normal',
			'image_height'   => '62%',
			'image_size'     => 'medium',
			'image_hover'    => 'zoom',
			'show_date'      => 'false',
			'show_category'  => 'false',
			'excerpt'        => 'visible',
			'excerpt_length' => 18,
			'readmore'       => 'Xem chi tiết →',
			'readmore_style' => 'link',
			'text_align'     => 'left',
			'text_padding'   => '16px 16px 20px 16px',
			'text_bg'        => '#ffffff',
			'depth'          => '1',
			'depth_hover'    => '3',
			'ids'            => $ids,
		) );
		?>

		<?php flatsome_posts_pagination(); ?>
	</div>
<?php else : ?>

	<?php get_template_part( 'template-parts/posts/content', 'none' ); ?>

<?php endif; ?>

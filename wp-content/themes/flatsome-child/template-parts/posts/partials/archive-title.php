<?php
/**
 * Post archive title - Clean Luxury Style
 *
 * @package Flatsome
 */
?>
<header class="archive-page-header luxury-archive-header">
	<div class="row">
		<div class="large-12 text-center col">
			<h1 class="page-title is-large uppercase">
				<?php
				if ( is_category() ) :
					echo single_cat_title( '', false );
				elseif ( is_tag() ) :
					echo single_tag_title( '', false );
				elseif ( is_search() ) :
					printf( 'Kết quả tìm kiếm: %s', '<span>&ldquo;' . esc_html( get_search_query() ) . '&rdquo;</span>' );
				elseif ( is_author() ) :
					echo esc_html( get_the_author() );
				elseif ( is_tax() ) :
					echo single_term_title( '', false );
				else :
					the_archive_title();
				endif;
				?>
			</h1>
			<div class="is-divider small" style="background-color: #d4af37; margin: 15px auto 20px auto; height: 3px; max-width: 60px;"></div>
			<?php
			if ( is_category() && category_description() ) :
				echo '<div class="taxonomy-description">' . category_description() . '</div>';
			elseif ( is_tag() && tag_description() ) :
				echo '<div class="taxonomy-description">' . tag_description() . '</div>';
			endif;
			?>
		</div>
	</div>
</header>

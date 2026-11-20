<?php
/**
 * Template Chi tiết Dự án BĐS (Hỗ trợ 100% UX Builder Tràn viền Landing Page)
 * Loại bỏ Sidebar Blog - Cho phép các Section trải dài 100% chiều rộng màn hình chuẩn Luxury Landing Page
 */

get_header();
?>
<div id="content" role="main" class="content-area project-landing-page" style="width:100%;max-width:100%;padding:0;margin:0;">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="project-<?php the_ID(); ?>" <?php post_class( 'project-landing-article' ); ?>>
			<div class="entry-content project-entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();

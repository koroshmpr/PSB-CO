<?php
/**
 * 404 — lightweight (no inline illustration), helpful next steps + search.
 *
 * @package bluebox
 */

get_header();

$psb_links = array(
	array( psb_shop_url(), 'محصولات', 'bolt' ),
	array( psb_archive_url( 'services', '/service/' ), 'خدمات و امکانات', 'cog' ),
	array( psb_archive_url( 'portfolio', '/portfolios/' ), 'پروژه‌ها', 'factory' ),
	array( psb_archive_url( 'confirmation' ), 'تأییدیه‌ها', 'shield' ),
	array( psb_contact_url(), 'تماس با شرکت', 'phone' ),
);
?>
<section class="border-b border-line bg-background py-section">
	<div class="container max-w-3xl text-center">
		<?php get_template_part( 'template-parts/global/breadcrumb', null, array( 'class' => 'mb-8 flex justify-center', 'schema' => false ) ); ?>
		<p class="font-mono text-7xl font-bold text-slate-300 sm:text-8xl" aria-hidden="true">404</p>
		<h1 class="mt-4 text-h2 font-bold text-dark">صفحه‌ای که دنبال آن بودید پیدا نشد</h1>
		<p class="section-lead mx-auto mt-3 max-w-xl">ممکن است نشانی تغییر کرده یا صفحه حذف شده باشد. از جستجو یا پیوندهای زیر استفاده کنید.</p>

		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="mx-auto mt-8 flex max-w-lg gap-2">
			<label for="psb-404-search" class="sr-only">جستجو</label>
			<input id="psb-404-search" class="input" type="search" name="s" placeholder="جستجو در سایت…">
			<button class="btn btn-primary" type="submit"><?php echo psb_icon( 'search' ); // phpcs:ignore ?>جستجو</button>
		</form>

		<ul class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
			<?php foreach ( $psb_links as $psb_link ) : ?>
				<li>
					<a class="card card-hover flex-row items-center gap-3 p-4 text-start font-bold text-dark" href="<?php echo esc_url( $psb_link[0] ); ?>">
						<span class="icon-tile"><?php echo psb_icon( $psb_link[2] ); // phpcs:ignore ?></span>
						<?php echo esc_html( $psb_link[1] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
			<li>
				<a class="card card-hover flex-row items-center gap-3 p-4 text-start font-bold text-dark" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="icon-tile"><?php echo psb_icon( 'arrow' ); // phpcs:ignore ?></span>
					صفحه اصلی
				</a>
			</li>
		</ul>
	</div>
</section>
<?php
get_footer();

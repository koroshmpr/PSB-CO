<?php
/**
 * Credentials hub sub-navigation (تأییدیه‌ها · تایپ‌تست · گواهینامه‌ها · رضایت‌نامه‌ها).
 *
 * $args: current (post type key)
 */
$psb_current  = $args['current'] ?? '';
$psb_sections = array_filter(
	psb_cred_sections(),
	function ( $s, $type ) use ( $psb_current ) {
		return $s['count'] > 0 || $type === $psb_current; // Hide empty sections (spec 6.3).
	},
	ARRAY_FILTER_USE_BOTH
);
if ( count( $psb_sections ) < 2 ) {
	return;
}
?>
<nav class="subnav" aria-label="اعتبارنامه‌ها">
	<ul class="subnav-list" role="list">
		<?php foreach ( $psb_sections as $psb_type => $psb_s ) : ?>
			<li>
				<a class="subnav-link" href="<?php echo esc_url( $psb_s['url'] ); ?>" <?php echo $psb_type === $psb_current ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $psb_s['short'] ); ?>
					<?php if ( $psb_s['count'] ) : ?>
						<span class="subnav-count"><?php echo esc_html( psb_fa_num( $psb_s['count'] ) ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

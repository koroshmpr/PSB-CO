<?php
/**
 * Stats band (ruled row, spec §4.5): projects · clients · sectors · provinces · span.
 * Numbers come from psb_pr_stats() (cached). No «+», no count-up.
 *
 * $args: stats (optional), class
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_pr_stats' ) ) {
	return;
}
$psb_s = $args['stats'] ?? psb_pr_stats();
if ( empty( $psb_s['projects'] ) ) {
	return;
}
$psb_items = array(
	array( psb_pr_fa_int( $psb_s['projects'] ), 'پروژه ثبت‌شده در سوابق' ),
	array( psb_pr_fa_int( $psb_s['clients'] ), 'کارفرمای مختلف' ),
	array( psb_pr_fa_int( $psb_s['sectors'] ), 'حوزه صنعتی و خدماتی' ),
	array( psb_pr_fa_int( $psb_s['provinces'] ), $psb_s['abroad'] ? 'استان، و پروژه‌هایی خارج از کشور' : 'استان کشور' ),
);
if ( $psb_s['span_from'] ) {
	$psb_items[] = $psb_s['span_to']
		? array( psb_pr_fa( $psb_s['span_from'] ) . '<small>تا ' . esc_html( psb_pr_fa( $psb_s['span_to'] ) ) . '</small>', 'بازه سوابق اجرایی' )
		: array( psb_pr_fa( $psb_s['span_from'] ), 'آغاز سوابق، سال تأسیس شرکت' );
}
?>
<section class="pr-stats-band <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-label="سوابق در یک نگاه">
	<div class="container">
		<dl class="stats pr-stats">
			<?php foreach ( $psb_items as $psb_i ) : ?>
				<div class="stat">
					<dt class="stat-label order-last"><?php echo esc_html( $psb_i[1] ); ?></dt>
					<dd class="stat-value"><?php echo wp_kses( $psb_i[0], array( 'small' => array() ) ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>

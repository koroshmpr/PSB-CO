<?php
/**
 * Factory & lab (spec v2 §5.5): the one dark band. Three equal cards (line illustration on a light mat,
 * eyebrow, title, text, link) — a sideways rail on mobile — then a ruled 4-fact row. Facts: company-facts.md.
 * Images: service featured images (CNC, electrical test lab); assembly = attachment 1726 if it is
 * an image, else a product photo. Filter: psb_home_factory_tiles.
 */
$psb_services = get_posts( array( 'post_type' => 'services', 'posts_per_page' => 12, 'no_found_rows' => true ) );
if ( ! $psb_services ) {
	return;
}
$psb_find = function ( $pattern ) use ( $psb_services ) {
	foreach ( $psb_services as $s ) {
		if ( preg_match( $pattern, $s->post_title ) ) {
			return $s;
		}
	}
	return null;
};
$psb_cnc  = $psb_find( '/CNC|ماشین/u' );
$psb_lab  = $psb_find( '/تست برق|آزمایشگاه تست برق/u' );
$psb_int_post = null;
foreach ( get_posts( array( 'post_type' => 'product', 'posts_per_page' => 6, 'no_found_rows' => true ) ) as $psb_pr ) {
	if ( false !== mb_strpos( $psb_pr->post_title, 'فرمان' ) ) {
		$psb_int_post = $psb_pr;
	}
}
// Assembly photo: the 16:9 crop uploaded on live (1921), else the original portrait (1726).
$psb_int_att = 0;
foreach ( array( 1921, 1726 ) as $psb_cand ) {
	if ( wp_attachment_is_image( $psb_cand ) && ( 1921 !== $psb_cand || 'psb.co.ir' === wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
		$psb_int_att = $psb_cand;
		break;
	}
}

// «بازدید از خطوط تولید» and the assembly tile go to the services page (/service/), not the CPT archive.
$psb_services_page = get_page_by_path( 'service' );
$psb_services_url  = $psb_services_page ? get_permalink( $psb_services_page ) : psb_archive_url( 'services', '/service/' );

/*
 * Tiles: [ source post (service/product, drives the illustration key), preferred attachment (0 = auto),
 *          url, eyebrow, title, description ]. Large tile prefers the 16:9 crop in post meta `psb_wide_image`.
 */
$psb_fp   = (int) get_option( 'page_on_front' );
$psb_tf   = function ( $tile, $part ) use ( $psb_fp ) {
	return 'image' === $part ? psb_pf_image_id( psb_pf( "home_tile_{$tile}_image", $psb_fp ) ) : psb_pf_text( psb_pf( "home_tile_{$tile}_{$part}", $psb_fp ) );
};
$psb_tiles = array_values(
	array_filter(
		apply_filters(
			'psb_home_factory_tiles',
			array(
				( $psb_cnc || $psb_tf( 'cnc', 'image' ) ) ? array( $psb_cnc, $psb_tf( 'cnc', 'image' ), $psb_cnc ? get_permalink( $psb_cnc ) : $psb_services_url, $psb_tf( 'cnc', 'eyebrow' ), $psb_tf( 'cnc', 'title' ), $psb_tf( 'cnc', 'text' ) ) : null,
				( $psb_int_post || $psb_tf( 'assembly', 'image' ) ) ? array( $psb_int_post, $psb_tf( 'assembly', 'image' ), $psb_services_url, $psb_tf( 'assembly', 'eyebrow' ), $psb_tf( 'assembly', 'title' ), $psb_tf( 'assembly', 'text' ) ) : null,
				( $psb_lab || $psb_tf( 'lab', 'image' ) ) ? array( $psb_lab, $psb_tf( 'lab', 'image' ), $psb_lab ? get_permalink( $psb_lab ) : $psb_services_url, $psb_tf( 'lab', 'eyebrow' ), $psb_tf( 'lab', 'title' ), $psb_tf( 'lab', 'text' ) ) : null,
			)
		)
	)
);
if ( ! $psb_tiles ) {
	return;
}

/**
 * One factory card (owner decision 2026-11-27: line illustrations, not photos): illustration on a light
 * mat, then eyebrow / title / text and the link. A photo chosen in the page field still wins.
 */
$psb_tile = function ( $t ) {
	list( $post, $att, $url, $eyebrow, $title, $text ) = $t;
	$attr = array(
		'class' => 'size-full object-contain',
		'alt'   => '',
		'sizes' => '(min-width: 1024px) 380px, 80vw',
	);
	if ( $att ) {
		$img = psb_image( $att, 'medium_large', $attr + array( 'class' => 'size-full object-cover' ) );
	} else {
		$img = function_exists( 'psb_placeholder_img' ) ? psb_placeholder_img( $post && function_exists( 'psb_placeholder_key' ) ? psb_placeholder_key( $post ) : 'default', array( 'class' => 'size-full object-contain', 'alt' => $title ) ) : '';
	}
	?>
	<li class="fac-slide">
		<a class="fac-card group" href="<?php echo esc_url( $url ); ?>" data-reveal>
			<span class="fac-media <?php echo $att ? 'is-photo' : ''; ?>"><?php echo $img; // phpcs:ignore ?></span>
			<span class="fac-body">
				<?php if ( $eyebrow ) : ?><small class="fac-eyebrow"><?php echo esc_html( $eyebrow ); ?></small><?php endif; ?>
				<span class="fac-title"><?php echo psb_pf_html( $title ); // phpcs:ignore ?></span>
				<?php if ( $text ) : ?><span class="fac-text"><?php echo psb_pf_html( $text ); // phpcs:ignore ?></span><?php endif; ?>
				<span class="fac-more">جزئیات <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
			</span>
		</a>
	</li>
	<?php
};
$psb_facts = array();
foreach ( psb_pf_rows( 'home_factory_facts', $psb_fp ) as $psb_row ) {
	if ( ! empty( $psb_row['value'] ) ) {
		$psb_facts[] = array( psb_pf_html( $psb_row['value'] ), $psb_row['label'] ?? '', ! empty( $psb_row['icon'] ) ? $psb_row['icon'] : 'check' );
	}
}
?>
<section class="on-dark bg-dark py-section text-white" aria-labelledby="home-factory-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_factory',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-factory-title',
					'invert'  => true,
					'link'    => array(
						'url'   => $psb_services_url
					),
				)
			)
		);
		?>
		<ul class="fac-rail" role="list">
			<?php foreach ( $psb_tiles as $psb_t ) : ?>
				<?php $psb_tile( $psb_t ); ?>
			<?php endforeach; ?>
		</ul>

		<?php if ( $psb_facts ) : ?>
		<dl class="stats stats--invert mt-8 lg:mt-12">
			<?php foreach ( $psb_facts as $psb_f ) : ?>
				<div class="stat">
					<dt class="order-1 flex items-center gap-3 text-2xl font-bold leading-[1.3] text-white"><?php echo psb_icon( $psb_f[2] ?? 'check', 'size-6 shrink-0 text-slate-400' ); // phpcs:ignore ?><span><?php echo wp_kses( $psb_f[0], array( 'span' => array( 'class' => true ) ) ); ?></span></dt>
					<dd class="stat-label order-2 !text-[0.8125rem]"><?php echo psb_pf_html( $psb_f[1] ); // phpcs:ignore ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php endif; ?>
	</div>
</section>

<?php
/**
 * Factory & lab (spec v2 §5.5): the one dark band. 7/5 photo mosaic (CNC big; assembly + test lab
 * small), each tile linking to its service, then a ruled 4-fact row. Facts: company-facts.md.
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
				( $psb_int_post || $psb_int_att || $psb_tf( 'assembly', 'image' ) ) ? array( $psb_int_post, $psb_tf( 'assembly', 'image' ) ? $psb_tf( 'assembly', 'image' ) : $psb_int_att, $psb_services_url, $psb_tf( 'assembly', 'eyebrow' ), $psb_tf( 'assembly', 'title' ), $psb_tf( 'assembly', 'text' ) ) : null,
				( $psb_lab || $psb_tf( 'lab', 'image' ) ) ? array( $psb_lab, $psb_tf( 'lab', 'image' ), $psb_lab ? get_permalink( $psb_lab ) : $psb_services_url, $psb_tf( 'lab', 'eyebrow' ), $psb_tf( 'lab', 'title' ), $psb_tf( 'lab', 'text' ) ) : null,
			)
		)
	)
);
if ( ! $psb_tiles ) {
	return;
}

/** Image HTML for a tile + whether it fell back to an illustration. */
$psb_tile_img = function ( $t, $big ) {
	list( $post, $att ) = $t;
	if ( ! $att && $post ) {
		$wide = (int) get_post_meta( $post->ID, 'psb_wide_image', true ); // All tiles are 16:9 (the large one is taller but cover-cropped).
		$att  = ( $wide && wp_attachment_is_image( $wide ) ) ? $wide : (int) get_post_thumbnail_id( $post );
	}
	$attr = array(
		'class' => 'size-full object-cover object-center [filter:saturate(.85)] transition-transform duration-[400ms] group-hover:scale-[1.03]',
		'alt'   => '',
		'sizes' => $big ? '(min-width: 1024px) 700px, 100vw' : '(min-width: 1024px) 500px, 100vw',
	);
	$args = array( 'size' => $big ? 'large' : 'medium_large', 'attr' => $attr );
	if ( $post && function_exists( 'psb_placeholder_key' ) ) {
		$args['key'] = psb_placeholder_key( $post );
	}
	if ( function_exists( 'psb_best_image' ) ) {
		$html = psb_best_image( $att ? $att : $post, $big ? 700 : 500, '16 / 9', $args );
	} else {
		$html = $att ? psb_image( $att, $args['size'], $attr ) : '';
	}
	return array( $html, false !== strpos( (string) $html, 'is-placeholder' ) || '' === $html );
};

$psb_tile = function ( $t, $big ) use ( $psb_tile_img ) {
	list( $img, $is_ph ) = $psb_tile_img( $t, $big );
	$size = $big ? 'h-full min-h-[340px]' : 'aspect-[16/9]';
	if ( $is_ph ) :
		// Light variant: illustration on steel-50 above, graphite text on a white strip (never white on light art).
		?>
		<a class="group flex flex-col overflow-hidden rounded-card bg-white text-foreground <?php echo esc_attr( $size ); ?>" href="<?php echo esc_url( $t[2] ); ?>" data-reveal>
			<span class="relative block min-h-0 flex-1 bg-slate-50 <?php echo $big ? 'min-h-[220px]' : 'min-h-[110px]'; ?> [&_img]:absolute [&_img]:inset-0 [&_img]:size-full [&_img]:object-contain [&_img]:p-4 [&_img]:[filter:none]">
				<?php echo $img; // phpcs:ignore ?>
			</span>
			<span class="block border-t border-line p-5 lg:px-6">
				<small class="text-xs font-semibold text-slate-600"><?php echo esc_html( $t[3] ); ?></small>
				<span class="mt-1 block text-lg font-bold leading-normal text-foreground"><?php echo esc_html( $t[4] ); ?></span>
				<?php if ( $t[5] && $big ) : ?>
					<span class="mt-1 block text-sm leading-[1.8] text-slate-600"><?php echo esc_html( $t[5] ); ?></span>
				<?php endif; ?>
			</span>
		</a>
		<?php
		return;
	endif;
	?>
	<a class="group relative isolate block overflow-hidden rounded-card bg-dark-800 <?php echo esc_attr( $size ); ?>" href="<?php echo esc_url( $t[2] ); ?>" data-reveal>
		<span class="absolute inset-0 -z-20 block"><?php echo $img; // phpcs:ignore ?></span>
		<span class="absolute inset-0 -z-10 bg-[linear-gradient(to_top,rgb(17_20_24/.92)_0%,rgb(17_20_24/.55)_38%,rgb(17_20_24/0)_65%)]" aria-hidden="true"></span>
		<span class="absolute inset-x-0 bottom-0 block p-6 lg:px-7">
			<small class="text-xs font-semibold text-slate-300"><?php echo esc_html( $t[3] ); ?></small>
			<span class="mb-1.5 mt-1 block text-xl font-bold leading-normal text-white"><?php echo esc_html( $t[4] ); ?></span>
			<?php if ( $t[5] ) : ?>
				<span class="block max-w-[30rem] text-sm leading-[1.8] text-slate-200"><?php echo esc_html( $t[5] ); ?></span>
			<?php endif; ?>
		</span>
	</a>
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
		<div class="grid gap-6 lg:grid-cols-12">
			<div class="lg:col-span-7 [&>a]:h-full"><?php $psb_tile( $psb_tiles[0], true ); ?></div>
			<?php if ( count( $psb_tiles ) > 1 ) : ?>
				<div class="grid gap-6 lg:col-span-5">
					<?php foreach ( array_slice( $psb_tiles, 1, 2 ) as $psb_t ) : ?>
						<?php $psb_tile( $psb_t, false ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

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

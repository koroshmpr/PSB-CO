<?php
/**
 * Production flow (owner, 2026-11-27): the six steps from mechanical & electrical design to the electrical
 * test lab, edited in the front page repeater «روند تولید» (home_flow: title, text, points, match).
 * «match» (words separated by |) finds the service for the link + line illustration.
 * Used on the home page (dark band) and on the services page (light).
 *
 * psb_production_steps()            → array of steps (title, text, points, post)
 * psb_production_flow( $variant )   → prints the <ol class="flow"> ('dark' | 'light')
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

function psb_production_steps() {
	static $steps = null;
	if ( null !== $steps ) {
		return $steps;
	}
	$steps    = array();
	$services = get_posts( array( 'post_type' => 'services', 'post_status' => 'publish', 'posts_per_page' => 20, 'no_found_rows' => true ) );
	$find     = function ( $pattern ) use ( $services ) {
		foreach ( $services as $s ) {
			if ( preg_match( $pattern, $s->post_title . ' ' . urldecode( $s->post_name ) ) ) {
				return $s;
			}
		}
		return null;
	};
	$front = (int) get_option( 'page_on_front' );
	if ( ! function_exists( 'psb_pf_rows' ) ) {
		return $steps;
	}
	foreach ( psb_pf_rows( 'home_flow', $front ) as $row ) {
		if ( empty( $row['title'] ) ) {
			continue;
		}
		$words   = array_filter( array_map( 'trim', explode( '|', (string) ( $row['match'] ?? '' ) ) ) );
		$steps[] = array(
			'title'  => psb_pf_text( $row['title'] ),
			'text'   => psb_pf_text( $row['text'] ?? '' ),
			'points' => array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $row['points'] ?? '' ) ) ) ),
			'post'   => $words ? $find( '/' . implode( '|', array_map( function ( $w ) { return preg_quote( $w, '/' ); }, $words ) ) . '/ui' ) : null,
		);
	}
	$steps = apply_filters( 'psb_home_factory_steps', $steps );
	return $steps;
}

/** Print the flow. $variant: 'dark' (home band) or 'light' (white section). */
function psb_production_flow( $variant = 'dark' ) {
	$steps = psb_production_steps();
	if ( ! $steps ) {
		return;
	}
	$page = get_page_by_path( 'service' );
	$psb_services_url = $page ? get_permalink( $page ) : psb_archive_url( 'services', '/service/' );
$psb_step = function ( $i, $st ) use ( $psb_services_url ) {
	$post = $st['post'];
	$url  = $post ? get_permalink( $post ) : $psb_services_url;
	$key  = ( $post && function_exists( 'psb_placeholder_key' ) ) ? psb_placeholder_key( $post ) : 'factory';
	$img  = function_exists( 'psb_placeholder_img' ) ? psb_placeholder_img( $key, array( 'class' => 'size-full object-contain', 'alt' => $st['title'] ) ) : '';
	?>
	<li class="flow-step">
		<span class="flow-num" aria-hidden="true"><?php echo esc_html( psb_fa_num( sprintf( '%02d', $i + 1 ) ) ); ?></span>
		<a class="flow-card group" href="<?php echo esc_url( $url ); ?>">
			<span class="flow-media"><?php echo $img; // phpcs:ignore ?></span>
			<span class="flow-body">
				<span class="flow-title"><span class="sr-only"><?php echo esc_html( 'مرحله ' . psb_fa_num( $i + 1 ) . ': ' ); ?></span><?php echo psb_pf_html( $st['title'] ); // phpcs:ignore ?></span>
				<?php if ( $st['text'] ) : ?><span class="flow-text"><?php echo psb_pf_html( $st['text'] ); // phpcs:ignore ?></span><?php endif; ?>
				<?php if ( $st['points'] ) : ?>
					<span class="flow-points">
						<?php foreach ( array_slice( $st['points'], 0, 3 ) as $psb_pt ) : ?>
							<span class="flow-point"><?php echo psb_icon( 'check', 'size-3.5 shrink-0' ); // phpcs:ignore ?><span><?php echo psb_pf_html( $psb_pt ); // phpcs:ignore ?></span></span>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
				<span class="flow-more">جزئیات<?php if ( $post ) : ?><span class="sr-only"> <?php echo esc_html( get_the_title( $post ) ); ?></span><?php endif; ?> <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
			</span>
		</a>
	</li>
	<?php
};
	?>
	<ol class="flow <?php echo 'light' === $variant ? 'flow--light' : ''; ?>" role="list">
		<?php foreach ( $steps as $i => $st ) : ?>
			<?php $psb_step( $i, $st ); ?>
		<?php endforeach; ?>
	</ol>
	<?php
}

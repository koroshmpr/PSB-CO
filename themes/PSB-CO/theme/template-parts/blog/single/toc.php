<?php
/**
 * Table of contents for articles.
 * - variant 'side'   : sticky desktop panel (≥1280px), viewport-capped with its own scrollbar, reading-progress
 *                      hairline, h2 items with h3 children that expand only for the active section.
 * - variant 'inline' : collapsible «فهرست مطالب» block at the top of the article (<1280px).
 * Scrollspy: IntersectionObserver + scroll fallback; the last heading above (header + 20% of the viewport) wins.
 * Labels go through psb_lat_html() so codes keep Latin digits (IEC 60364-7-710).
 *
 * $args: items (psb_toc_items()), variant ('side'|'inline')
 */
$psb_items = (array) ( $args['items'] ?? array() );
if ( count( $psb_items ) < 3 ) {
	return;
}
$psb_side = 'side' === ( $args['variant'] ?? 'side' );

// Nest h3 under the preceding h2.
$psb_tree = array();
foreach ( $psb_items as $psb_it ) {
	if ( 3 === $psb_it['level'] && $psb_tree ) {
		$psb_tree[ count( $psb_tree ) - 1 ]['children'][] = $psb_it;
	} else {
		$psb_tree[] = $psb_it + array( 'children' => array() );
	}
}
$psb_list = function () use ( $psb_tree ) {
	echo '<ol class="toc-list" role="list">';
	foreach ( $psb_tree as $psb_node ) {
		echo '<li class="toc-item' . ( $psb_node['children'] ? ' has-children' : '' ) . '">';
		echo '<a href="#' . esc_attr( $psb_node['id'] ) . '" data-toc-link><span class="toc-label">' . psb_lat_html( $psb_node['text'] ) . '</span></a>'; // phpcs:ignore -- escaped in helper
		if ( $psb_node['children'] ) {
			echo '<ol class="toc-children" role="list">';
			foreach ( $psb_node['children'] as $psb_child ) {
				echo '<li class="toc-item toc-sub"><a href="#' . esc_attr( $psb_child['id'] ) . '" data-toc-link><span class="toc-label">' . psb_lat_html( $psb_child['text'] ) . '</span></a></li>'; // phpcs:ignore
			}
			echo '</ol>';
		}
		echo '</li>';
	}
	echo '</ol>';
};

if ( $psb_side ) :
	?>
	<nav class="toc toc--side hidden xl:flex" aria-labelledby="toc-title" data-toc>
		<div class="toc-progress" aria-hidden="true"><span data-toc-progress></span></div>
		<p id="toc-title" class="toc-title">فهرست مطالب</p>
		<div class="toc-scroll" data-toc-scroll><?php $psb_list(); ?></div>
	</nav>
<?php else : ?>
	<details class="toc toc--inline mb-8 xl:hidden" data-toc>
		<summary class="toc-summary"><span>فهرست مطالب</span><?php echo psb_icon( 'chevron', 'size-4 -rotate-90 transition-transform' ); // phpcs:ignore ?></summary>
		<?php $psb_list(); ?>
	</details>
	<?php
endif;

// Scrollspy script (once per page).
if ( empty( $GLOBALS['psb_toc_js'] ) ) :
	$GLOBALS['psb_toc_js'] = true;
	add_action(
		'wp_footer',
		function () {
			?>
			<script>
			(function(){
			var tocs=[].slice.call(document.querySelectorAll('[data-toc]'));if(!tocs.length)return;
			var ids=[],seen={};tocs[0].querySelectorAll('[data-toc-link]').forEach(function(a){var id=decodeURIComponent(a.getAttribute('href').slice(1));if(!seen[id]){seen[id]=1;ids.push(id);}});
			var heads=ids.map(function(id){return document.getElementById(id);}).filter(Boolean);if(!heads.length)return;
			var art=document.querySelector('[data-article]'),bar=document.querySelector('[data-toc-progress]'),box=document.querySelector('[data-toc-scroll]'),cur=null,ticking=false;
			function headerH(){var h=document.querySelector('.site-header');return h?Math.max(0,h.getBoundingClientRect().bottom):0;}
			function setActive(id){if(id===cur)return;cur=id;
				tocs.forEach(function(t){t.querySelectorAll('[data-toc-link]').forEach(function(a){var on=decodeURIComponent(a.getAttribute('href').slice(1))===id;if(on)a.setAttribute('aria-current','true');else a.removeAttribute('aria-current');});
					t.querySelectorAll('.toc-item.has-children').forEach(function(li){li.classList.toggle('is-open',!!li.querySelector('[aria-current="true"]'));});});
				if(box){var a=box.querySelector('[aria-current="true"]');if(a){var br=box.getBoundingClientRect(),ar=a.getBoundingClientRect();if(ar.top<br.top+8)box.scrollTop+=ar.top-br.top-8;else if(ar.bottom>br.bottom-8)box.scrollTop+=ar.bottom-br.bottom+8;}}}
			function update(){ticking=false;var line=headerH()+window.innerHeight*0.2,act=heads[0].id;
				for(var i=0;i<heads.length;i++){if(heads[i].getBoundingClientRect().top<=line)act=heads[i].id;else break;}
				if(heads[0].getBoundingClientRect().top>line)act=null;setActive(act);
				if(bar&&art){var r=art.getBoundingClientRect(),h=r.height-window.innerHeight,p=h>0?Math.min(1,Math.max(0,-r.top/h)):1;bar.style.width=(p*100)+'%';}}
			function req(){if(!ticking){ticking=true;requestAnimationFrame(update);}}
			if('IntersectionObserver' in window){var io=new IntersectionObserver(req,{rootMargin:'-'+Math.round(headerH())+'px 0px -60% 0px',threshold:[0,1]});heads.forEach(function(h){io.observe(h);});}
			window.addEventListener('scroll',req,{passive:true});window.addEventListener('resize',req);update();
			tocs.forEach(function(t){t.addEventListener('click',function(e){var a=e.target.closest('[data-toc-link]');if(!a)return;var id=decodeURIComponent(a.getAttribute('href').slice(1)),el=document.getElementById(id);if(!el)return;e.preventDefault();
				var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;el.scrollIntoView({behavior:reduce?'auto':'smooth',block:'start'});
				if(history.replaceState)history.replaceState(null,'','#'+encodeURIComponent(id));if(t.tagName==='DETAILS')t.open=false;setActive(id);});});
			})();
			</script>
			<?php
		},
		35
	);
endif;

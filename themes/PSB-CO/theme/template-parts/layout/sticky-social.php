<nav x-cloak
	 :class="atBottom ? 'opacity-0' : ''"
	 class="fixed max-lg:hidden inset-y-0 end-7 py-20 z-[2] flex flex-col gap-y-5 justify-end items-center transition-opacity duration-300">

	<div
		class="flex flex-col bg-white/95 backdrop-blur-sm border border-black/5 shadow-md overflow-hidden rounded-md items-center justify-center lg:justify-end">
		<?php
		$transition = 'transition-all';
		$class = 'text-black w-full aspect-square px-1.5 py-2 hover:bg-black group hover:text-white ' . $transition;
		$svgClass = $transition;
		$socials = get_field('social', 'option');
		if ($socials):
			foreach ($socials as $social):?>
				<a title="<?= esc_attr($social['name']); ?>" aria-label="<?= esc_attr($social['name']); ?>"
				   class="<?= $class; ?>" target="_blank"
				   href="<?= esc_url($social['link']['url'] ?? ''); ?>">
					<?php
					$args = array('size' => 14, 'class' => $svgClass);
					get_template_part('template-parts/svg/socials/' . esc_attr($social['name']), null, $args); ?>
				</a>
			<?php endforeach;
		endif;
		$phones = get_field('phones', 'option') ?: get_field('phone', 'option');
		$phone = '';
		if (is_array($phones)) {
			foreach ($phones as $p) {
				$num_obj = $p['number'] ?? null;
				$url = is_array($num_obj) ? ($num_obj['url'] ?? '') : (is_string($num_obj) ? $num_obj : '');
				if ($url) {
					$phone = str_replace('tel:', '', $url);
					break;
				}
			}
		} else {
			$phone = is_string($phones) ? $phones : '';
		}
		?>
		<a title="call to <?= esc_attr($phone); ?>"
		   aria-label="<?= esc_attr($phone); ?>"
		   class="<?= $class; ?>" target="_blank"
		   href="tel:<?= esc_url($phone); ?>">
			<?php
			$args = array('size' => 14, 'class' => $svgClass);
			get_template_part('template-parts/svg/call-fill', null, $args); ?>
		</a>
	</div>
</nav>

<?php
global $product;

if (!$product) {
    return;
}
$catMode = get_field('catalogue_mode', 'option');
$isArchive = $args['isArchive'] ?? false;
$product_id = $product->get_id();
$product_slug = $product->get_slug();
$product_permalink = esc_url(get_permalink($product_id));

// Base Pricing
$price = $product->get_price();
$regular_price = $product->get_regular_price();
$sale_price = $product->get_sale_price();

// Variables for initial display
$reg_price_val = !empty($regular_price) ? (float)$regular_price : 0;
$sale_price_val = !empty($sale_price) ? (float)$sale_price : 0;
$price_val = !empty($price) ? (float)$price : 0;

$has_price = ($price_val > 0);

// Variations Logic for Alpine.js
$available_variations = [];
if ($product->is_type('variable')) {
    $price = $product->get_variation_price('min', true);
    $regular_price = $product->get_variation_regular_price('min', true);
    $sale_price = $product->get_variation_sale_price('min', true);

    $price_val = !empty($price) ? (float)$price : 0;
    $has_price = ($price_val > 0);

    // Fetch variations to pass to Alpine
    $variations = $product->get_available_variations();
    foreach ($variations as $var) {
        $var_price = (float)($var['display_price'] ?? 0);
        $available_variations[] = [
            'id' => $var['variation_id'],
            'price' => number_format($var_price),
            'has_price' => $var_price > 0,
            'regular_price' => number_format((float)$var['display_regular_price']),
            'is_on_sale' => $var['display_price'] < $var['display_regular_price'],
            'percentage' => $var['display_regular_price'] > 0 ? round(((($var['display_regular_price'] - $var['display_price']) / $var['display_regular_price']) * 100)) : 0,
            'image' => $var['image']['url'] ?? '',
            'attributes' => $var['attributes'] // e.g., attribute_pa_color
        ];
    }
}

$is_on_sale = $product->is_on_sale();
$stock_status = $product->get_stock_status();
$percentage = ($reg_price_val > 0) ? round((($reg_price_val - $sale_price_val) / $reg_price_val) * 100) : 0;

$args_svg = array(
    'size' => '20',
    'class' => 'group-hover/add:delay-100 text-white duration-300 rotate-45 group-hover/add:rotate-0 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
);

// Optimized image sizes
$image_id = $product->get_image_id();
$image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') ?: wc_placeholder_img_src();
$gallery_ids = $product->get_gallery_image_ids();
$hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : '';

// Dynamic loading attributes
$is_eager = $args['eager'] ?? false;
$loading_attr = $is_eager ? 'eager' : 'lazy';
$fetch_priority = $is_eager ? 'fetchpriority="high"' : '';
?>

<div
    x-data="{
        hover: false,
        imgLoaded: false,
        variations: <?= htmlspecialchars(json_encode($available_variations), ENT_QUOTES, 'UTF-8'); ?>,
        selectedVar: null,
        mainImageUrl: '<?= esc_url($image_url); ?>',
        currentPrice: '<?= number_format($sale_price_val > 0 ? $sale_price_val : $price_val); ?>',
        currentRegularPrice: '<?= number_format($reg_price_val); ?>',
        hasPrice: <?= $has_price ? 'true' : 'false'; ?>,
        isOnSale: <?= $is_on_sale ? 'true' : 'false'; ?>,
        currentDiscount: '<?= $percentage; ?>',
        productUrl: '<?= $product_permalink; ?>',
        addToCartUrl: '<?= $product->is_type('variable') ? $product_permalink : esc_url(add_query_arg('add-to-cart', $product_id)); ?>',

        selectColor(termSlug) {
            if (this.variations.length === 0) return;

            let match = this.variations.find(v => v.attributes['attribute_pa_color'] === termSlug);
            if (match) {
                this.selectedVar = match.id;
                this.currentPrice = match.price;
                this.hasPrice = match.has_price;
                this.currentRegularPrice = match.regular_price;
                this.isOnSale = match.is_on_sale;
                this.currentDiscount = match.percentage;
                this.addToCartUrl = '?add-to-cart=' + match.id;
                if(match.image) {
                    this.mainImageUrl = match.image;
                }
            }
        }
    }"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
    class="group relative bg-white border rounded-2xl border-slate-200/90 !my-0 transition-all duration-300 hover:shadow-xl hover:border-blue-500/50 rtl <?= esc_attr($args['class'] ?? ''); ?>"
    dir="rtl"
>
    <?php
    $args_compare = [
        'id' => get_the_ID(),
        'class' => 'mr-auto rounded-full lg:opacity-0 group-hover:opacity-100 lg:translate-y-2 group-hover:translate-y-0 !absolute z-[3] block top-2 end-2 p-2 lg:p-2.5 backdrop-blur-sm !border-gray-300 text-primary hover:bg-gray-800 hover:text-white transition-all',
        'textClass' => 'hidden'
    ];
    get_template_part('template-parts/product/compare-button', null, $args_compare);
    ?>

    <!-- Card Anchor pointing to Single Product Page -->
    <a href="<?= $product_permalink; ?>" aria-label="View <?= esc_attr($product_slug); ?>"
       class="relative overflow-hidden flex <?= $isArchive ? 'max-lg:flex-row' : ''; ?> flex-col rounded-2xl bg-white">

        <?php if ($stock_status === 'outofstock') : ?>
            <span class="absolute top-3 start-3 z-[2] bg-red-600/90 text-white text-xs font-bold px-2.5 py-1 text-center rounded-lg shadow-sm">
                ناموجود
            </span>
        <?php endif; ?>

        <!-- Image Container -->
        <div class="block bg-slate-50/50 relative <?= $isArchive ? 'max-lg:w-1/3' : ''; ?> size-full aspect-square overflow-hidden p-4 rounded-t-2xl">
            <div x-show="!imgLoaded" x-transition.opacity.duration.500ms
                 class="absolute inset-0 z-[1] bg-slate-100 animate-pulse flex items-center justify-center"></div>

            <img width="300" height="300"
                 x-ref="mainImg"
                 x-init="if ($refs.mainImg && $refs.mainImg.complete) imgLoaded = true"
                 @load="imgLoaded = true"
                 loading="<?= esc_attr($loading_attr); ?>"
                <?= $fetch_priority; ?>
                 decoding="<?= $is_eager ? 'sync' : 'async'; ?>"
                 src="<?= esc_url($image_url); ?>"
                 :src="mainImageUrl"
                 alt="<?= esc_attr(get_the_title()); ?>"
                 class="object-contain size-full !my-0 transition-all duration-500 transform group-hover:scale-105"
                 :class="hover ? '<?= $hover_image_url ? 'opacity-0' : ''; ?> scale-105' : 'opacity-100'"
            >

            <?php if ($hover_image_url) : ?>
                <img width="300" height="300"
                     loading="<?= esc_attr($loading_attr); ?>"
                     decoding="<?= $is_eager ? 'sync' : 'async'; ?>"
                     alt="<?= esc_attr(get_the_title() . '-hover'); ?>"
                     src="<?= esc_url($hover_image_url); ?>"
                     class="absolute inset-0 size-full aspect-square object-contain transition-all duration-500 opacity-0"
                     :class="hover ? 'opacity-100 scale-105' : 'opacity-0'">
            <?php endif; ?>

            <!-- Hover Cart Button if product has price -->
            <?php if (!$catMode && $stock_status === 'instock' && $has_price && !wp_is_mobile()): ?>
                <button aria-label="add to cart"
                        @click.stop.prevent="window.location.href = addToCartUrl"
                        class="absolute bottom-3 group/add overflow-hidden inset-x-4 duration-300 shadow-md translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 z-20 flex items-center justify-center gap-2 py-2.5 bg-blue-700 text-white cursor-pointer rounded-xl font-bold hover:bg-blue-800 transition-all text-xs"
                >
                    <?php get_template_part('template-parts/svg/shop', null, $args_svg); ?>
                    <span class="text-xs transition-all duration-300">افزودن به سبد</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Product Card Details -->
        <div class="flex flex-col flex-1 relative p-4 gap-3">
            <div class="space-y-1">
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-relaxed text-right">
                    <?php the_title(); ?>
                </h3>

                <!-- Color Variations if available -->
                <?php
                $colors = wc_get_product_terms($product_id, 'pa_color', array('fields' => 'all'));
                if (!empty($colors) && !is_wp_error($colors)) : ?>
                    <div class="flex items-center gap-1 pt-1" @click.stop.prevent>
                        <?php foreach ($colors as $color) :
                            $acf_color = get_field('color', $color->taxonomy . '_' . $color->term_id);
                            if ($acf_color) : ?>
                                <button
                                        @click="selectColor('<?= esc_js($color->slug); ?>')"
                                        class="rounded-sm size-5 cursor-pointer border border-gray-300 shadow-sm transition-all focus:outline-none hover:scale-110"
                                        :class="selectedVar && variations.find(v => v.id === selectedVar)?.attributes['attribute_pa_color'] === '<?= esc_js($color->slug); ?>' ? 'ring-2 ring-offset-1 ring-gray-400' : ''"
                                        title="<?php echo esc_attr($color->name); ?>"
                                        style="background-color: <?php echo esc_attr($acf_color); ?>;"
                                        aria-label="Select <?= esc_attr($color->name); ?>">
                                </button>
                            <?php endif;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price & Inquiry Button Container -->
            <div class="mt-auto pt-2 border-t border-slate-100 flex items-center justify-between min-h-[44px]">
                <template x-if="hasPrice">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base sm:text-lg font-bold text-slate-900 font-mono tracking-tight" x-text="currentPrice"></span>
                            <span class="text-[10px] text-slate-500 font-bold">تومان</span>
                        </div>
                        <template x-if="isOnSale && currentDiscount > 0">
                            <span class="bg-red-50 text-red-600 text-[11px] font-bold px-2 py-0.5 rounded-md border border-red-200" x-text="currentDiscount + '%'"></span>
                        </template>
                    </div>
                </template>

                <!-- Inquiry & Consultation CTA Button if Product Has No Price -->
                <template x-if="!hasPrice">
                    <button type="button"
                            @click.stop.prevent="shopContact = true"
                            class="w-full py-2.5 px-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>استعلام قیمت & مشاوره</span>
                    </button>
                </template>
            </div>
        </div>
    </a>
</div>
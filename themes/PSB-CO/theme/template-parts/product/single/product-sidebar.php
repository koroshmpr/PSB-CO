<?php
global $product;

if (!$product) return;
$isSale = $product->is_on_sale();
$catMode = get_field('catalogue_mode', 'option');
$stock_status = $product->get_stock_status();
$is_purchasable = $product->is_purchasable();

$price = (float) $product->get_price();
if ($product->is_type('variable')) {
    $price = (float) $product->get_variation_price('min', true);
}
$has_price = ($price > 0);
?>

<aside :class="scrollingDown ? 'shadow scale-95' :''"
       class="lg:col-span-3 origin-top duration-500 sticky bg-white border border-slate-200/90 shadow-sm rounded-2xl max-xl:hidden <?= current_user_can('administrator') ? 'top-28' : 'top-20'; ?> p-4 flex flex-col justify-center transition-all">

    <?php if ($has_price) : ?>
        <!-- Header: Image and Price for priced products -->
        <div class="flex gap-3 items-center mb-3">
            <img :class="scrolled ? 'w-16' : '!w-0'"
                 class="object-cover w-0 !h-16 rounded-xl duration-500 aspect-square transition-all border border-slate-200"
                 src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>"/>
            <?php get_template_part('template-parts/product/single/product', 'price'); ?>
        </div>
    <?php endif; ?>

    <?php if ($catMode || !$has_price || $stock_status !== 'instock' || !$is_purchasable) : ?>
        <!-- Single CTA Button for unpriced / custom B2B equipment -->
        <button type="button"
                @click="shopContact = true"
                class="w-full py-3.5 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-sm shadow-md shadow-blue-700/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <span>استعلام قیمت & مشاوره فنی</span>
        </button>
    <?php else : ?>
        <?php if (!wp_is_mobile()) :
            get_template_part('template-parts/product/single/product', 'buyform');
        endif;
    endif;
    get_template_part('template-parts/shop/property', null, ['class' => 'max-lg:hidden mt-3']);
    ?>
</aside>
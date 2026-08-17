<?php
global $product;
if (!$product) return;

$catMode = get_field('catalogue_mode', 'option');
$isSale = $product->is_on_sale();
$regular_price = (float)$product->get_regular_price();
$sale_price = (float)$product->get_sale_price();
$price = (float)$product->get_price();

if ($product->is_type('variable')) {
    $price = (float)$product->get_variation_price('min', true);
}
$has_price = ($price > 0);
$stock_status = $product->get_stock_status();
$is_purchasable = $product->is_purchasable();

$percentage = 0;
if ($isSale && $regular_price > 0 && $has_price) {
    $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
}
?>
<nav class="fixed bottom-12 inset-x-0 w-full rounded-none border-t border-slate-200 border-x-0 border-b-0 bg-white p-3 z-[55] shadow-[0_-4px_25px_rgba(0,0,0,0.12)] xl:hidden flex justify-between items-center transition-all duration-300">

    <?php if ($catMode || !$has_price || $stock_status !== 'instock' || !$is_purchasable) : ?>
        <!-- Full-Width Single Inquiry CTA Button for Unpriced / Out-of-stock Products -->
        <button @click="shopContact = true" type="button" aria-label="open contact modal"
                class="w-full py-3 px-4 bg-blue-700 hover:bg-blue-800 flex items-center justify-center gap-2 transition-all text-center rounded-xl text-white font-bold text-sm shadow-md shadow-blue-700/20 active:scale-98">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <span>استعلام قیمت & مشاوره فنی</span>
        </button>

    <?php else : ?>
        <!-- Price Display + Add to Cart Button for Priced Items -->
        <div class="flex flex-col ps-2 flex-1 relative justify-center">
            <?php if ($percentage > 0) : ?>
                <div class="absolute top-0 -translate-y-1/2 end-3 z-0 flex items-center justify-center">
                    <div class="bg-red-500 text-white px-2 py-0.5 rounded-full shadow-sm text-[10px] font-bold">
                        <span><?= $percentage; ?>%</span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="flex items-center gap-1.5">
                <span class="text-lg font-bold text-slate-900 font-mono tracking-tight"><?= number_format($price); ?></span>
                <span class="text-[10px] text-slate-500 font-bold">تومان</span>
            </div>
        </div>

        <button @click="openForm = true" type="button" aria-label="open add to cart form"
                class="bg-blue-700 hover:bg-blue-800 px-5 py-3 flex gap-x-2 justify-center transition-all text-center rounded-xl text-white font-bold text-xs shadow-md">
            <?= $product->is_purchasable() ? 'افزودن به سبد خرید' : $product->add_to_cart_text(); ?>
        </button>
    <?php endif; ?>
</nav>

<?php if (!$catMode && $has_price && $stock_status === 'instock' && $is_purchasable): ?>
    <div
            @keydown.escape.window="openForm = false" id="openForm"
            :class="openForm ? '!z-50 !opacity-100' : ''"
            class="fixed inset-0 flex justify-center z-[-1] bg-black/20 opacity-0 items-end backdrop-blur-[1px] transition-all duration-300"
            @click.self="openForm = false">
        <div :class="openForm ? 'delay-200 !translate-y-0' : 'translate-y-full'"
             class="relative w-full p-5 pt-10 transition-all bg-white rounded-t-2xl">
            <button @click="openForm = false" aria-label="close menu"
                    class=" text-black mb-2 absolute -top-4 start-3 bg-gray-100 border border-gray-200 p-2 rounded-xl">
                <?php
                $args = array(
                    'size' => '20',
                    'class' => '',
                );
                get_template_part('template-parts/svg/close', null, $args);
                ?>
            </button>
            <?php
            if (wp_is_mobile()):
                get_template_part('template-parts/product/single/product', 'buyform');
            endif;
            ?>
        </div>
    </div>
<?php endif; ?>

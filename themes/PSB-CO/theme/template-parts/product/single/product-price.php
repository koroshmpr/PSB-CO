<?php
global $product;
if (!$product) return;

if ($product->is_type('variable')) {
    $price = (float) $product->get_variation_price('min', true);
    $regular_price = (float) $product->get_variation_regular_price('min', true);
    $is_on_sale = $price < $regular_price;
} else {
    $price = (float) $product->get_price();
    $regular_price = (float) $product->get_regular_price();
    $is_on_sale = $product->is_on_sale();
}

$has_price = ($price > 0);
$percentage = ($is_on_sale && $regular_price > 0) ? round((($regular_price - $price) / $regular_price) * 100) : 0;

if (!$has_price) {
    return; // Price component does not render button directly to prevent duplicate buttons in parent containers
}
?>

<div class="flex flex-col w-full rtl" dir="rtl">
    <div class="flex justify-start items-start min-h-[50px] w-full gap-2">
        <div class="flex flex-col items-start gap-0.5">
            <!-- Main Price Row -->
            <div class="flex items-center justify-start gap-1.5 w-full">
                <span class="text-lg lg:text-2xl font-bold text-slate-900 font-mono tracking-tight">
                    <?= number_format($price); ?>
                </span>

                <div class="flex flex-col items-center justify-center text-[11px] font-black leading-[0.85] text-slate-600">
                    <span>ن</span>
                    <span class="-mb-[2px]">توما</span>
                </div>
            </div>

            <!-- Old Price Row (Strikethrough) -->
            <div class="h-5 w-full flex items-center gap-1 justify-start">
                <?php if ($is_on_sale && $regular_price > 0) : ?>
                    <del class="text-[13px] text-slate-500 font-medium decoration-red-600">
                        <?= number_format($regular_price); ?>
                    </del>
                <?php endif; ?>
                <div class="w-12 shrink-0 text-right">
                    <?php if ($is_on_sale && $percentage > 0) : ?>
                        <div class="bg-red-600 text-white text-[12px] font-bold px-2 py-0.5 rounded-lg inline-flex items-center justify-center">
                            <?= $percentage; ?>٪
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
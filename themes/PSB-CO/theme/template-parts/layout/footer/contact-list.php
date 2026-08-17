<?php
$fillColor = 'fill-white/80';
$contactClass = 'ps-2 lg:ps-4';
$iconTransitionClass = 'flex before:duration-200 justify-center items-center group-hover:before:scale-100 before:transition-all before:scale-0 before:size-[33px] before:rounded-full relative before:absolute before:top-1/2 before:translate-x-1/2  before:-translate-y-1/2 before:right-1/2 before:bg-white/20';
$columnClass = 'max-lg:my-5 flex lg:border-s border-white/10  flex-col max-lg:items-center gap-y-4 text-white/70';
$titleCLass = 'lg:bg-gradient-to-l select-none from-white/5 py-0.5 text-xl lg:text-2xl lg:ps-5 w-full lg:border-s-2 max-lg:text-white/85 border-white/75 text-white-75'
?>
<nav class=" <?= $columnClass; ?>">
    <p class="<?= $titleCLass; ?> max-lg:text-center">
        راه‌های ارتباطی
    </p>
    <?php $address = get_field('address', 'option');
    if ($address) :?>
        <address class="flex gap-3 not-italic items-center <?= $contactClass; ?>">
            <?php
            $args = array(
                'size' => 20,
                'class' => $fillColor
            );
            get_template_part('template-parts/svg/map', null, $args);
            ?>
            <?= get_field('address', 'option'); ?>
        </address>
    <?php endif; ?>
    <?php
    $emails = get_field('emails', 'option') ?: get_field('email', 'option');
    $email = '';
    if (is_array($emails)) {
        foreach ($emails as $e) {
            $e_val = is_array($e) ? ($e['email'] ?? '') : $e;
            if ($e_val) { $email = $e_val; break; }
        }
    } else {
        $email = is_string($emails) ? $emails : '';
    }
    if ($email) :?>
        <a aria-label="email link"
           class="flex gap-3 hover:text-warning group transition-colors justify-start <?= $contactClass; ?>"
           href="mailto:<?= esc_attr($email); ?>">
            <span class="<?= $iconTransitionClass; ?>">
                <?php get_template_part('template-parts/svg/envelope', null, $args); ?>
            </span>
            <span class="group-hover:!text-icon">
                <?= esc_html($email); ?>
            </span>
        </a>
    <?php endif;
    $phones = get_field('phones', 'option') ?: get_field('phone', 'option');
    $phone = '';
    if (is_array($phones)) {
        foreach ($phones as $p) {
            $num_obj = is_array($p) ? ($p['number'] ?? null) : null;
            $url = is_array($num_obj) ? ($num_obj['url'] ?? '') : (is_string($num_obj) ? $num_obj : '');
            if ($url) { $phone = str_replace('tel:', '', $url); break; }
        }
    } else {
        $phone = is_string($phones) ? $phones : '';
    }
    if ($phone) :
        ?>
        <a aria-label="call us"
           class="flex gap-3 group transition-colors justify-start <?= $contactClass; ?>"
           href="tel:<?= esc_attr($phone); ?>">
            <span class="<?= $iconTransitionClass; ?>">
                <?php get_template_part('template-parts/svg/call-fill', null, $args); ?>
            </span>
            <span class="ltr group-hover:!text-icon"><?= esc_html($phone); ?></span>
        </a>
    <?php endif;
    ?>
</nav>
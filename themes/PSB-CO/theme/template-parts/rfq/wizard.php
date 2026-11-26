<?php
/**
 * RFQ multi-step wizard (markup; behaviour in js/rfq.js).
 * Usage: get_template_part( 'template-parts/rfq/wizard' ) or [psb_rfq].
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_rfq_enqueue' ) ) {
	return;
}
psb_rfq_enqueue();
require_once __DIR__ . '/helpers.php';

$psb_uid   = 'rfq' . wp_rand( 1000, 9999 );
$psb_steps = psb_rfq_steps();
$psb_files = psb_rfq_opt( 'max_files', 5 );
$psb_ext   = psb_rfq_allowed_ext();
$psb_sales = psb_rfq_opt( 'sales_phone' );
$psb_types = psb_rfq_types_by_group();
$psb_grp   = psb_rfq_groups();
$psb_links = psb_rfq_type_links();
$psb_i     = 0;
?>
<div class="rfq" id="<?php echo esc_attr( $psb_uid ); ?>" data-rfq="wizard">
	<noscript>
		<p class="rfq-alert">برای ثبت آنلاین درخواست، جاوااسکریپت مرورگر را فعال کنید<?php echo $psb_sales ? ' یا با شماره <a href="' . esc_url( psb_rfq_tel( $psb_sales ) ) . '" dir="ltr">' . esc_html( psb_rfq_fa( $psb_sales ) ) . '</a> تماس بگیرید' : ''; ?>.</p>
	</noscript>

	<ol class="rfq-progress" aria-label="مراحل فرم">
		<?php foreach ( $psb_steps as $psb_key => $psb_label ) : ?>
			<li data-p="<?php echo esc_attr( $psb_key ); ?>"><span class="rfq-progress-n" aria-hidden="true"><?php echo esc_html( psb_rfq_fa( ++$psb_i ) ); ?></span><span class="rfq-progress-l"><?php echo esc_html( $psb_label ); ?></span><span class="rfq-sr" data-p-state></span></li>
		<?php endforeach; ?>
	</ol>
	<div class="rfq-mstep">
		<p class="rfq-mstep-t" data-mstep aria-hidden="true">مرحله <?php echo esc_html( psb_rfq_fa( 1 ) ); ?> از <?php echo esc_html( psb_rfq_fa( count( $psb_steps ) ) ); ?></p>
		<div class="rfq-bar" role="progressbar" aria-label="پیشرفت تکمیل فرم" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>
	</div>

	<p class="rfq-restored" data-restored hidden><span>اطلاعاتی که قبلاً وارد کرده بودید بازیابی شد.</span> <button type="button" class="rfq-link" data-act="reset">شروع از نو</button></p>

	<form class="rfq-form" novalidate>
		<div class="rfq-hp" aria-hidden="true"><label>وب‌سایت <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

		<section class="rfq-step" data-step="types" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-types">
			<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-types" tabindex="-1">نوع تابلوی مورد نیاز</h2>
			<p class="rfq-step-lead">یک یا چند نوع تابلو را انتخاب و تعداد هرکدام را مشخص کنید. اگر مطمئن نیستید، گزینه «سایر / مطمئن نیستم» را انتخاب کنید؛ کارشناسان فروش راهنمایی خواهند کرد.</p>
			<div class="rfq-groups">
			<?php foreach ( $psb_types as $psb_g => $psb_list ) : ?>
				<div class="rfq-grp rfq-grp--s<?php echo (int) min( 3, count( $psb_list ) ); ?>">
				<h3 class="rfq-group"><span><?php echo psb_rfq_bidi( isset( $psb_grp[ $psb_g ] ) ? $psb_grp[ $psb_g ] : $psb_g ); // phpcs:ignore -- escaped by psb_rfq_bidi(). ?></span></h3>
				<div class="rfq-cards">
					<?php
					foreach ( $psb_list as $psb_k => $psb_t ) :
						$psb_more = isset( $psb_links[ $psb_k ] ) ? $psb_links[ $psb_k ] : '';
						?>
						<div class="rfq-tile" data-tile="<?php echo esc_attr( $psb_k ); ?>">
							<button type="button" class="rfq-card" data-type="<?php echo esc_attr( $psb_k ); ?>" aria-pressed="false">
								<span class="rfq-card-check" aria-hidden="true"></span>
								<span class="rfq-card-body">
									<span class="rfq-card-t"><?php echo psb_rfq_bidi( $psb_t['label'] ); // phpcs:ignore -- escaped by psb_rfq_bidi(). ?></span>
									<?php if ( $psb_t['desc'] ) : ?>
										<span class="rfq-card-d"><?php echo psb_rfq_bidi( $psb_t['desc'] ); // phpcs:ignore -- escaped by psb_rfq_bidi(). ?></span>
									<?php endif; ?>
								</span>
							</button>
							<?php if ( $psb_more ) : ?>
								<a class="rfq-more" href="<?php echo esc_url( $psb_more ); ?>">اطلاعات بیشتر<span class="rfq-sr"> درباره <?php echo esc_html( $psb_t['label'] ); ?></span><svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></a>
							<?php endif; ?>
							<span class="rfq-tqty" data-tqty hidden>
								<span class="rfq-tqty-l" aria-hidden="true">تعداد</span>
								<span class="rfq-qty">
									<button type="button" data-tq="-1" aria-label="کاهش تعداد <?php echo esc_attr( $psb_t['label'] ); ?>">−</button>
									<output data-tqv aria-live="polite">۱</output>
									<button type="button" data-tq="1" aria-label="افزایش تعداد <?php echo esc_attr( $psb_t['label'] ); ?>">+</button>
								</span>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
				</div>
			<?php endforeach; ?>
			</div>
			<div class="rfq-items" data-items aria-live="polite"></div>
			<p class="rfq-err" data-err="items" aria-live="polite"></p>
		</section>

		<?php if ( isset( $psb_steps['specs'] ) ) : ?>
			<section class="rfq-step" data-step="specs" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-specs" hidden>
				<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-specs" tabindex="-1">مشخصات فنی</h2>
				<p class="rfq-step-lead">اطلاعات موجود را وارد کنید؛ هر جا مطمئن نیستید «<?php echo esc_html( psb_rfq_unknown_label() ); ?>» را انتخاب کنید یا خالی بگذارید. در صورت وجود نقشه، آن را در مرحله بعد بارگذاری کنید.</p>
				<div data-specs></div>
			</section>
		<?php endif; ?>

		<?php if ( isset( $psb_steps['files'] ) ) : ?>
			<section class="rfq-step" data-step="files" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-files" hidden>
				<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-files" tabindex="-1">مدارک فنی</h2>
				<p class="rfq-step-lead">نقشه تک‌خطی (<bdi dir="ltr">Single Line</bdi>)، جدول مقادیر (<bdi dir="ltr">BOQ</bdi>)، مشخصات فنی مشاور یا تصویر تابلوی موجود، دقیق‌ترین مبنای قیمت‌گذاری است.<?php echo 'required' === psb_rfq_vis( 'files' ) ? '' : ' این مرحله اختیاری است.'; ?></p>
				<div class="rfq-drop" data-drop>
					<input type="file" class="rfq-file-input" id="<?php echo esc_attr( $psb_uid ); ?>-file" multiple accept="<?php echo esc_attr( '.' . implode( ',.', $psb_ext ) ); ?>">
					<label for="<?php echo esc_attr( $psb_uid ); ?>-file" class="rfq-drop-label">
						<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
						<span><strong>انتخاب فایل</strong> یا کشیدن و رها کردن فایل در این کادر</span>
					</label>
					<p class="rfq-drop-hint">فرمت‌ها: <bdi dir="ltr" class="rfq-lat"><?php echo esc_html( strtoupper( implode( ', ', $psb_ext ) ) ); ?></bdi> — <?php echo esc_html( 'حداکثر ' . psb_rfq_fa( $psb_files ) . ' فایل، هر فایل تا ' . psb_rfq_size( psb_rfq_max_bytes() ) ); ?></p>
				</div>
				<ul class="rfq-files" data-files aria-live="polite"></ul>
				<p class="rfq-err" data-err="files" aria-live="polite"></p>
			</section>
		<?php endif; ?>

		<?php if ( isset( $psb_steps['project'] ) ) : ?>
			<section class="rfq-step" data-step="project" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-project" hidden>
				<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-project" tabindex="-1">اطلاعات پروژه</h2>
				<p class="rfq-step-lead">این اطلاعات برای برآورد دقیق استاندارد، الزامات کارفرما و زمان‌بندی تولید به کار می‌رود.</p>
				<div class="rfq-grid">
					<?php
					foreach ( psb_rfq_field_defs() as $psb_k => $psb_d ) {
						if ( 'project' === $psb_d['step'] ) {
							psb_rfq_render_field( $psb_k, $psb_uid );
						}
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<section class="rfq-step" data-step="contact" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-contact" hidden>
			<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-contact" tabindex="-1">اطلاعات تماس</h2>
			<p class="rfq-step-lead">پیش‌فاکتور و پرسش‌های فنی از همین طریق ارسال می‌شود.</p>
			<div class="rfq-grid">
				<?php
				foreach ( psb_rfq_field_defs() as $psb_k => $psb_d ) {
					if ( 'contact' === $psb_d['step'] ) {
						psb_rfq_render_field( $psb_k, $psb_uid );
					}
				}
				?>
				<div class="rfq-field rfq-field--challenge" data-f="challenge" data-challenge hidden>
					<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-answer">سؤال امنیتی: حاصل <bdi class="rfq-q" data-q></bdi> چند می‌شود؟ <span class="rfq-req" aria-hidden="true">*</span></label>
					<input class="rfq-input rfq-ltr" type="text" inputmode="numeric" dir="ltr" id="<?php echo esc_attr( $psb_uid ); ?>-answer" name="answer" maxlength="3" autocomplete="off" aria-required="true" aria-describedby="<?php echo esc_attr( $psb_uid ); ?>-answer-err">
					<p class="rfq-err" id="<?php echo esc_attr( $psb_uid ); ?>-answer-err" aria-live="polite"></p>
				</div>
			</div>
		</section>

		<section class="rfq-step" data-step="review" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>-h-review" hidden>
			<h2 class="rfq-step-title" id="<?php echo esc_attr( $psb_uid ); ?>-h-review" tabindex="-1">بازبینی و ارسال</h2>
			<p class="rfq-step-lead">خلاصه درخواست را بررسی کنید. برای اصلاح هر بخش، گزینه «ویرایش» را انتخاب کنید.</p>
			<div data-review></div>
			<p class="rfq-privacy"><?php echo esc_html( psb_rfq_opt( 'privacy_note' ) ); ?></p>
		</section>

		<p class="rfq-err rfq-err--submit" data-err="submit" role="alert"></p>

		<div class="rfq-nav">
			<button type="button" class="btn btn-secondary rfq-prev" data-act="prev" hidden>مرحله قبل</button>
			<span class="rfq-nav-count" data-count aria-hidden="true"></span>
			<button type="button" class="btn btn-primary rfq-next" data-act="next">مرحله بعد<svg class="rfq-arrow" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6-6-6 6 6 6"/></svg></button>
			<button type="submit" class="btn btn-primary rfq-submit" data-act="submit" hidden>ثبت درخواست</button>
		</div>
	</form>

	<div class="rfq-success" data-success hidden tabindex="-1">
		<span class="rfq-success-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
		<h2 class="rfq-success-title" data-s-title></h2>
		<p class="rfq-success-msg" data-s-msg></p>
		<p class="rfq-code"><span>کد پیگیری</span> <strong class="rfq-lat" data-s-code dir="ltr"></strong> <button type="button" class="rfq-link" data-act="copy">کپی</button></p>
		<p class="rfq-success-time" data-s-time></p>
		<div class="rfq-success-actions">
			<a class="btn btn-primary" data-s-tel hidden href="#">تماس با واحد فروش</a>
			<a class="btn btn-secondary" data-s-wa hidden href="#" target="_blank" rel="noopener">پیام در واتساپ</a>
			<button type="button" class="btn btn-ghost" data-act="new">ثبت درخواست جدید</button>
		</div>
	</div>
</div>

<?php
/**
 * RFQ — settings pages: «تنظیمات» (form, lists, fields, limits, texts) and «اتصال بات‌ها» (channels + tests).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		$parent = 'edit.php?post_type=' . PSB_RFQ_CPT;
		add_submenu_page( $parent, 'تنظیمات استعلام قیمت', 'تنظیمات', 'manage_options', 'psb-rfq-settings', 'psb_rfq_settings_page' );
		add_submenu_page( $parent, 'اتصال بات‌ها و اعلان‌ها', 'اتصال بات‌ها', 'manage_options', 'psb-rfq-channels', 'psb_rfq_channels_page' );
	}
);

/** One-shot notice for the current user. */
function psb_rfq_flash( $msg = null, $ok = true ) {
	$key = 'psb_rfq_flash_' . get_current_user_id();
	if ( null === $msg ) {
		$v = get_transient( $key );
		delete_transient( $key );
		return $v;
	}
	$list   = get_transient( $key );
	$list   = is_array( $list ) ? $list : array();
	$list[] = array( $ok, $msg );
	set_transient( $key, $list, 300 );
}

function psb_rfq_print_flash() {
	$list = psb_rfq_flash();
	foreach ( is_array( $list ) ? $list : array() as $n ) {
		printf( '<div class="notice notice-%s is-dismissible"><p style="white-space:pre-wrap">%s</p></div>', $n[0] ? 'success' : 'error', esc_html( $n[1] ) );
	}
}

/* -------------------------------------------------------------------------
 * Page: تنظیمات
 * ---------------------------------------------------------------------- */

function psb_rfq_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = psb_rfq_settings( true );
	$vis    = array( 'show' => 'نمایش (اختیاری)', 'required' => 'الزامی', 'hide' => 'پنهان' );
	$srvmax = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
	?>
	<div class="wrap">
		<h1>تنظیمات استعلام قیمت و ثبت سفارش</h1>
		<?php psb_rfq_print_flash(); ?>
		<p>فرم در صفحه‌ای با قالب «استعلام قیمت» (آدرس <code>/request-quote/</code>) و با شورت‌کدهای <code>[psb_rfq]</code> و <code>[psb_rfq_quick product="…"]</code> نمایش داده می‌شود.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="psb_rfq_save">
			<input type="hidden" name="tab" value="settings">
			<?php wp_nonce_field( 'psb_rfq_save' ); ?>

			<h2 class="title">صفحه موفقیت و اطلاعات تماس</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="rfq-prefix">پیشوند کد پیگیری</label></th><td><input id="rfq-prefix" name="s[prefix]" class="small-text ltr" value="<?php echo esc_attr( $s['prefix'] ); ?>"> <span class="description">نمونه: <?php echo esc_html( strtoupper( $s['prefix'] ) . '-' . psb_rfq_jyear() . '-0012' ); ?></span></td></tr>
				<tr><th><label for="rfq-phone">تلفن فروش</label></th><td><input id="rfq-phone" name="s[sales_phone]" class="regular-text ltr" value="<?php echo esc_attr( $s['sales_phone'] ); ?>"><p class="description">برای دکمه «تماس با فروش» در صفحه موفقیت.</p></td></tr>
				<tr><th><label for="rfq-wa">واتساپ فروش</label></th><td><input id="rfq-wa" name="s[sales_whatsapp]" class="regular-text ltr" value="<?php echo esc_attr( $s['sales_whatsapp'] ); ?>"></td></tr>
				<tr><th><label for="rfq-st">عنوان پیام موفقیت</label></th><td><input id="rfq-st" name="s[success_title]" class="large-text" value="<?php echo esc_attr( $s['success_title'] ); ?>"></td></tr>
				<tr><th><label for="rfq-sm">متن پیام موفقیت</label></th><td><textarea id="rfq-sm" name="s[success_message]" class="large-text" rows="2"><?php echo esc_textarea( $s['success_message'] ); ?></textarea></td></tr>
				<tr><th><label for="rfq-rt">زمان پاسخ‌گویی</label></th><td><textarea id="rfq-rt" name="s[response_time]" class="large-text" rows="2"><?php echo esc_textarea( $s['response_time'] ); ?></textarea></td></tr>
				<tr><th><label for="rfq-pn">متن حریم خصوصی</label></th><td><textarea id="rfq-pn" name="s[privacy_note]" class="large-text" rows="2"><?php echo esc_textarea( $s['privacy_note'] ); ?></textarea></td></tr>
			</table>

			<h2 class="title">بارگذاری فایل و ضد اسپم</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="rfq-mf">حداکثر تعداد فایل</label></th><td><input id="rfq-mf" type="number" min="1" max="10" name="s[max_files]" class="small-text" value="<?php echo esc_attr( $s['max_files'] ); ?>"></td></tr>
				<tr><th><label for="rfq-mb">حداکثر حجم هر فایل (MB)</label></th><td><input id="rfq-mb" type="number" min="1" max="200" name="s[max_mb]" class="small-text" value="<?php echo esc_attr( $s['max_mb'] ); ?>">
					<?php if ( $srvmax && $srvmax < (int) $s['max_mb'] * 1048576 ) : ?>
						<p class="description" style="color:#b32d2e">محدودیت سرور (upload_max_filesize / post_max_size) فعلاً <?php echo esc_html( size_format( $srvmax ) ); ?> است و همین مقدار اعمال می‌شود. برای فایل‌های بزرگ‌تر این مقادیر را در php.ini یا کنترل‌پنل هاست افزایش دهید.</p>
					<?php else : ?>
						<p class="description">محدودیت سرور: <?php echo esc_html( $srvmax ? size_format( $srvmax ) : 'نامشخص' ); ?></p>
					<?php endif; ?>
				</td></tr>
				<tr><th><label for="rfq-ext">پسوندهای مجاز</label></th><td><input id="rfq-ext" name="s[ext]" class="large-text ltr" value="<?php echo esc_attr( $s['ext'] ); ?>"><p class="description">با کاما جدا کنید. فقط این پسوندها پذیرفته می‌شوند: <?php echo esc_html( implode( '، ', psb_rfq_safe_ext() ) ); ?> (پسوندهای دیگر نادیده گرفته می‌شوند).</p></td></tr>
				<tr><th><label for="rfq-rl">سقف ثبت از هر IP</label></th><td><input id="rfq-rl" type="number" min="1" max="100" name="s[rate_limit]" class="small-text" value="<?php echo esc_attr( $s['rate_limit'] ); ?>"> درخواست در ۱۰ دقیقه</td></tr>
				<tr><th><label for="rfq-ms">حداقل زمان پر کردن فرم</label></th><td><input id="rfq-ms" type="number" min="0" max="60" name="s[min_seconds]" class="small-text" value="<?php echo esc_attr( $s['min_seconds'] ); ?>"> ثانیه (ربات‌ها سریع‌تر ارسال می‌کنند)</td></tr>
				<tr><th>سؤال امنیتی ساده</th><td><label><input type="checkbox" name="s[challenge]" value="1" <?php checked( ! empty( $s['challenge'] ) ); ?>> نمایش یک جمع ساده (مثلاً ۳ + ۴) در مرحله آخر — بدون سرویس خارجی</label></td></tr>
				<tr><th><label for="rfq-smsc">سقف روزانه پیامک تأیید</label></th><td><input id="rfq-smsc" type="number" min="0" max="10000" name="s[sms_daily_cap]" class="small-text" value="<?php echo esc_attr( $s['sms_daily_cap'] ); ?>"> پیامک در روز برای کل سایت (۰ = بدون پیامک). به هر شماره موبایل حداکثر یک پیامک در ۲۴ ساعت ارسال می‌شود.</td></tr>
				<tr><th><label for="rfq-uip">سقف حجم بارگذاری هر IP</label></th><td><input id="rfq-uip" type="number" min="1" max="10000" name="s[upload_ip_mb]" class="small-text" value="<?php echo esc_attr( $s['upload_ip_mb'] ); ?>"> مگابایت در ۲۴ ساعت</td></tr>
				<tr><th><label for="rfq-utot">سقف کل حجم فایل‌ها</label></th><td><input id="rfq-utot" type="number" min="100" max="200000" name="s[upload_total_mb]" class="small-text" value="<?php echo esc_attr( $s['upload_total_mb'] ); ?>"> مگابایت برای پوشه uploads/psb-rfq (پیش‌فرض ۲۰۴۸ = ۲ گیگابایت)<?php if ( function_exists( 'psb_rfq_storage_used' ) ) : ?><p class="description">حجم فعلی: <?php echo esc_html( size_format( psb_rfq_storage_used() ) ); ?></p><?php endif; ?></td></tr>
			</table>

			<h2 class="title">فیلدهای فرم</h2>
			<p class="description">برای هر فیلد مشخص کنید نمایش داده شود، الزامی باشد یا پنهان شود. مرحله‌ای که همه فیلدهایش پنهان باشد حذف می‌شود. نام و موبایل همیشه الزامی‌اند.</p>
			<table class="widefat striped" style="max-width:780px">
				<thead><tr><th>مرحله</th><th>فیلد</th><th>وضعیت</th></tr></thead>
				<tbody>
				<?php
				$rows = array();
				foreach ( psb_rfq_spec_defs() as $k => $def ) {
					$rows[ 'spec.' . $k ] = array( 'مشخصات فنی', $def['label'] );
				}
				$step_labels = array( 'project' => 'پروژه', 'files' => 'مدارک', 'contact' => 'تماس' );
				foreach ( psb_rfq_field_defs() as $k => $def ) {
					$rows[ $k ] = array( $step_labels[ $def['step'] ], $def['label'], ! empty( $def['locked'] ) );
				}
				foreach ( $rows as $key => $r ) :
					$cur = psb_rfq_vis( $key );
					?>
					<tr><td><?php echo esc_html( $r[0] ); ?></td><td><?php echo esc_html( $r[1] ); ?></td><td>
						<?php if ( ! empty( $r[2] ) ) : ?>
							<em>همیشه الزامی</em>
						<?php else : ?>
							<select name="s[fields][<?php echo esc_attr( $key ); ?>]" aria-label="<?php echo esc_attr( $r[1] ); ?>">
								<?php foreach ( $vis as $vk => $vl ) : ?>
									<option value="<?php echo esc_attr( $vk ); ?>" <?php selected( $cur, $vk ); ?>><?php echo esc_html( $vl ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2 class="title">فهرست گزینه‌ها</h2>
			<p class="description">هر گزینه در یک خط. قالب: <code>کلید|عنوان</code>. کلید (انگلیسی، بدون فاصله) در داده‌ها ذخیره می‌شود؛ پس از ثبت اولین درخواست‌ها کلیدها را تغییر ندهید، فقط عنوان را ویرایش کنید. برای بازگشت به پیش‌فرض، کادر را خالی کنید.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( psb_rfq_list_defs() as $name => $def ) : ?>
					<tr><th><label for="rfq-l-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
						<td><textarea id="rfq-l-<?php echo esc_attr( $name ); ?>" name="s[lists][<?php echo esc_attr( $name ); ?>]" class="large-text code" rows="<?php echo 'types' === $name ? 14 : 6; ?>" dir="auto"><?php echo esc_textarea( $s['lists'][ $name ] ); ?></textarea>
						<p class="description"><?php echo esc_html( $def[1] ); ?></p></td></tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Page: اتصال بات‌ها
 * ---------------------------------------------------------------------- */

/** Masked secret input. */
function psb_rfq_secret_input( $ch, $key, $value, $label ) {
	$fields = psb_rfq_secret_fields();
	$const  = isset( $fields[ $ch ][ $key ] ) ? $fields[ $ch ][ $key ] : '';
	$id     = 'rfq-' . $ch . '-' . $key;
	echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
	if ( $const && defined( $const ) && constant( $const ) ) {
		echo '<code>در wp-config.php با ثابت ' . esc_html( $const ) . ' تعریف شده است</code></td></tr>';
		return;
	}
	$saved = '' !== (string) $value;
	printf(
		'<input type="password" id="%1$s" name="c[%2$s][%3$s]" class="regular-text ltr" autocomplete="new-password" value="" placeholder="%4$s">',
		esc_attr( $id ),
		esc_attr( $ch ),
		esc_attr( $key ),
		esc_attr( $saved ? '•••••••• ' . mb_substr( (string) $value, -4 ) . ' (ذخیره شده — برای تغییر مقدار جدید وارد کنید)' : '' )
	);
	if ( $saved ) {
		printf( ' <label><input type="checkbox" name="c[%1$s][%2$s__clear]" value="1"> حذف</label>', esc_attr( $ch ), esc_attr( $key ) );
	}
	if ( $const ) {
		echo '<p class="description">امن‌تر: در wp-config.php تعریف کنید: <code>define( \'' . esc_html( $const ) . '\', \'…\' );</code></p>';
	}
	echo '</td></tr>';
}

/** Plain row helpers. */
function psb_rfq_row_text( $ch, $key, $value, $label, $desc = '', $class = 'regular-text ltr' ) {
	$id = 'rfq-' . $ch . '-' . $key;
	printf( '<tr><th><label for="%1$s">%2$s</label></th><td><input id="%1$s" name="c[%3$s][%4$s]" class="%5$s" value="%6$s">%7$s</td></tr>', esc_attr( $id ), esc_html( $label ), esc_attr( $ch ), esc_attr( $key ), esc_attr( $class ), esc_attr( $value ), $desc ? '<p class="description">' . wp_kses( $desc, array( 'code' => array(), 'br' => array(), 'b' => array() ) ) . '</p>' : '' );
}
function psb_rfq_row_area( $ch, $key, $value, $label, $desc = '', $rows = 3 ) {
	$id = 'rfq-' . $ch . '-' . $key;
	printf( '<tr><th><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="c[%3$s][%4$s]" class="large-text code" rows="%5$d" dir="auto">%6$s</textarea>%7$s</td></tr>', esc_attr( $id ), esc_html( $label ), esc_attr( $ch ), esc_attr( $key ), (int) $rows, esc_textarea( $value ), $desc ? '<p class="description">' . wp_kses( $desc, array( 'code' => array(), 'br' => array(), 'b' => array() ) ) . '</p>' : '' );
}
function psb_rfq_row_check( $ch, $key, $value, $label, $text ) {
	printf( '<tr><th>%1$s</th><td><label><input type="checkbox" name="c[%2$s][%3$s]" value="1" %4$s> %5$s</label></td></tr>', esc_html( $label ), esc_attr( $ch ), esc_attr( $key ), checked( ! empty( $value ), true, false ), esc_html( $text ) );
}
function psb_rfq_row_select( $ch, $key, $value, $label, $opts ) {
	$id = 'rfq-' . $ch . '-' . $key;
	echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><select id="' . esc_attr( $id ) . '" name="c[' . esc_attr( $ch ) . '][' . esc_attr( $key ) . ']">';
	foreach ( $opts as $k => $l ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $value, $k, false ), esc_html( $l ) );
	}
	echo '</select></td></tr>';
}

/** Card header + footer with the test button. */
function psb_rfq_channel_card( $ch, $title, $intro, $cb ) {
	$cfg  = psb_rfq_channels();
	$url  = psb_rfq_channel_url( $ch );
	$open = psb_rfq_breaker_open( $url );
	echo '<div class="card" style="max-width:900px;padding:8px 20px 16px;margin:16px 0"><h2>' . esc_html( $title ) . ( ! empty( $cfg[ $ch ]['enabled'] ) ? ' <span class="psb-rfq-pill" style="--c:#059669">فعال</span>' : ' <span class="psb-rfq-pill" style="--c:#64748b">غیرفعال</span>' ) . '</h2>';
	echo '<p>' . wp_kses( $intro, array( 'code' => array(), 'b' => array(), 'a' => array( 'href' => array(), 'target' => array() ), 'br' => array() ) ) . '</p>';
	if ( $open ) {
		echo '<p style="color:#b32d2e">اتصال به این سرویس به‌دلیل خطای قبلی موقتاً قطع شده است (circuit breaker تا ۶ ساعت). دکمه «ذخیره و ارسال پیام تست» آن را بازنشانی می‌کند.</p>';
	}
	echo '<table class="form-table" role="presentation">';
	psb_rfq_row_check( $ch, 'enabled', $cfg[ $ch ]['enabled'], 'فعال', 'ارسال اعلان درخواست‌های جدید از این کانال' );
	call_user_func( $cb, $cfg[ $ch ] );
	echo '</table><p>';
	submit_button( 'ذخیره و ارسال پیام تست', 'secondary', 'psb_rfq_test[' . $ch . ']', false );
	if ( in_array( $ch, array( 'bale', 'telegram' ), true ) ) {
		echo ' ';
		submit_button( 'ذخیره و پیدا کردن Chat ID', 'secondary', 'psb_rfq_updates[' . $ch . ']', false );
	}
	echo '</p></div>';
}

function psb_rfq_channels_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cfg   = psb_rfq_channels();
	$parse = array( 'html' => 'HTML (پیشنهادی)', 'markdown' => 'Markdown', 'plain' => 'متن ساده' );
	?>
	<div class="wrap">
		<h1>اتصال بات‌ها و اعلان‌ها</h1>
		<?php psb_rfq_print_flash(); ?>
		<p>هر درخواست ابتدا در وردپرس ذخیره می‌شود و سپس (بدون معطل کردن مشتری) به کانال‌های فعال ارسال می‌شود. نتیجه هر کانال در صفحه جزئیات درخواست ثبت می‌شود و قابل «ارسال مجدد» است. راهنمای کامل: فایل <code>themes/PSB-CO/RFQ.md</code>.</p>
		<p class="description">
			وضعیت سرور:
			ارسال پس‌زمینه <?php echo function_exists( 'fastcgi_finish_request' ) || function_exists( 'litespeed_finish_request' ) ? '<b>فوری (پس از پاسخ به مرورگر)</b>' : '<b>از طریق WP-Cron</b>'; ?>
			<?php echo ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? ' — <b style="color:#b32d2e">DISABLE_WP_CRON فعال است؛ مطمئن شوید کران‌جاب سرور هر ۱ تا ۵ دقیقه wp-cron.php را اجرا می‌کند.</b>' : ''; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="psb_rfq_save">
			<input type="hidden" name="tab" value="channels">
			<?php wp_nonce_field( 'psb_rfq_save' ); ?>

			<?php
			psb_rfq_channel_card(
				'bale',
				'بات بله (پیشنهادی)',
				'API بله با تلگرام سازگار است و از سرورهای داخل ایران در دسترس است. در بله به <b>@botfather</b> پیام دهید، ربات بسازید و توکن را اینجا وارد کنید. ربات را در گروه فروش عضو (و ادمین) کنید، یک پیام در گروه بفرستید و دکمه «پیدا کردن Chat ID» را بزنید.',
				static function ( $c ) use ( $parse ) {
					psb_rfq_secret_input( 'bale', 'token', $c['token'], 'توکن ربات' );
					psb_rfq_row_area( 'bale', 'chats', $c['chats'], 'Chat IDها', 'هر شناسه در یک خط (کاربر، گروه یا کانال). اعلان برای همه ارسال می‌شود.', 3 );
					psb_rfq_row_select( 'bale', 'parse', $c['parse'], 'قالب متن', $parse );
					psb_rfq_row_check( 'bale', 'files', $c['files'], 'ارسال فایل‌ها', 'مدارک بارگذاری‌شده هم با sendDocument ارسال شود' );
					psb_rfq_row_text( 'bale', 'base', $c['base'], 'آدرس API', 'پیش‌فرض: <code>https://tapi.bale.ai</code>' );
				}
			);
			psb_rfq_channel_card(
				'telegram',
				'بات تلگرام',
				'سرور در ایران معمولاً به <code>api.telegram.org</code> دسترسی ندارد؛ آدرس API را روی یک رله (مثلاً Cloudflare Worker آماده در RFQ.md) بگذارید و رمز مشترک رله را وارد کنید. توکن را از <b>@BotFather</b> بگیرید.',
				static function ( $c ) use ( $parse ) {
					psb_rfq_secret_input( 'telegram', 'token', $c['token'], 'توکن ربات' );
					psb_rfq_row_area( 'telegram', 'chats', $c['chats'], 'Chat IDها', 'هر شناسه در یک خط. گروه‌ها و کانال‌ها معمولاً با <code>-100</code> شروع می‌شوند.', 3 );
					psb_rfq_row_select( 'telegram', 'parse', $c['parse'], 'قالب متن', $parse );
					psb_rfq_row_check( 'telegram', 'files', $c['files'], 'ارسال فایل‌ها', 'مدارک بارگذاری‌شده هم ارسال شود (از طریق رله ممکن است کند باشد)' );
					psb_rfq_row_text( 'telegram', 'base', $c['base'], 'آدرس API / رله', 'مثلاً <code>https://tg-relay.example.workers.dev</code> — پیش‌فرض <code>https://api.telegram.org</code>' );
					psb_rfq_row_text( 'telegram', 'relay_header', $c['relay_header'], 'نام هدر رمز رله', 'پیش‌فرض <code>X-Relay-Secret</code>' );
					psb_rfq_secret_input( 'telegram', 'relay_secret', $c['relay_secret'], 'رمز مشترک رله' );
				}
			);
			psb_rfq_channel_card(
				'whatsapp',
				'واتساپ — WhatsApp Cloud API',
				'نیاز به حساب Meta Business، شماره ثبت‌شده و توکن دسترسی دارد. پیام متنی آزاد فقط تا ۲۴ ساعت پس از آخرین پیام گیرنده به این شماره ارسال می‌شود؛ برای اطمینان یک <b>Message Template</b> با ۴ متغیر (کد، نام، موبایل، اقلام) بسازید و نامش را وارد کنید. برای دسترسی از ایران می‌توانید آدرس API را روی رله بگذارید. برای درگاه‌های دیگر واتساپ از «وب‌هوک عمومی» استفاده کنید.',
				static function ( $c ) {
					psb_rfq_row_text( 'whatsapp', 'phone_id', $c['phone_id'], 'Phone number ID' );
					psb_rfq_secret_input( 'whatsapp', 'token', $c['token'], 'Access token' );
					psb_rfq_row_area( 'whatsapp', 'to', $c['to'], 'شماره‌های گیرنده', 'هر شماره در یک خط، مثلاً <code>09124950082</code>', 2 );
					psb_rfq_row_text( 'whatsapp', 'template', $c['template'], 'نام Template (اختیاری)', 'خالی = پیام متنی (فقط داخل پنجره ۲۴ ساعته).' );
					psb_rfq_row_text( 'whatsapp', 'lang', $c['lang'], 'زبان Template', '', 'small-text ltr' );
					psb_rfq_row_text( 'whatsapp', 'base', $c['base'], 'آدرس API / رله', 'پیش‌فرض <code>https://graph.facebook.com</code>' );
					psb_rfq_row_text( 'whatsapp', 'version', $c['version'], 'نسخه Graph API', '', 'small-text ltr' );
					psb_rfq_row_text( 'whatsapp', 'relay_header', $c['relay_header'], 'نام هدر رمز رله' );
					psb_rfq_secret_input( 'whatsapp', 'relay_secret', $c['relay_secret'], 'رمز مشترک رله' );
				}
			);
			psb_rfq_channel_card(
				'webhook',
				'وب‌هوک عمومی (واتساپ، پیامک، n8n، CRM …)',
				'برای هر درخواست یک POST با بدنه JSON (همه فیلدها + متن آماده <code>text</code> و <code>text_wa</code>) به این آدرس ارسال می‌شود. اگر «رمز امضا» تنظیم شود، هدر <code>X-PSB-Signature: sha256=HMAC(body)</code> اضافه می‌شود.',
				static function ( $c ) {
					psb_rfq_row_text( 'webhook', 'url', $c['url'], 'آدرس وب‌هوک', '', 'large-text ltr' );
					psb_rfq_secret_input( 'webhook', 'secret', $c['secret'], 'رمز امضا (اختیاری)' );
					psb_rfq_row_area( 'webhook', 'headers', $c['headers'], 'هدرهای اضافه', 'هر هدر در یک خط: <code>Authorization: Bearer xxx</code>', 2 );
				}
			);
			psb_rfq_channel_card(
				'email',
				'ایمیل',
				'از wp_mail وردپرس استفاده می‌کند؛ اگر ایمیل‌ها نمی‌رسد، یک افزونه SMTP تنظیم کنید. پیام خطای دقیق در تست نمایش داده می‌شود.',
				static function ( $c ) {
					psb_rfq_row_area( 'email', 'to', $c['to'], 'گیرندگان', 'هر ایمیل در یک خط.', 2 );
					psb_rfq_row_check( 'email', 'attach', $c['attach'], 'پیوست فایل‌ها', 'مدارک تا مجموع ۱۵ مگابایت پیوست شود' );
				}
			);
			psb_rfq_channel_card(
				'sms',
				'پیامک تأیید به مشتری (اختیاری)',
				'پس از ثبت، یک پیامک با کد پیگیری به موبایل مشتری ارسال می‌شود. با هر سرویس پیامکی که API HTTP دارد کار می‌کند. متغیرها: <code>{name}</code> <code>{code}</code> <code>{mobile}</code> <code>{mobile_intl}</code> <code>{message}</code> <code>{response_time}</code> <code>{api_key}</code>. در URL مقادیر URL-encode و در بدنه JSON، JSON-escape می‌شوند (متغیر را داخل "" بگذارید).',
				static function ( $c ) {
					psb_rfq_row_select( 'sms', 'method', $c['method'], 'روش', array( 'POST' => 'POST', 'GET' => 'GET' ) );
					psb_rfq_row_select( 'sms', 'format', $c['format'], 'نوع بدنه', array( 'json' => 'JSON', 'form' => 'فرم (x-www-form-urlencoded)' ) );
					psb_rfq_row_text( 'sms', 'url', $c['url'], 'آدرس API', 'مثال GET: <code>https://api.example.ir/v1/{api_key}/sms/send.json?receptor={mobile}&amp;message={message}</code>', 'large-text ltr' );
					psb_rfq_row_area( 'sms', 'body', $c['body'], 'بدنه درخواست (POST)', 'مثال JSON: <code>{"to":"{mobile}","text":"{message}"}</code> — مثال فرم: <code>receptor={mobile}&amp;message={message}</code>', 3 );
					psb_rfq_row_area( 'sms', 'headers', $c['headers'], 'هدرها', 'هر هدر در یک خط، مثلاً <code>apikey: {api_key}</code>', 2 );
					psb_rfq_secret_input( 'sms', 'api_key', $c['api_key'], 'کلید API' );
					psb_rfq_row_area( 'sms', 'message', $c['message'], 'متن پیامک', '', 2 );
					psb_rfq_row_text( 'sms', 'test_mobile', $c['test_mobile'], 'شماره موبایل تست' );
				}
			);
			?>
			<div class="card" style="max-width:900px;padding:8px 20px 16px">
				<h2>فایل‌ها در اعلان‌ها</h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="rfq-fmm">حداکثر حجم فایل برای ارسال در بات</label></th><td><input id="rfq-fmm" type="number" min="1" max="50" name="c[files_max_mb]" class="small-text" value="<?php echo esc_attr( $cfg['files_max_mb'] ); ?>"> MB <p class="description">فایل‌های بزرگ‌تر فقط در پنل قابل دانلودند (زمان هر درخواست HTTP حداکثر ۸ ثانیه است).</p></td></tr>
				</table>
			</div>
			<?php submit_button( 'ذخیره همه تنظیمات' ); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Save + test handler
 * ---------------------------------------------------------------------- */

add_action(
	'admin_post_psb_rfq_save',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز', 403 );
		}
		check_admin_referer( 'psb_rfq_save' );
		$tab = isset( $_POST['tab'] ) && 'channels' === $_POST['tab'] ? 'channels' : 'settings';

		if ( 'settings' === $tab ) {
			$in = isset( $_POST['s'] ) && is_array( $_POST['s'] ) ? wp_unslash( $_POST['s'] ) : array(); // phpcs:ignore -- sanitised below.
			update_option( 'psb_rfq_settings', psb_rfq_sanitize_settings( $in ), true );
			psb_rfq_flash( 'تنظیمات ذخیره شد.' );
			wp_safe_redirect( psb_rfq_settings_url() );
			exit;
		}

		$in = isset( $_POST['c'] ) && is_array( $_POST['c'] ) ? wp_unslash( $_POST['c'] ) : array(); // phpcs:ignore -- sanitised below.
		psb_rfq_save_channels( psb_rfq_sanitize_channels( $in ) );

		$labels = psb_rfq_channel_labels();
		if ( ! empty( $_POST['psb_rfq_test'] ) && is_array( $_POST['psb_rfq_test'] ) ) {
			$ch = sanitize_key( key( $_POST['psb_rfq_test'] ) );
			if ( isset( $labels[ $ch ] ) ) {
				$r = psb_rfq_test_channel( $ch );
				psb_rfq_flash( $labels[ $ch ] . ' — ' . ( $r[0] ? 'پیام تست ارسال شد: ' : 'ارسال تست ناموفق: ' ) . $r[1], $r[0] );
			}
		} elseif ( ! empty( $_POST['psb_rfq_updates'] ) && is_array( $_POST['psb_rfq_updates'] ) ) {
			$ch = sanitize_key( key( $_POST['psb_rfq_updates'] ) );
			if ( in_array( $ch, array( 'bale', 'telegram' ), true ) ) {
				$cfg = psb_rfq_channels();
				psb_rfq_reset_breaker( psb_rfq_channel_url( $ch ) );
				$r = psb_rfq_bot_updates( $ch, $cfg[ $ch ] );
				psb_rfq_flash( $labels[ $ch ] . ' — ' . $r[1], $r[0] );
			}
		} else {
			psb_rfq_flash( 'تنظیمات کانال‌ها ذخیره شد.' );
		}
		wp_safe_redirect( psb_rfq_settings_url( 'psb-rfq-channels' ) );
		exit;
	}
);

/** Sanitize the general settings. */
function psb_rfq_sanitize_settings( $in ) {
	$def = psb_rfq_default_settings();
	$out = array(
		'max_files'       => min( 10, max( 1, absint( isset( $in['max_files'] ) ? $in['max_files'] : $def['max_files'] ) ) ),
		'max_mb'          => min( 200, max( 1, absint( isset( $in['max_mb'] ) ? $in['max_mb'] : $def['max_mb'] ) ) ),
		'ext'             => implode( ',', array_filter( array_map( 'sanitize_key', explode( ',', strtolower( isset( $in['ext'] ) ? (string) $in['ext'] : '' ) ) ) ) ),
		'rate_limit'      => min( 100, max( 1, absint( isset( $in['rate_limit'] ) ? $in['rate_limit'] : 5 ) ) ),
		'min_seconds'     => min( 60, absint( isset( $in['min_seconds'] ) ? $in['min_seconds'] : 4 ) ),
		'challenge'       => empty( $in['challenge'] ) ? 0 : 1,
		'sms_daily_cap'   => min( 10000, absint( isset( $in['sms_daily_cap'] ) ? $in['sms_daily_cap'] : $def['sms_daily_cap'] ) ),
		'upload_ip_mb'    => min( 10000, max( 1, absint( isset( $in['upload_ip_mb'] ) ? $in['upload_ip_mb'] : $def['upload_ip_mb'] ) ) ),
		'upload_total_mb' => min( 200000, max( 100, absint( isset( $in['upload_total_mb'] ) ? $in['upload_total_mb'] : $def['upload_total_mb'] ) ) ),
		'prefix'          => substr( strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', isset( $in['prefix'] ) ? (string) $in['prefix'] : '' ) ), 0, 8 ),
		'sales_phone'     => psb_rfq_text( isset( $in['sales_phone'] ) ? psb_rfq_latin( $in['sales_phone'] ) : '', 30 ),
		'sales_whatsapp'  => psb_rfq_text( isset( $in['sales_whatsapp'] ) ? psb_rfq_latin( $in['sales_whatsapp'] ) : '', 30 ),
		'success_title'   => psb_rfq_text( isset( $in['success_title'] ) ? $in['success_title'] : '', 120 ),
		'success_message' => psb_rfq_textarea( isset( $in['success_message'] ) ? $in['success_message'] : '', 600 ),
		'response_time'   => psb_rfq_textarea( isset( $in['response_time'] ) ? $in['response_time'] : '', 400 ),
		'privacy_note'    => psb_rfq_textarea( isset( $in['privacy_note'] ) ? $in['privacy_note'] : '', 600 ),
		'lists'           => array(),
		'fields'          => array(),
	);
	foreach ( array( 'ext', 'prefix', 'success_title', 'success_message', 'response_time' ) as $k ) {
		if ( '' === $out[ $k ] ) {
			$out[ $k ] = $def[ $k ];
		}
	}
	foreach ( array_keys( psb_rfq_list_defs() ) as $name ) {
		$v = isset( $in['lists'][ $name ] ) ? trim( sanitize_textarea_field( (string) $in['lists'][ $name ] ) ) : '';
		if ( '' !== $v && $v !== trim( $def['lists'][ $name ] ) ) {
			$out['lists'][ $name ] = $v; // Only store customised lists; empty = default.
		}
	}
	$allowed = array_merge( array_map( static function ( $k ) {
		return 'spec.' . $k;
	}, array_keys( psb_rfq_spec_defs() ) ), array_keys( psb_rfq_field_defs() ) );
	foreach ( $allowed as $key ) {
		$v = isset( $in['fields'][ $key ] ) ? sanitize_key( $in['fields'][ $key ] ) : 'show';
		if ( in_array( $v, array( 'required', 'hide' ), true ) ) {
			$out['fields'][ $key ] = $v;
		}
	}
	return $out;
}

/** Sanitize channel config; empty secret inputs keep the stored value. */
function psb_rfq_sanitize_channels( $in ) {
	$old = get_option( 'psb_rfq_channels', array() );
	$old = is_array( $old ) ? $old : array();
	$def = psb_rfq_default_channels();
	$out = array();
	$url = static function ( $v, $fallback ) {
		$v = esc_url_raw( trim( (string) $v ), array( 'https', 'http' ) );
		return $v ? untrailingslashit( $v ) : $fallback;
	};
	foreach ( $def as $ch => $d ) {
		if ( ! is_array( $d ) ) {
			continue;
		}
		$i   = isset( $in[ $ch ] ) && is_array( $in[ $ch ] ) ? $in[ $ch ] : array();
		$o   = array( 'enabled' => empty( $i['enabled'] ) ? 0 : 1 );
		$get = static function ( $k ) use ( $i ) {
			return isset( $i[ $k ] ) ? (string) $i[ $k ] : '';
		};
		switch ( $ch ) {
			case 'bale':
			case 'telegram':
				$o['chats'] = implode( "\n", array_map( static function ( $v ) {
					return preg_replace( '/[^0-9A-Za-z_@\-]/', '', $v );
				}, psb_rfq_lines( $get( 'chats' ) ) ) );
				$o['parse'] = in_array( $get( 'parse' ), array( 'html', 'markdown', 'plain' ), true ) ? $get( 'parse' ) : 'html';
				$o['files'] = empty( $i['files'] ) ? 0 : 1;
				$o['base']  = $url( $get( 'base' ), $d['base'] );
				if ( 'telegram' === $ch ) {
					$o['relay_header'] = preg_replace( '/[^A-Za-z0-9-]/', '', $get( 'relay_header' ) ) ?: 'X-Relay-Secret';
				}
				break;
			case 'whatsapp':
				$o['phone_id']     = preg_replace( '/\D+/', '', $get( 'phone_id' ) );
				$o['to']           = implode( "\n", array_map( 'psb_rfq_latin', array_map( 'sanitize_text_field', psb_rfq_lines( $get( 'to' ) ) ) ) );
				$o['template']     = preg_replace( '/[^a-z0-9_]/', '', strtolower( $get( 'template' ) ) );
				$o['lang']         = preg_replace( '/[^A-Za-z_]/', '', $get( 'lang' ) ) ?: 'fa';
				$o['base']         = $url( $get( 'base' ), $d['base'] );
				$o['version']      = preg_match( '/^v\d+(\.\d+)?$/', $get( 'version' ) ) ? $get( 'version' ) : $d['version'];
				$o['relay_header'] = preg_replace( '/[^A-Za-z0-9-]/', '', $get( 'relay_header' ) ) ?: 'X-Relay-Secret';
				break;
			case 'webhook':
				$o['url']     = $url( $get( 'url' ), '' );
				$o['headers'] = sanitize_textarea_field( $get( 'headers' ) );
				break;
			case 'email':
				$o['to']     = implode( "\n", array_filter( array_map( 'sanitize_email', psb_rfq_lines( $get( 'to' ) ) ), 'is_email' ) );
				$o['attach'] = empty( $i['attach'] ) ? 0 : 1;
				break;
			case 'sms':
				$o['method']      = 'GET' === strtoupper( $get( 'method' ) ) ? 'GET' : 'POST';
				$o['format']      = 'form' === $get( 'format' ) ? 'form' : 'json';
				$u                = trim( sanitize_text_field( $get( 'url' ) ) );
				$o['url']         = preg_match( '#^https?://#i', $u ) ? $u : '';
				$o['body']        = sanitize_textarea_field( $get( 'body' ) );
				$o['headers']     = sanitize_textarea_field( $get( 'headers' ) );
				$o['message']     = sanitize_textarea_field( $get( 'message' ) ) ?: $d['message'];
				$o['test_mobile'] = psb_rfq_mobile( $get( 'test_mobile' ) );
				break;
		}
		// Secrets: blank = keep, "__clear" = delete.
		$secrets = psb_rfq_secret_fields();
		foreach ( isset( $secrets[ $ch ] ) ? array_keys( $secrets[ $ch ] ) : array() as $sk ) {
			$new = trim( sanitize_text_field( $get( $sk ) ) );
			if ( ! empty( $i[ $sk . '__clear' ] ) ) {
				$o[ $sk ] = '';
			} elseif ( '' !== $new ) {
				$o[ $sk ] = $new;
			} else {
				$o[ $sk ] = isset( $old[ $ch ][ $sk ] ) ? (string) $old[ $ch ][ $sk ] : '';
			}
		}
		$out[ $ch ] = $o;
	}
	$out['files_max_mb'] = min( 50, max( 1, absint( isset( $in['files_max_mb'] ) ? $in['files_max_mb'] : 10 ) ) );
	return $out;
}

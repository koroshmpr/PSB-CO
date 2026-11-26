<?php
/**
 * Comments (articles). Clean list + form in the design system; no author website links.
 *
 * @package bluebox
 */

if ( post_password_required() ) {
	return;
}
$psb_count = (int) get_comments_number();
?>
<div id="comments" class="comments-area">
	<h2 class="text-h3 font-bold text-dark">
		<?php echo $psb_count ? esc_html( psb_fa_num( $psb_count ) . ' دیدگاه' ) : 'دیدگاه خود را بنویسید'; ?>
	</h2>

	<?php if ( have_comments() ) : ?>
		<ol class="comment-list mt-6" role="list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 0,
					'callback'    => function ( $comment, $args, $depth ) {
						$psb_is_staff = user_can( (int) $comment->user_id, 'edit_posts' );
						?>
						<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'list-none' ); ?>>
							<article class="rounded-card border border-line bg-white p-4 sm:p-5">
								<header class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
									<span class="font-bold text-dark"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
									<?php if ( $psb_is_staff ) : ?>
										<span class="badge badge-primary"><?php echo esc_html( psb_brand_name() ); ?></span>
									<?php endif; ?>
									<time class="text-xs text-slate-500" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>"><?php echo esc_html( function_exists( 'shamsi_date' ) ? psb_fa_num( ltrim( shamsi_date( 'd F Y', (int) get_comment_time( 'U', false, true, $comment ) ), '0' ) ) : get_comment_date( '', $comment ) ); ?></time>
								</header>
								<?php if ( '0' === $comment->comment_approved ) : ?>
									<p class="mt-2 text-xs text-primary-700">دیدگاه شما پس از بررسی منتشر می‌شود.</p>
								<?php endif; ?>
								<div class="mt-2 text-sm leading-7 text-slate-700"><?php comment_text( $comment ); ?></div>
								<div class="mt-2 text-xs font-bold text-primary-700">
									<?php
									comment_reply_link(
										array_merge(
											$args,
											array(
												'depth'     => $depth,
												'max_depth' => $args['max_depth'],
												'reply_text' => 'پاسخ',
											)
										),
										$comment
									);
									?>
								</div>
							</article>
						<?php
						// </li> is closed by WordPress (end-callback).
					},
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<div class="mt-8 rounded-card border border-line bg-slate-50 p-5 sm:p-6">
			<?php
			comment_form(
				array(
					'title_reply'          => 'ارسال دیدگاه',
					'title_reply_before'   => '<p id="reply-title" class="mb-4 text-base font-bold text-dark">',
					'title_reply_after'    => '</p>',
					'comment_notes_before' => '<p class="mb-4 text-xs text-slate-500">نشانی ایمیل شما منتشر نمی‌شود.</p>',
					'class_submit'         => 'btn btn-primary',
					'label_submit'         => 'ثبت دیدگاه',
					'comment_field'        => '<p class="mb-4"><label class="label" for="comment">دیدگاه</label><textarea class="input min-h-32" id="comment" name="comment" rows="5" required></textarea></p>',
					'fields'               => array(
						'author' => '<p class="mb-4"><label class="label" for="author">نام</label><input class="input" id="author" name="author" type="text" autocomplete="name" required></p>',
						'email'  => '<p class="mb-4"><label class="label" for="email">ایمیل</label><input class="input" id="email" name="email" type="email" autocomplete="email" dir="ltr" required></p>',
					),
				)
			);
			?>
		</div>
	<?php endif; ?>
</div>

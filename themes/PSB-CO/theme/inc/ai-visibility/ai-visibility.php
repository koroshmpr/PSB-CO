<?php
/**
 * AI visibility (GEO / LLM crawlers) module.
 *
 * Serves /llms.txt, /llms-full.txt, /llms/<id>.md and /ai.txt dynamically,
 * appends AI-crawler rules to robots.txt, keeps a light-weight AI-bot
 * activity log and adds an admin dashboard ("هوش مصنوعی و llms.txt").
 *
 * Entry point — required from functions.php.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_AI_VERSION' ) ) {
	return;
}

const PSB_AI_VERSION = '1.0.0';
define( 'PSB_AI_DIR', __DIR__ );

require_once PSB_AI_DIR . '/settings.php';
require_once PSB_AI_DIR . '/markdown.php';
require_once PSB_AI_DIR . '/generator.php';
require_once PSB_AI_DIR . '/endpoints.php';
require_once PSB_AI_DIR . '/robots.php';
require_once PSB_AI_DIR . '/invalidation.php';
require_once PSB_AI_DIR . '/bot-log.php';

if ( is_admin() ) {
	require_once PSB_AI_DIR . '/admin.php';
}

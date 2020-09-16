<?php

/**
 * Class Blocks is the base class for Gutenberg blocks registration.
 * It provides the ability to register custom blocks using manifest.json.
 *
 * @package EightshiftLibs\Blocks
 */

declare(strict_types=1);

namespace EightshiftLibs\Blocks;

use EightshiftLibs\Config\Config;

/**
 * Class Blocks
 */
class Blocks extends AbstractBlocks
{

	/**
	 * Register all the hooks
	 *
	 * @return void
	 */
	public function register(): void
	{
		// // Register all custom blocks.
		add_action('init', [$this, 'getBlocksDataFullRaw'], 10);
		add_action('init', [$this, 'registerBlocks'], 11);

		// Remove P tags from content.
		remove_filter('the_content', 'wpautop');

		// Create new custom category for custom blocks.
		add_filter('block_categories', [$this, 'getCustomCategory']);

		add_action('after_setup_theme', [$this, 'addThemeSupport'], 25);

		add_action('after_setup_theme', [$this, 'changeEditorColorPalette'], 11);
	}

	/**
	 * Get blocks absolute path
	 *
	 * Prefix path is defined by project config.
	 *
	 * @return string
	 */
	protected function getBlocksPath(): string
	{
		return Config::getProjectPath() . '/src/Blocks';
	}
}

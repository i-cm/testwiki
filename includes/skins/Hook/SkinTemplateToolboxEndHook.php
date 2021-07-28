<?php

namespace MediaWiki\Hook;

use QuickTemplate;

/**
 * This is a hook handler interface, see docs/Hooks.md.
 * Use the hook name "SkinTemplateToolboxEnd" to register handlers implementing this interface.
 *
 * @deprecated since 1.35
 * @ingroup Hooks
 */
interface SkinTemplateToolboxEndHook {
	/**
	 * This hook is called by SkinTemplate skins after toolbox links have
	 * been rendered (useful for adding more).
	 *
	 * @since 1.35
	 *
	 * @param QuickTemplate $sk QuickTemplate based skin template running the hook
	 * @param bool $dummy Called when SkinTemplateToolboxEnd is used from a BaseTemplate skin.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSkinTemplateToolboxEnd( $sk, $dummy );
}

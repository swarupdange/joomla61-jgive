<?php
/**
 * @package     JGive
 * @subpackage  com_jgive
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Campaign model.
 *
 * @since  2.1
 */
if (file_exists(JPATH_SITE . '/components/com_jgive/models/campaignform.php')) {
	require_once JPATH_SITE . '/components/com_jgive/models/campaignform.php';
}
/**
 * JGive Model
 *
 * @since  2.1
 */
class JGiveModelCampaign extends JGiveModelCampaignForm
{
}

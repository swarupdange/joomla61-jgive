<?php
/**
 * @package     JGive
 * @subpackage  com_jgive
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
JLoader::import('campaign', JPATH_SITE . '/components/com_jgive/helpers');
/**
 * PaymentMode API class
 *
 * @since  2.5.4
 */
class JgiveApiResourceStates extends ApiResource
{
	/**
	 * Get list of payment gateways data which is configured on site.
	 *
	 * @return  json payment gateways information
	 *
	 * @since   2.5.4
	 */
	public function get()
	{
		$resultArr = new stdclass;
		$user      = Factory::getUser();

		if (empty($user->id))
		{
			ApiError::raiseError(400, Text::_("PLG_API_JGIVE_INVALID_USER"));
		}

		$jgiveFrontendHelper = new jgiveFrontendHelper;
		$input = Factory::getApplication()->input;
		$countryid    = $input->getInt('country', 0);
		$states = $jgiveFrontendHelper->getState($countryid);

		$resultArr->data = $states;
		$this->plugin->setResponse($resultArr);
	}
}

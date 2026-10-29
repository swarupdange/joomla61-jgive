<?php
/**
 * @package     JGive
 * @subpackage  com_jgive
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;


/**
 * Organization form json controller class.
 *
 * @package  JGive
 * @since    2.5.0
 */
class JGiveControllerOrganizationform extends JGiveController
{
	/**
	 * Checking if email enter exists in organization table.
	 *
	 * @return  boolean returns 1 or 0.
	 *
	 * @since   2.5.0
	 */
	public function Checkemail()
	{
		// Prevent CSRF attack
		Session::checkToken('get') or Factory::getApplication()->close();

		try
		{
			$app             = Factory::getApplication();
			$email           = $app->getInput()->get('email', '', 'STRING');
			$id              = $app->getInput()->get('id', 0, 'INT');
			$vendorId        = $app->getInput()->get('vendor_id', 0, 'INT');
			$organizationObj = JGive::organization($id);
			$result          = $organizationObj->isExist($email, $vendorId);
			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}
}

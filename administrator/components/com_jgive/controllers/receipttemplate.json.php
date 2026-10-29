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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;

/**
 * ReceiptTemplate JSON controller class.
 *
 * @since  2.3.0
 */
class JgiveControllerReceiptTemplate extends FormController
{
	/**
	 * Method to generate 80G certificate
	 *
	 * @return  void
	 *
	 * @since   2.3.0
	 */
	public function generateReceipt()
	{
		// Prevent CSRF attack
		Session::checkToken('get') or Factory::getApplication()->close();

		$donationId = Factory::getApplication()->getInput()->get('donationId', '', 'INT');

		if ($donationId)
		{
			$model  = $this->getModel('receipttemplate');
			$result = $model->generateReceipt($donationId);
			echo new JsonResponse($result, Text::_('COM_JGIVE_CONFIG_SAVED'));
		}
	}
}

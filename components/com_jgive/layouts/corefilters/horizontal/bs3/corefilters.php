<?php
/**
 * @package    Jgive
 * @author     TechJoomla <extensions@techjoomla.com>
 * @website    http://techjoomla.com*
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Cms\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

require_once JPATH_SITE . '/components/com_jgive/models/campaigns.php';
require_once JPATH_SITE . '/components/com_jgive/helpers/campaign.php';

$app            = Factory::getApplication();
$jinput         = $app->input;
$campaignHelper = new campaignHelper;

BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jgive/models');
$campaignsModel               = BaseDatabaseModel::getInstance('Campaigns', 'JgiveModel');
$jgiveParams                  = $campaignsModel->getState();
$campaigns_to_show            = $campaignHelper->campaignsToShowOptions();
$campaign_type_filter_options = $campaignHelper->getCampaignTypeFilterOptions();
$filter_org_ind_type          = $campaignHelper->organization_individual_type();

// Get itemids
$menu       = $app->getMenu();
$activeMenu = $menu->getActive();

if (!empty($activeMenu))
{
	$menuItemId = $activeMenu->id;
}

$singleCampaignItemid = !empty($menuItemId)?$menuItemId:'';

// Take option value
$lists['filter_campaigns_to_show']  = $jgiveParams->get('filter_campaigns_to_show');
$lists['filter_campaign_countries'] = $jgiveParams->get('filter_campaign_countries');
$lists['filter_campaign_states']    = $jgiveParams->get('filter_campaign_states');
$lists['filter_campaign_city']      = $jgiveParams->get('filter_campaign_city');
$lists['filter_org_ind_type']       = $jgiveParams->get('filter_org_ind_type');
$lists['filter_org_ind_type_my']    = $jgiveParams->get('filter_org_ind_type_my');
$lists['filter_campaign_type']      = $jgiveParams->get('filter_campaign_type');

$campaignsModel->ordering_options           = $campaignsModel->getCampignsOrderingOptions();
$campaignsModel->ordering_direction_options = $campaignsModel->getCampignsOrderingDirection();

// For countries
$countryOptions    = array();
$countryOptions[]  = HTMLHelper::_('select.option', '', Text::_('COM_JGIVE_COUNTRY_TOOLTIP'));
$campaignCountries = $campaignsModel->getFilterCountries();

if (!empty ($campaignCountries))
{
	foreach ($campaignCountries  as $campaignCountry)
	{
		$value            = $campaignCountry->country_id;
		$option           = $campaignCountry->country;
		$countryOptions[] = HTMLHelper::_('select.option', $value, $option);
	}
}

// For states
$stateArray     = array();
$stateArray[]   = HTMLHelper::_('select.option', '', Text::_('COM_JGIVE_SELECT_STATE'));
$campaignStates = $campaignsModel->getCampaignsFilterStates();

if (isset($campaignStates))
{
	foreach ($campaignStates  as $campaignState)
	{
		$value        = $campaignState->id;
		$option       = $campaignState->region;
		$stateArray[] = HTMLHelper::_('select.option', $value, $option);
	}
}

// For city
$cityArray      = array();
$cityArray[]    = HTMLHelper::_('select.option', '', Text::_('COM_JGIVE_SELECT_CITY'));
$campaignCities = $campaignsModel->getCampaignsFilterCities();

if (isset($campaignCities))
{
	foreach ($campaignCities  as $campaignCity)
	{
		if ($campaignCity->id )
		{
			$value       = $campaignCity->id;
			$option      = $campaignCity->city;
			$cityArray[] = HTMLHelper::_('select.option', $value, $option);
		}
		elseif (empty($campaignCity->id) && isset($campaignCity->othercity))
		{
			$value       = $campaignCity->othercity;
			$option      = $campaignCity->othercity;
			$cityArray[] = HTMLHelper::_('select.option', $value, $option);
		}
	}
}
?>
<!-- Quick Search -->
<div class="col-xs-12 col-md-9">
<form action="" method="GET" name="jgCoreFilters" id="jgCoreFilters">
	<div class="tj-filterhrizontal pull-left col-xs-12 col-sm-3" >
		<h5>
			<strong>
				<?php echo Text::_('COM_JGIVE_CAMPAIGNS_TO_SHOW');?>
			</strong>
		</h5>
		<?php
			$selected    = $jinput->get('filter_campaigns_to_show', '', 'STRING');
			$camps_quick = 'index.php?option=com_jgive&view=campaigns&layout=all&filter_campaigns_to_show=&Itemid=' . $singleCampaignItemid;
			$camps_quick = Uri::root() . substr(Route::_($camps_quick), strlen(Uri::base(true)) + 1);
		?>
		<div class="<?php echo ($selected == '') ? 'active': ''; ?>">
			<label>
				<input type="radio" class="" name="<?php echo "filter_campaigns_to_show";?>"
					id="quicksearch" value=""
					<?php echo ($selected == '') ? ' checked ': ''; ?>
					onclick="jgiveCommon.filters.submitFilters('jgCoreFilters');"/>
				<?php echo Text::_('COM_JGIVE_RESET_FILTER_TO_ALL'); ?>
			</label>
		</div>
			<?php
			for ($i = 1; $i < count($campaigns_to_show); $i ++)
			{
				$check       = "";
				$selected    = $campaigns_to_show[$i]->value;
				$camps_quick = 'index.php?option=com_jgive&view=campaigns&layout=all&filter_campaigns_to_show=' . $selected . '&Itemid=' . $singleCampaignItemid;
				$camps_quick = Uri::root() . substr(Route::_($camps_quick), strlen(Uri::base(true)) + 1);
				$class       = "";

				if ($lists['filter_campaigns_to_show'] == $selected)
				{
					$class = "active";
					$check = "checked";
				}
				?>
				<div class="<?php echo $class; ?>">
					<label>
						<input type="radio" class=""
						name="<?php echo 'filter_campaigns_to_show';?>"
						id="quicksearchfields" <?php echo $check;?>
						value="<?php echo $campaigns_to_show[$i]->value; ?>"
						onclick="jgiveCommon.filters.submitFilters('jgCoreFilters');" />
						<?php echo $campaigns_to_show[$i]->text; ?>
					</label>
				</div>
				<?php
			}
		?>
	</div>
	<!-- Quick Search E-->

	<!-- Organiazation/individual type filter start-->
	<?php
	if ($jgiveParams->params->get('show_org_ind_type_filter'))
	{
	?>
		<div class="tj-filterhrizontal pull-left col-xs-12 col-sm-3" >
			<h5>
				<strong>
					<?php echo Text::_('COM_JGIVE_ORG_IND_TYPE'); ?>
				</strong>
			</h5>
			<?php
				foreach ($filter_org_ind_type as $organizationType)
				{
					$check       = "";
					$selected    = $organizationType->value;
					$camps_quick = 'index.php?option=com_jgive&view=campaigns&layout=all&filter_org_ind_type=' . $selected . '&Itemid=' . $singleCampaignItemid;
					$camps_quick = Uri::root() . substr(Route::_($camps_quick), strlen(Uri::base(true)) + 1);
					$class       = "";

					if ($lists['filter_org_ind_type'] == $selected)
					{
						$class = "active";
						$check = "checked";
					}
					?>
					<div class="<?php echo $class; ?>">
						<label>
							<input type="radio" class=""
							name="<?php echo 'filter_org_ind_type';?>"
							id="filter_org_ind_type" <?php echo $check;?>
							value="<?php echo $organizationType->value; ?>"
							onclick="jgiveCommon.filters.submitFilters('jgCoreFilters');"/>
							<?php echo $organizationType->text; ?>
						</label>
					</div>
					<?php
				}
			?>
		</div>
	<?php
	}
	?>
	<!-- Organiazation/individual type filter end-->

	<!--  Filters s-->
	<?php
		$campaign_type = $campaignHelper->filedToShowOrHide('campaign_type');

		if (count((array)$jgiveParams->params->get('camp_type')) > 1)
		{
			if ($jgiveParams->params->get('show_type_filter') AND $campaign_type)
			{
				?>
				<div class="tj-filterhrizontal pull-left col-xs-12 col-sm-3" >
					<h5>
						<strong>
							<?php echo Text::_('COM_JGIVE_CAMP_TYPE');?>
						</strong>
					</h5>
					<?php
						$camp_type    = 'index.php?option=com_jgive&view=campaigns&layout=all&filter_campaign_type=&Itemid=' . $singleCampaignItemid;
						$camp_type    = Uri::root() . substr(Route::_($camp_type), strlen(Uri::base(true)) + 1);
						$selectedType = $jinput->get('filter_campaign_type', '', 'string');
					?>
					<div class="<?php echo empty($selectedType) ? 'active': ''; ?>">
						<label>
							<input type="radio" class="tjfieldCheck" name="filter_campaign_type"
							id="campaign_type" value="" <?php echo empty($selectedType) ? 'checked': '';?>
							onclick="jgiveCommon.filters.submitFilters('jgCoreFilters');"/>
							<?php echo Text::_('COM_JGIVE_RESET_FILTER_TO_ALL'); ?>
						</label>
					</div>
					<?php
						for ($i = 1; $i < count($campaign_type_filter_options); $i ++)
						{
							$chec      = "";
							$selected  = $campaign_type_filter_options[$i]->value;
							$camp_type = 'index.php?option=com_jgive&view=campaigns&layout=all&filter_campaign_type=' . $selected . '&Itemid=' . $singleCampaignItemid;
							$camp_type = Uri::root() . substr(Route::_($camp_type), strlen(Uri::base(true)) + 1);
							$class     = "";

							if ($selectedType == $campaign_type_filter_options[$i]->value)
							{
								$chec = 'checked';
							}

							if ($lists['filter_campaign_type'] == $selected)
							{
								$class = "active";
								$chec = "checked";
							}
							?>
							<div class="<?php echo $class; ?>">
								<label>
									<input type="radio" class="tjfieldCheck" name="filter_campaign_type"
									id="filter_campaign_type" value="<?php echo $campaign_type_filter_options[$i]->value?>" <?php echo $chec;?>
									onclick="jgiveCommon.filters.submitFilters('jgCoreFilters');"/>
									<?php echo $campaign_type_filter_options[$i]->text; ?>
								</label>
							</div>
							<?php
						}
					?>
				</div>
		<?php
			}
		}
	?>
</form>
</div>

<form action="" method="post" name="adminForm3" id="adminForm3">
<!--Country Filters start-->
<?php
if ($jgiveParams->params->get('show_place_filter'))
{
?>
	<div class="tj-filterhrizontal pull-left col-xs-12">
		<div class="row margin-top">
			<div class="col-xs-12">
				<h5>
					<strong>
						<?php echo Text::_('COM_JGIVE_PLACE_FILTER');?>
					</strong>
				</h5>
			</div>
			<div class="col-xs-12 col-sm-4 af-mb-10">
				<?php echo HTMLHelper::_('select.genericlist', $countryOptions, "filter_campaign_countries", ' size="1" onchange="jgiveCommon.filters.submitFilters(\'adminForm3\');" class="form-control" name="filter_campaign_countries"', "value", "text", $lists['filter_campaign_countries']);?>
			</div>
			<div class="col-xs-12 col-sm-4 af-mb-10">
				<?php echo HTMLHelper::_('select.genericlist', $stateArray, "filter_campaign_states", ' size="1" onchange="jgiveCommon.filters.submitFilters(\'adminForm3\');" class="form-control"  name="filter_campaign_states"', "value", "text", $lists['filter_campaign_states']);
				?>
			</div>
			<div class="col-xs-12 col-sm-4 af-mb-10">
				<?php echo HTMLHelper::_('select.genericlist', $cityArray, "filter_campaign_city", ' size="1" onchange="jgiveCommon.filters.submitFilters(\'adminForm3\');" class="form-control" name="filter_campaign_city"', "value", "text", $lists['filter_campaign_city']);
				?>
			</div>
		</div>
	</div>
<?php
}
?>
<!--Country Filters end-->
	<input type="hidden" name="option" value="com_jgive" />
	<input type="hidden" name="view" value="campaigns" />
	<input type="hidden" name="layout" value="all" />
</form>

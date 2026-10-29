<?php
/**
 *  @package AdminTools
 *  @copyright Copyright (c)2012-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 *  @version $Id$
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Date\Date;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

class pkg_jgiveInstallerScript
{
	/**
	 * Database driver
	 *
	 * @var DatabaseInterface
	 */
	private $db;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
	}

	/** @var array The list of extra modules and plugins to install */
	private array $installation_queue = array(

		// modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(),
			'site' => array(
				'mod_jgive_campaigns'        => 0,
				'mod_jgive_campaigns_pin'    => 0,
				'mod_jgive_donations'        => 0,
				'mod_jgive_category_progress'=> 0,
			),
		),

		// plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'system' => array(
				'jgive_api'             => 1,
				'jgiveactivities'       => 1,
				'jma_campaign_jgive'    => 0,
				'jgive_invitex_email'   => 0,
				'tjassetsloader'        => 1,
				'tjupdates'             => 1,
			),
			'community' => array(
				'jgive' => 0,
			),
			'payment' => array(
				'2checkout'        => 0,
				'alphauserpoints'  => 0,
				'authorizenet'     => 1,
				'bycheck'          => 1,
				'byorder'          => 1,
				'jomsocialpoints'  => 0,
				'paypal'           => 1,
				'razorpay'         => 0,
			),
			'panverification' => array(
				'setu' => 1,
			),
			'tjvideo' => array(
				'jwplayer' => 1,
				'vimeo'    => 1,
			),
			'tjreports' => array(
				'jgivecampaigns'        => 1,
				'jgivecampaignspromoter'=> 1,
				'jgivedonorcampaign'    => 1,
				'jgivedonors'           => 1,
				'jgivegiveback'         => 1,
				'jgivedonororganization'=> 1,
				'jgivedonorindividual'  => 1,
			),
			'content' => array(
				'jlike_jgive' => 1,
			),
			'actionlog' => array(
				'jgive' => 1,
			),
			'privacy' => array(
				'jgive' => 1,
			),
			'api' => array(
				'jgive' => 1,
			),
			'tjsms' => array(
				'twilio'     => 0,
				'clickatell' => 0,
				'mvaayoo'    => 0,
				'smshorizon' => 0,
			),
			'tjurlshortner' => array(
				'bitly' => 0,
			),
			'user' => array(
				'tjnotificationsmobilenumber' => 0,
			),
			'finder' => array(
				'jgivecampaigns' => 0,
			),
		),
		'libraries' => array(
			'techjoomla' => 1,
		),
	);

	/** @var array The list of extra modules and plugins to uninstall */
	private array $uninstall_queue = array(
		'modules' => array(
			'admin' => array(),
			'site' => array(
				'mod_jgive_campaigns'        => 0,
				'mod_jgive_campaigns_pin'    => 0,
				'mod_jgive_donations'        => 0,
				'mod_jgive_category_progress'=> 0,
			),
		),
		'plugins' => array(
			'system' => array(
				'jgive_api'           => 1,
				'jgiveactivities'     => 1,
				'jma_campaign_jgive'  => 0,
				'jgive_invitex_email' => 0,
			),
			'community' => array(
				'jgive' => 0,
			),
			'payment'        => array(),
			'panverification'=> array(),
			'tjvideo'        => array(),
			'actionlog'      => array(
				'jgive' => 1,
			),
			'privacy'        => array(
				'jgive' => 1,
			),
		),
	);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   string            $type    install, update or discover_update
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function preflight(string $type, InstallerAdapter $parent): void
	{
		// Add version checks here if you want
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string            $type    install, update or discover_update
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function postflight(string $type, InstallerAdapter $parent): void
	{
		// Install subextensions
		$status = $this->_installSubextensions($parent);

		// Install Techjoomla Straper
		$straperStatus = $this->_installStraper($parent);

		// Adds the overridden subform layouts file
		$this->_addLayout($parent);

		// Get in built reports from tjreports extension
		$this->_getInBuiltTJReports();

		// Show the post-installation page
		$this->_renderPostInstallation($status, $straperStatus, $parent);
	}

	/**
	 * Get in built tjReports and install them with extension
	 *
	 * @return  int  Number of installed reports (if available)
	 */
	public function _getInBuiltTJReports(): int
	{
		$installed = 0;

		try
		{
			$app        = Factory::getApplication();
			$component  = $app->bootComponent('com_tjreports');
			$mvcFactory = $component->getMVCFactory();
			$model      = $mvcFactory->createModel('Reports', 'Administrator', ['ignore_request' => true]);

			if ($model && method_exists($model, 'addTjReportsPlugins'))
			{
				$installed = (int) $model->addTjReportsPlugins();
			}
		}
		catch (\Throwable $e)
		{
			// com_tjreports not installed or other issue; fail silently
		}

		return $installed;
	}

	/**
	 * Renders the post-installation message
	 *
	 * @param   \stdClass         $status         Installation status
	 * @param   array             $straperStatus  Strapper installation status
	 * @param   InstallerAdapter  $parent         Parent installer adapter
	 *
	 * @return void
	 */
	private function _renderPostInstallation(\stdClass $status, array $straperStatus, InstallerAdapter $parent): void
	{
		// Show link for payment plugins.
		$rows = 1; ?>
		<div class="techjoomla-bootstrap" >
			<table class="table-condensed table">
				<thead>
					<tr>
						<th class="title" colspan="2">Extension</th>
						<th width="30%">Status</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="3"></td>
					</tr>
				</tfoot>
				<tbody>
					<tr class="row0">
						<td class="key" colspan="2"><h4>JGive component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TjFields component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TJ-Notifications component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TjActivityStream component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TjPrivacy component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TjVendor component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>TjReport component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2"><h4>Jlike component</h4></td>
						<td><strong style="color: green">Installed</strong></td>
					</tr>
					<tr class="row0">
						<td class="key" colspan="2">
							<strong>TechJoomla Strapper <?php echo $straperStatus['version']; ?></strong> [<?php echo $straperStatus['date']; ?>]
						</td>
						<td><strong>
							<span style="color: <?php echo $straperStatus['required'] ? ($straperStatus['installed'] ? 'green' : 'red') : '#660'; ?>; font-weight: bold;">
								<?php echo $straperStatus['required'] ? ($straperStatus['installed'] ? 'Installed' : 'Not Installed') : 'Already up-to-date'; ?>
							</span>
						</strong></td>
					</tr>
					<?php
					if (!empty($status->modules))
					{
						?>
						<tr>
							<th>Module</th>
							<th>Client</th>
							<th></th>
						</tr>
						<?php
						foreach ($status->modules as $module)
						{ ?>
							<tr class="row<?php echo ($rows++ % 2); ?>">
								<td class="key"><?php echo $module['name']; ?></td>
								<td class="key"><?php echo ucfirst($module['client']); ?></td>
								<td><strong style="color: <?php echo ($module['result']) ? "green" : "red";?>"><?php echo ($module['result']) ? 'Installed' : 'Not installed';?></strong></td>
							</tr>
						<?php
						}
					}?>
					<?php
					if (!empty($status->plugins))
					{ ?>
						<tr>
							<th>Plugin</th>
							<th>Group</th>
							<th></th>
						</tr>
						<?php
						foreach ($status->plugins as $plugin)
						{?>
							<tr class="row<?php echo ($rows++ % 2);?>">
								<td class="key"><?php echo ucfirst($plugin['name']);?></td>
								<td class="key"><?php echo ucfirst($plugin['group']);?></td>
								<td><strong style="color: <?php echo ($plugin['result']) ? "green" : "red";?>"><?php echo ($plugin['result']) ? 'Installed' : 'Not installed';?></strong></td>
							</tr>
						<?php
						}
					}?>
					<?php
					if (!empty($status->libraries))
					{?>
						<tr class="row1">
							<th>Library</th>
							<th></th>
							<th></th>
						</tr>
						<?php
						foreach ($status->libraries as $libraries)
						{?>
							<tr class="row2">
								<td class="key"><?php echo ucfirst($libraries['name']);?></td>
								<td class="key"></td>
								<td><strong style="color: <?php echo ($libraries['result']) ? "green" : "red";?>"><?php echo ($libraries['result']) ? 'Installed' : 'Not installed';?></strong>
								<?php
								if (!empty($libraries['result']))
								{
									echo $libraries['status']
										? '<span class="label label-success">Enabled</span>'
										: '<span class="label label-important">Disabled</span>';
								}
								?>
								</td>
							</tr>
						<?php
						}?>
					<?php
					}
					if (!empty($status->app_install))
					{
						if (count($status->app_install))
						{?>
							<tr class="row1">
								<th>EasySocial App</th>
								<th></th>
								<th></th>
							</tr>
							<?php
							foreach ($status->app_install as $app_install)
							{?>
								<tr class="row2">
									<td class="key"><?php echo ucfirst($app_install['name']);?></td>
									<td class="key"></td>
									<td><strong style="color: <?php echo ($app_install['result']) ? "green" : "red";?>"><?php echo ($app_install['result']) ? 'Installed' : 'Not installed';?></strong>
										<?php
										if (!empty($app_install['result'])) // if installed then only show msg
										{
											echo $app_install['status']
												? '<span class="label label-success">Enabled</span>'
												: '<span class="label label-important">Disabled</span>';
										}?>
									</td>
								</tr>
							<?php
							}
						}
					}?>
				</tbody>
			</table>
		</div>

		<br/>
		<div class="row-fluid">
			<div class="span12">
				<div class="alert alert-info">
					<a class="btn"
					   href="index.php?option=com_jgive&task=createActivity"
					   target="_blank">
						<i class="icon-refresh"></i>
						Create Activities
					</a>
					Click this to create activities for your existing campaigns.
				</div>
			</div>
		</div>
		<br/>
		<?php
	}

	/**
	 * Adds the overridden subform layout files
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function _addLayout(InstallerAdapter $parent): void
	{
		$src              = $parent->getParent()->getPath('source');
		$JGsubformlayouts = $src . "/layouts/JGive";

		Folder::copy($JGsubformlayouts, JPATH_SITE . '/layouts/JGive', '', true);
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return  \stdClass  The subextension installation status
	 */
	private function _installSubextensions(InstallerAdapter $parent): \stdClass
	{
		$src = $parent->getParent()->getPath('source');

		$status          = new \stdClass();
		$status->modules = array();
		$status->plugins = array();
		$status->libraries = array();
		$status->app_install = array();

		// Modules installation
		if (count($this->installation_queue['modules']))
		{
			foreach ($this->installation_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}

						$path = "$src/modules/$folder/$module";

						if (!is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}
						if (!is_dir($path))
						{
							$path = "$src/modules/$module";
						}
						if (!is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}
						if (!is_dir($path))
						{
							continue;
						}

						// Was the module already installed?
						$sql = $this->db->getQuery(true)
							->select('COUNT(*)')
							->from($this->db->quoteName('#__modules'))
							->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_' . $module));
						$this->db->setQuery($sql);
						$count = (int) ($this->db->loadResult() ?? 0);

						$installer = new Installer();
						$installer->setDatabase($this->db);
						$result    = $installer->install($path);

						$status->modules[] = array(
							'name'   => 'mod_' . $module,
							'client' => $folder,
							'result' => $result,
						);

						// Modify where it's published and its published state
						if (!$count)
						{
							// A. Position and state
							list($modulePosition, $modulePublished) = $modulePreferences;

							if ($modulePosition === 'cpanel')
							{
								$modulePosition = 'icon';
							}

							$sql = $this->db->getQuery(true)
								->update($this->db->quoteName('#__modules'))
								->set($this->db->quoteName('position') . ' = ' . $this->db->quote($modulePosition))
								->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_' . $module));

							if ($modulePublished)
							{
								$sql->set($this->db->quoteName('published') . ' = ' . $this->db->quote('1'));
							}

							$this->db->setQuery($sql);
							$this->db->execute();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder === 'admin')
							{
								$query = $this->db->getQuery(true);
								$query->select('MAX(' . $this->db->quoteName('ordering') . ')')
									->from($this->db->quoteName('#__modules'))
									->where($this->db->quoteName('position') . ' = ' . $this->db->quote($modulePosition));
								$this->db->setQuery($query);
								$position = (int) ($this->db->loadResult() ?? 0);
								$position++;

								$query = $this->db->getQuery(true);
								$query->update($this->db->quoteName('#__modules'))
									->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($position))
									->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_' . $module));
								$this->db->setQuery($query);
								$this->db->execute();
							}

							// C. Link to all pages
							$query = $this->db->getQuery(true);
							$query->select($this->db->quoteName('id'))
								->from($this->db->quoteName('#__modules'))
								->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_' . $module));
							$this->db->setQuery($query);
							$moduleid = (int) ($this->db->loadResult() ?? 0);

							$query = $this->db->getQuery(true);
							$query->select('*')
								->from($this->db->quoteName('#__modules_menu'))
								->where($this->db->quoteName('moduleid') . ' = ' . $this->db->quote($moduleid));
							$this->db->setQuery($query);
							$assignments = $this->db->loadObjectList();
							$isAssigned  = !empty($assignments);

							if (!$isAssigned)
							{
								$o = (object) array(
									'moduleid' => $moduleid,
									'menuid'   => 0,
								);
								$this->db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		// Plugins installation
		if (count($this->installation_queue['plugins']))
		{
			foreach ($this->installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}
						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}
						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}
						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $this->db->getQuery(true)
							->select('COUNT(*)')
							->from($this->db->quoteName('#__extensions'))
							->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
							->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
						$this->db->setQuery($query);
						$count = (int) ($this->db->loadResult() ?? 0);

						$installer = new Installer();
						$installer->setDatabase($this->db);
						$result    = $installer->install($path);

						$status->plugins[] = array(
							'name'   => 'plg_' . $plugin,
							'group'  => $folder,
							'result' => $result,
						);

						if ($published && !$count)
						{
							$query = $this->db->getQuery(true)
								->update($this->db->quoteName('#__extensions'))
								->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote('1'))
								->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
								->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
							$this->db->setQuery($query);
							$this->db->execute();
						}
					}
				}
			}
		}

		// Libraries installation
		if (count($this->installation_queue['libraries']))
		{
			foreach ($this->installation_queue['libraries'] as $folder => $published)
			{
				$path = "$src/libraries/$folder";

				if (!file_exists($path))
				{
					continue;
				}

				$query = $this->db->getQuery(true)
					->select('COUNT(*)')
					->from($this->db->quoteName('#__extensions'))
					->where(
						'( ' .
						$this->db->quoteName('name') . ' = ' . $this->db->quote($folder) .
						' OR ' .
						$this->db->quoteName('element') . ' = ' . $this->db->quote($folder) .
						' )'
					)
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('library'));
				$this->db->setQuery($query);
				$count = (int) ($this->db->loadResult() ?? 0);

				$installer = new Installer();
				$installer->setDatabase($this->db);
				$result    = $installer->install($path);

				$status->libraries[] = array(
					'name'   => $folder,
					'group'  => $folder,
					'result' => $result,
					'status' => $published,
				);

				if ($published && !$count)
				{
					$query = $this->db->getQuery(true)
						->update($this->db->quoteName('#__extensions'))
						->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote('1'))
						->where(
							'( ' .
							$this->db->quoteName('name') . ' = ' . $this->db->quote($folder) .
							' OR ' .
							$this->db->quoteName('element') . ' = ' . $this->db->quote($folder) .
							' )'
						)
						->where($this->db->quoteName('type') . ' = ' . $this->db->quote('library'));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
		}

		// install easysocial plugin
		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			$installer = \Foundry::get('Installer');
			// The $path here refers to your application path
			$installer->load($src . "/plugins/easysocial_camp_plg");
			$plg_install           = $installer->install();
			$status->app_install[] = array(
				'name'   => 'easysocial_camp_plg',
				'group'  => 'easysocial_camp_plg',
				'result' => $plg_install,
				'status' => '1',
			);
		}

		return $status;
	}

	/**
	 * Installs TechJoomla Strapper
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return  array  Strapper installation status
	 */
	private function _installStraper(InstallerAdapter $parent): array
	{
		$src = $parent->getParent()->getPath('source');

		// Install the FOF framework
		$source = $src . '/tj_strapper';
		$target = JPATH_ROOT . '/media/techjoomla_strapper';

		$haveToInstallStraper = false;

		if (!file_exists($target))
		{
			$haveToInstallStraper = true;
		}
		else
		{
			$straperVersion = array();

			if (File::exists($target . '/version.txt'))
			{
				$rawData                     = file_get_contents($target . '/version.txt');
				$info                        = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version' => trim($info[0]),
					'date'    => new Date(trim($info[1])),
				);
			}
			else
			{
				$straperVersion['installed'] = array(
					'version' => '0.0',
					'date'    => new Date('2011-01-01'),
				);
			}

			$rawData                   = file_get_contents($source . '/version.txt');
			$info                      = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version' => trim($info[0]),
				'date'    => new Date(trim($info[1])),
			);

			$haveToInstallStraper = $straperVersion['package']['date']->toUnix() > $straperVersion['installed']['date']->toUnix();
		}

		$installedStraper = false;

		if ($haveToInstallStraper)
		{
			$versionSource    = 'package';
			$installer        = new Installer();
			$installer->setDatabase($this->db);
			$installedStraper = $installer->install($source);
		}
		else
		{
			$versionSource = 'installed';
		}

		if (!isset($straperVersion))
		{
			$straperVersion = array();

			if (File::exists($target . '/version.txt'))
			{
				$rawData                     = file_get_contents($target . '/version.txt');
				$info                        = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version' => trim($info[0]),
					'date'    => new Date(trim($info[1])),
				);
			}
			else
			{
				$straperVersion['installed'] = array(
					'version' => '0.0',
					'date'    => new Date('2011-01-01'),
				);
			}

			$rawData                   = file_get_contents($source . '/version.txt');
			$info                      = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version' => trim($info[0]),
				'date'    => new Date(trim($info[1])),
			);
			$versionSource             = 'installed';
		}

		if (!($straperVersion[$versionSource]['date'] instanceof Date))
		{
			$straperVersion[$versionSource]['date'] = new Date();
		}

		return array(
			'required'  => $haveToInstallStraper,
			'installed' => $installedStraper,
			'version'   => $straperVersion[$versionSource]['version'],
			'date'      => $straperVersion[$versionSource]['date']->format('Y-m-d'),
		);
	}

	/**
	 * method to install the component
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function install(InstallerAdapter $parent): void
	{
		// $parent is the class calling this method
	}

	/**
	 * method to uninstall the component
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function uninstall(InstallerAdapter $parent): void
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return  \stdClass  The subextension uninstallation status
	 */
	private function _uninstallSubextensions(InstallerAdapter $parent): \stdClass
	{
		$status          = new \stdClass();
		$status->modules = array();
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if (count($this->uninstall_queue['modules']))
		{
			foreach ($this->uninstall_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Find the module ID
						$sql = $this->db->getQuery(true)
							->select($this->db->quoteName('extension_id'))
							->from($this->db->quoteName('#__extensions'))
							->where($this->db->quoteName('element') . ' = ' . $this->db->quote($module))
							->where($this->db->quoteName('type') . ' = ' . $this->db->quote('module'));
						$this->db->setQuery($sql);
						$extensionIds = $this->db->loadColumn();
						$id = $extensionIds[0] ?? null;

						// Uninstall the module
						if ($id)
						{
							$installer         = new Installer();
							$installer->setDatabase($this->db);
							$result            = $installer->uninstall('module', (int) $id, 1);
							$status->modules[] = array(
								'name'   => 'mod_' . $module,
								'client' => $folder,
								'result' => $result,
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (count($this->uninstall_queue['plugins']))
		{
			foreach ($this->uninstall_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $this->db->getQuery(true)
							->select($this->db->quoteName('extension_id'))
							->from($this->db->quoteName('#__extensions'))
							->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
							->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
							->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
						$this->db->setQuery($sql);
						$extensionIds = $this->db->loadColumn();
						$id = $extensionIds[0] ?? null;

						if ($id)
						{
							$installer         = new Installer();
							$installer->setDatabase($this->db);
							$result            = $installer->uninstall('plugin', (int) $id);
							$status->plugins[] = array(
								'name'   => 'plg_' . $plugin,
								'group'  => $folder,
								'result' => $result,
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Method to update the component
	 *
	 * @param   InstallerAdapter  $parent  Parent installer adapter
	 *
	 * @return void
	 */
	public function update(InstallerAdapter $parent): void
	{
		$config  = Factory::getConfig();
		$configdb = $config->get('db');
		$dbprefix = $config->get('dbprefix');

		// Add any DB migration logic here if needed
	}
}

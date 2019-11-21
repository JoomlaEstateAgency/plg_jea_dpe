<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Jea.Dpe
 *
 * @copyright   Copyright (C) 2007 - 2019 PHILIP Sylvain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Install Script file of JEA component
 */
class plgjeadpeInstallerScript
{
	/**
	 * Method to install the extension
	 *
	 * @return void
	 */
	function install ($parent)
	{
		if (!JFolder::exists(JPATH_ROOT . '/images/com_jea/dpe'))
		{
			JFolder::create(JPATH_ROOT . '/images/com_jea/dpe');
		}
	}

	/**
	 * Method to uninstall the extension
	 *
	 * @return void
	 */
	function uninstall ($parent)
	{
		if (JFolder::exists(JPATH_ROOT . '/images/com_jea/dpe'))
		{
			JFolder::delete(JPATH_ROOT . '/images/com_jea/dpe');
		}
	}

	/**
	 * Method to update the extension
	 *
	 * @return void
	 */
	function update ($parent)
	{
		if (! JFolder::exists(JPATH_ROOT . '/images/com_jea/dpe'))
		{
			JFolder::create(JPATH_ROOT . '/images/com_jea/dpe');
		}
	}

	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight ($type, $parent)
	{
		$db = JFactory::getDbo();
		$db->setQuery('SHOW COLUMNS FROM #__jea_properties');
		$cols = $db->loadObjectList('Field');

		if (! isset($cols['dpe_energy']) && ! isset($cols['dpe_ges']))
		{
			$query = 'ALTER TABLE `#__jea_properties` ' . "ADD `dpe_energy` SMALLINT(4) NOT NULL DEFAULT '-1',"
					. "ADD `dpe_ges` SMALLINT(4) NOT NULL DEFAULT '-1'";
			$db->setQuery($query);
			$db->query();
		}
		elseif (isset($cols['dpe_energie']) && isset($cols['dpe_ges']))
		{
			$query = 'ALTER TABLE `#__jea_properties` ' . "CHANGE `dpe_energie` `dpe_energy` SMALLINT(4) NOT NULL DEFAULT '-1',"
					. "CHANGE `dpe_ges` `dpe_ges` SMALLINT(4) NOT NULL DEFAULT '-1'";
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * Method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight ($type, $parent)
	{
	}
}



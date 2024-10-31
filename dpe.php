<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Jea.Dpe
 *
 * @copyright   Copyright (C) 2007 - 2023 PHILIP Sylvain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Application\WebApplication;

defined('_JEXEC') or die;

class PlgJeaDpe extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.1.3
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeSaveProperty' => 'onBeforeSaveProperty',
			'onBeforeEndPanels' => 'onBeforeEndPanels',
			'onAfterShowDescription' => 'onAfterShowDescription',
		];
	}

	/**
	 * @var string
	 */
	protected $autoloadLanguage = true;

	/**
	 * onBeforeSaveProperty method
	 *
	 * @param  Event  $event  The subscribed event
	 *
	 * @return boolean  True on success
	 */
	public function onBeforeSaveProperty(Event $event)
	{
		$arguments = $event->getArguments();
		$row = $arguments[1];
		assert($row instanceof TableProperty);

		$app = Factory::getApplication();

		if ($app instanceof WebApplication)
		{
			$input = $app->input;

			if ($input->get('dpe_energy') || $input->get('dpe_ges'))
			{
				$row->dpe_energy = $input->getFloat('dpe_energy', - 1.0);
				$row->dpe_ges = $input->getFloat('dpe_ges', - 1.0);
			}
		}

		return true;
	}

	/**
	 * onBeforeEndPane method
	 * Called in the admin property form
	 *
	 * @param  Event  $event  The subscribed event
	 *
	 * @return void
	 */
	public function onBeforeEndPanels(Event $event)
	{
		$arguments = $event->getArguments();
		$row = $arguments[0];

		if ($row->dpe_energy === null)
		{
			$row->dpe_energy = '-1';
		}

		if ($row->dpe_ges === null)
		{
			$row->dpe_ges = '-1';
		}

		$energyLabel = Text::_('PLG_JEA_DPE_ENERGY_CONSUMPTION');
		$energyDesc = $energyLabel . '::' . Text::_('PLG_JEA_DPE_ENERGY_CONSUMPTION_DESC');
		$gesLabel = Text::_('PLG_JEA_DPE_EMISSIONS_GES');
		$gesDesc = $gesLabel . '::' . Text::_('PLG_JEA_DPE_EMISSIONS_GES_DESC');

		echo HTMLHelper::_('bootstrap.addSlide', 'property-sliders', Text::_('PLG_JEA_DPE'), 'dpe-pane');

		echo '
        <fieldset class="panelform">
          <div class="control-group">
            <div class="control-label">
              <label for="dpe_energy" class="hasTip" title="' . $energyDesc . '">' . $energyLabel . ' : </label>
            </div>
            <div class="controls">
              <input type="number" name="dpe_energy" id="dpe_energy" value="' . $row->dpe_energy . '" class="form-control numberbox" />
            </div>
          </div>
          <div class="control-group">
            <div class="control-label">
              <label for="dpe_ges" class="hasTip" title="' . $gesDesc . '">' . $gesLabel . ' : </label>
            </div>
            <div class="controls">
              <input type="number" name="dpe_ges" id="dpe_ges" value="' . $row->dpe_ges . '" class="form-control numberbox" />
            </div>
          </div>
        </fieldset>';

		 echo HTMLHelper::_('bootstrap.endSlide');
	}

	/**
	 * onAfterShowDescription method
	 * Called in the default_item.php layout
	 *
	 * @param  Event  $event  The subscribed event
	 *
	 * @return void
	 */
	public function onAfterShowDescription(Event $event)
	{
		$arguments = $event->getArguments();
		$row = $arguments[0];

		if ($row->dpe_energy < 0 && $row->dpe_ges < 0)
		{
			return;
		}

		echo '<h3 class="jea_dpe">' . Text::_('PLG_JEA_DPE') . '</h3>' . PHP_EOL;
		echo '<div class="jea_dpe">' . PHP_EOL;

		if ($row->dpe_energy >= 0)
		{
			try
			{
				$img = $this->generateEnergyImage($row->dpe_energy, $row->dpe_ges, (int) $row->living_space);
				echo '<img src="' . $img . '" alt="' . Text::_('PLG_JEA_DPE_ENERGY_CONSUMPTION') . '" style="margin-right: 50px;" />';
			}
			catch (Exception $e)
			{
				echo '<strong style="color:red">' . $e->getMessage() . '</strong>';
			}
		}

		if ($row->dpe_ges >= 0)
		{
			try
			{
				$img = $this->generateGESImage($row->dpe_ges, $row->dpe_ges, (int) $row->living_space);
				echo '<img src="' . $img . '" alt="' . Text::_('PLG_JEA_DPE_EMISSIONS_GES') . '" />';
			}
			catch (Exception $e)
			{
				echo '<strong style="color:red">' . $e->getMessage() . '</strong>';
			}
		}

		echo '</div>' . PHP_EOL;
	}

	private function getLanguageTag()
	{
		$app = Factory::getApplication();
		assert($app instanceof WebApplication);

		return $app->getLanguage()->getTag();
	}

	private function generateEnergyImage($energy = 0, $ges = 0, $superficie = 0)
	{
		require __DIR__ . '/vendor/autoload.php';

		$type = LBIGroupDpeGenerator\DpeGenerator::DPE_TYPE;

		$tag = $this->getLanguageTag();
		$imagePath = JPATH_ROOT . '/images/com_jea/dpe/energy-' . $tag . '-' . $energy . '.png';
		$imageURL = Uri::root(true) . '/images/com_jea/dpe/energy-' . $tag . '-' . $energy . '.png';

		if (!file_exists($imagePath))
		{
			$dpe = new LBIGroupDpeGenerator\DpeGenerator();
			$dpe->setDpeVal($energy);
			$dpe->setGesVal($ges);
			if ($superficie) $dpe->setSuperficie($superficie);
			$dpe->setPictureType($type);
			$dpe->setImageSize(LBIGroupDpeGenerator\DpeGenerator::WEB_SIZE_TYPE);
			$dpe->setPathToWriteImage(JPATH_ROOT . '/images/com_jea/dpe/');
			$dpe->setNameOfPicture('energy-' . $tag . '-' . $energy);
			$dpe->setGenerateImage(true);
			$dpe->generatePicture();
		}

		return $imageURL;
	}

	private function generateGESImage ($energy = 0, $ges = 0, $superficie = 0)
	{
		require __DIR__ . '/vendor/autoload.php';

		$type = LBIGroupDpeGenerator\DpeGenerator::GES_TYPE;

		$tag = $this->getLanguageTag();
		$imagePath = JPATH_ROOT . '/images/com_jea/dpe/ges-' . $tag . '-' . $ges . '.png';
		$imageURL = Uri::root(true) . '/images/com_jea/dpe/ges-' . $tag . '-' . $ges . '.png';

		if (!file_exists($imagePath))
		{
			$dpe = new LBIGroupDpeGenerator\DpeGenerator();
			$dpe->setDpeVal($energy);
			$dpe->setGesVal($ges);
			if ($superficie) $dpe->setSuperficie($superficie);
			$dpe->setPictureType($type);
			$dpe->setImageSize(LBIGroupDpeGenerator\DpeGenerator::WEB_SIZE_TYPE);
			$dpe->setPathToWriteImage(JPATH_ROOT . '/images/com_jea/dpe/');
			$dpe->setNameOfPicture('ges-' . $tag . '-' . $ges);
			$dpe->setGenerateImage(true);
			$dpe->generatePicture();
		}


		return $imageURL;
	}
}

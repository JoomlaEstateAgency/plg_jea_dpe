<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Jea.Dpe
 *
 * @copyright   Copyright (C) 2007 - 2019 PHILIP Sylvain. All rights reserved.
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
				$img = $this->generateEnergyImage($row->dpe_energy);
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
				$img = $this->generateGESImage($row->dpe_ges);
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

	private function generateEnergyImage($energy = 0)
	{
		$tag = $this->getLanguageTag();
		$imagePath = JPATH_ROOT . '/images/com_jea/dpe/energy-' . $tag . '-' . $energy . '.png';
		$imageURL = Uri::root(true) . '/images/com_jea/dpe/energy-' . $tag . '-' . $energy . '.png';

		if (!file_exists($imagePath))
		{
			$levels = array(50, 90, 150, 230, 330, 450);
			$labels = array(
				'measure' => Text::_('PLG_JEA_DPE_ENERGY_MEASURE'),
				'top-left' => Text::_('PLG_JEA_DPE_ENERGY_TOP_LEFT_LABEL'),
				'bottom-left' => Text::_('PLG_JEA_DPE_ENERGY_BOTTOM_LEFT_LABEL')
			);

			$this->generateGDImage($energy, $imagePath, 'dpe-energy.png', $levels, $labels);
		}

		return $imageURL;
	}

	private function generateGESImage ($ges = 0)
	{
		$tag = $this->getLanguageTag();
		$imagePath = JPATH_ROOT . '/images/com_jea/dpe/ges-' . $tag . '-' . $ges . '.png';
		$imageURL = Uri::root(true) . '/images/com_jea/dpe/ges-' . $tag . '-' . $ges . '.png';

		if (!file_exists($imagePath))
		{
			$levels = array(5, 10, 20, 35, 55, 80);
			$labels = array(
				'measure' => Text::_('PLG_JEA_DPE_GES_MEASURE'),
				'top-left' => Text::_('PLG_JEA_DPE_GES_TOP_LEFT_LABEL'),
				'bottom-left' => Text::_('PLG_JEA_DPE_GES_BOTTOM_LEFT_LABEL')
			);

			$this->generateGDImage($ges, $imagePath, 'dpe-ges.png', $levels, $labels);
		}

		return $imageURL;
	}

	private function generateGDImage ($dpeValue, $imagePath, $imageModel, $levels, $labels = array())
	{
		$currentLevel = 0;
		$imgWidth = 300;
		$imgHeiht = 260;
		$fontFile = JPATH_ROOT . '/plugins/jea/dpe/fonts/DejaVuSans.ttf';
		$fontBoldFile = JPATH_ROOT . '/plugins/jea/dpe/fonts/DejaVuSans-Bold.ttf';

		foreach ($levels as $level => $value)
		{
			if ($dpeValue <= $value)
			{
				$currentLevel = $level;
				break;
			}
		}

		if ($currentLevel == 0 && $dpeValue > $levels[count($levels) - 1])
		{
			$currentLevel = 6;
		}

		$img = @imagecreatetruecolor($imgWidth, $imgHeiht);

		if (! $img)
		{
			throw new Exception('Cannot create a GD image stream');
		}

		$white = imagecolorallocate($img, 255, 255, 255);
		$grey = imagecolorallocate($img, 200, 200, 200);
		$grey2 = imagecolorallocate($img, 40, 40, 40);
		imagefill($img, 0, 0, $white);
		$arrowImg = @imagecreatefrompng(JPATH_ROOT . '/plugins/jea/dpe/images/arrow.png');
		$imgModel = @imagecreatefrompng(JPATH_ROOT . '/plugins/jea/dpe/images/' . $imageModel);

		// Where the img model start from Y
		$destY = ceil(($imgHeiht - imagesy($imgModel)) / 2);

		$dpeY = $destY;

		if ($currentLevel == 6)
		{
			$dpeY += imagesy($imgModel) - 15;
		}
		else
		{
			/*
			 * 30 px height per level + 3px margin
			 * Adjust now y between the levels limits
			 */
			$dpeY += $currentLevel * 33;
			$start = 0;
			$end = $levels[$currentLevel];

			if (isset($levels[$currentLevel - 1]))
			{
				$start = $levels[$currentLevel - 1] + 1;
			}

			$dpeY += floor(($dpeValue - $start) * 30 / ($end - $start));
		}

		// Draw horizontal line
		imageline($img, 0, $dpeY, $imgWidth, $dpeY, $grey);

		// Draw vertical line
		imageline($img, 220, 0, 220, $imgHeiht, $grey2);

		// Copy the image model
		imagecopy($img, $imgModel, 0, $destY, 0, 0, imagesx($imgModel), imagesy($imgModel));
		$destX = $imgWidth - imagesx($arrowImg);
		$destY = $dpeY - (imagesy($arrowImg) / 2);
		imagecopy($img, $arrowImg, $destX, $destY, 0, 0, imagesx($arrowImg), imagesy($arrowImg));

		// Add the value
		imagettftext($img, 11, 0, $destX + 18, $destY + 20, $white, $fontBoldFile, $dpeValue);

		// Add the measure
		if (isset($labels['measure']))
		{
			$box = imagettfbbox(7, 0, $fontFile, $labels['measure']);
			$x = $box[4] - $box[0];
			imagettftext($img, 7, 0, $imgWidth - $x, $destY + 41, $grey2, $fontFile, $labels['measure']);
		}

		// Add text to top left
		if (isset($labels['top-left']))
		{
			imagettftext($img, 8, 0, 0, 9, $grey2, $fontFile, $labels['top-left']);
		}

		// Add text to top right
		imagettftext($img, 8, 0, 230, 9, $grey2, $fontFile, Text::_('PLG_JEA_DPE_TOP_RIGHT_LABEL'));

		// Add text to bottom left
		if (isset($labels['bottom-left']))
		{
			imagettftext($img, 8, 0, 0, $imgHeiht - 3, $grey2, $fontFile, $labels['bottom-left']);
		}

		$ret = @imagepng($img, $imagePath);

		imagedestroy($img);
		imagedestroy($arrowImg);
		imagedestroy($imgModel);

		if (!$ret)
		{
			throw new Exception('Cannot save image : ' . $imagePath);
		}
	}
}

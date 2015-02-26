<?php
/**
 * Render the CMS layout
 *
 * @package Calendarize\Hooks
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Render the CMS layout
 *
 * @author Tim Lochmüller
 * @see    News extension (Thanks Georg)
 */
class CmsLayout {

	/**
	 * Flex form data
	 *
	 * @var array
	 */
	protected $flexformData = array();

	/**
	 * Table data
	 *
	 * @var array
	 */
	protected $tableData = array();

	/**
	 * Returns information about this extension plugin
	 *
	 * @param array $params Parameters to the hook
	 *
	 * @return string Information about pi1 plugin
	 * @hook TYPO3_CONF_VARS|SC_OPTIONS|cms/layout/class.tx_cms_layout.php|list_type_Info|calendarize_calendar
	 */
	public function getExtensionSummary(array $params) {
		$relIconPath = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . ExtensionManagementUtility::siteRelPath('calendarize') . 'ext_icon.png';
		$result = '<strong><img src="' . $relIconPath . '" /> Calendarize</strong>';

		if ($params['row']['list_type'] == 'calendarize_calendar') {
			$this->flexformData = GeneralUtility::xml2array($params['row']['pi_flexform']);
			if ($this->flexformData) {
				$actions = $this->getFieldFromFlexform('switchableControllerActions', 'main');
				$parts = GeneralUtility::trimExplode(';', $actions, TRUE);
				$parts = array_map(function ($element) {
					$split = explode('->', $element);
					return ucfirst($split[1]);
				}, $parts);
				$actionKey = lcfirst(implode('', $parts));

				$this->tableData[] = array(
					LocalizationUtility::translate('mode', 'calendarize'),
					LocalizationUtility::translate('mode.' . $actionKey, 'calendarize')
				);

				$this->tableData[] = array(
					LocalizationUtility::translate('configuration', 'calendarize'),
					$this->getFieldFromFlexform('settings.configuration', 'main')
				);

				if ((bool)$this->getFieldFromFlexform('settings.hidePagination', 'main')) {
					$this->tableData[] = array(
						LocalizationUtility::translate('hide.pagination.teaser', 'calendarize'),
						'!!!'
					);
				}
				$this->addPageIdsToTable();
				$result .= $this->renderSettingsAsTable();
			}
		}

		return $result;
	}

	/**
	 * Add page IDs to the preview of the element
	 */
	protected function addPageIdsToTable() {
		$pageIdsNames = array(
			'detailPid',
			'listPid',
			'yearPid',
			'monthPid',
			'weekPid',
			'dayPid',
		);
		foreach ($pageIdsNames as $pageIdName) {
			$pageId = (int)$this->getFieldFromFlexform('settings.' . $pageIdName, 'pages');
			if ($pageId) {
				$pageRow = BackendUtility::getRecord('pages', $pageId);
				if ($pageRow) {
					$this->tableData[] = array(
						LocalizationUtility::translate($pageIdName, 'calendarize'),
						$pageRow['title'] . ' (' . $pageId . ')'
					);
				}
			}
		}
	}

	/**
	 * Render the settings as table for Web>Page module
	 * System settings are displayed in mono font
	 *
	 * @return string
	 */
	protected function renderSettingsAsTable() {
		if (!$this->tableData) {
			return '';
		}
		$content = '';
		foreach ($this->tableData as $line) {
			$content .= '<strong>' . $line[0] . '</strong>' . ' ' . $line[1] . '<br />';
		}

		return '<pre style="white-space:normal">' . $content . '</pre>';
	}

	/**
	 * Get field value from flexform configuration,
	 * including checks if flexform configuration is available
	 *
	 * @param string $key   name of the key
	 * @param string $sheet name of the sheet
	 *
	 * @return string|NULL if nothing found, value if found
	 */
	public function getFieldFromFlexform($key, $sheet = 'sDEF') {
		$flexform = $this->flexformData;
		if (isset($flexform['data'])) {
			$flexform = $flexform['data'];
			if (is_array($flexform) && is_array($flexform[$sheet]) && is_array($flexform[$sheet]['lDEF']) && is_array($flexform[$sheet]['lDEF'][$key]) && isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
			) {
				return $flexform[$sheet]['lDEF'][$key]['vDEF'];
			}
		}

		return NULL;
	}
}

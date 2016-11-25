<?php
namespace HENRIKBRAUNE\SeoBasicsPluginSitemap\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Henrik Braune <henrik@braune.org>, HENRIK BRAUNE
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use B13\SeoBasics\Controller\SitemapController;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 *
 *
 * @package seo_basics_plugin_sitemap
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Sitemap
{

    /**
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * @var DatabaseConnection
     */
    protected $dbConnection;

    public function __construct()
    {
        $this->frontendController = $GLOBALS['TSFE'];
        $this->dbConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param array $params
     * @param SitemapController $sitemap
     * @return void
     */
    public function setAdditionalUrls($params, SitemapController $sitemap)
    {

        $plugins = GeneralUtility::removeDotsFromTS($this->frontendController->tmpl->setup['plugin.']['tx_seobasicspluginsitemap.']['extensions.']);

        foreach ($plugins as $plugin => $configuration) {
            if (ExtensionManagementUtility::isLoaded($plugin)) {
                $where = !empty($configuration['where']) ? $configuration['where'] : '';

                $enableFileds = $this->frontendController->cObj->enableFields($configuration['table']);
                $where .= empty($where) ? substr($enableFileds, 4) : $enableFileds;

                $result = $this->dbConnection->exec_SELECTgetRows(
                    implode(',', $configuration['fields']),
                    $configuration['table'],
                    $where
                );

                $additionalParams = [];
                foreach ($configuration['additionalParams'] as $param) {
                    $pair = GeneralUtility::trimExplode('=', $param);
                    $additionalParams[$pair[0]] = $pair[1];
                }

                if (is_array($result)) {
                    foreach ($result as $row) {
                        $uniqueAdditionalParams = [];
                        foreach ($additionalParams as $paramName => $paramValue) {
                            $uniqueAdditionalParams[$paramName] = (substr($paramValue, 0, 1) == '$') ? $row[substr($paramValue, 1)] : $paramValue;
                        }

                        $conf = [
                            'parameter' => $configuration['detailPid'],
                            'additionalParams' => GeneralUtility::implodeArrayForUrl('', $uniqueAdditionalParams),
                            'forceAbsoluteUrl' => 1
                        ];

                        $link = $this->frontendController->cObj->typoLink_URL($conf);

                        if ($row[$configuration['fields']['tstamp']]) {
                            $lastmod = '<lastmod>' . htmlspecialchars(date('c', $row[$configuration['fields']['tstamp']])) . '</lastmod>';
                        } else {
                            $lastmod = '';
                        }

                        $params['content'] .= '
                            <url>
                                <loc>' . htmlspecialchars($link) . '</loc>' . $lastmod . '
                            </url>
                        ';
                    }
                }
            }
        }
    }
}
<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['seo_basics']['sitemap']['additionalUrlsHook'][] = \HENRIKBRAUNE\SeoBasicsPluginSitemap\Hooks\Sitemap::class . '->setAdditionalUrls';
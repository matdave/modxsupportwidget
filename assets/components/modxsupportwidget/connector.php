<?php
/**
 * Created with PhpStorm
 * User: matdave
 * Project: modxsupportwidget
 * Date: 8/3/2018
 * https://github.com/matdave
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('modxsupportwidget.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/modxsupportwidget/');
$modxsupportwidget = $modx->getService('modxsupportwidget', 'modxSupportWidget', $corePath . '/model/modxsupportwidget/', array(
    'core_path' => $corePath
));

/* handle request */
$modx->request->handleRequest(
    array(
        'processors_path' => $modxsupportwidget->getOption('processorsPath', null, $corePath . 'processors/'),
        'location' => '',
    )
);
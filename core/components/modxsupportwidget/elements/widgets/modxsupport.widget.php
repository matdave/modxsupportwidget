<?php
/**
 * Created with PhpStorm
 * User: matdave
 * Project: modxsupportwidget
 * Date: 8/3/2018
 * https://github.com/matdave
 */

class modDashboardWidgetMODXSupport extends modDashboardWidgetInterface
{
    /**
     * @return string
     */
    public function render()
    {
        $corePath = $this->modx->getOption('modxsupportwidget.core_path', null, $this->modx->getOption('core_path') . 'components/modxsupportwidget/');
        $modxsupportwidget = $this->modx->getService('modxsupportwidget', 'modxSupportWidget', $corePath . '/model/modxsupportwidget/', array(
            'core_path' => $corePath
        ));
        $this->controller->addLexiconTopic($this->widget->get('lexicon'));
        $assetsUrl = $modxsupportwidget->getOption('assetsUrl');
        $jsUrl = $modxsupportwidget->getOption('jsUrl');
        $cssUrl = $modxsupportwidget->getOption('cssUrl');
        $this->controller->addJavascript($jsUrl . 'modxsupportwidget.min.js');
        $this->controller->addCss($cssUrl . 'support.css');

        $userArray = array('id'=>$modxsupportwidget->userArray['id'], 'fullname'=>$modxsupportwidget->userArray['fullname'], 'email'=>$modxsupportwidget->userArray['email']);

        $this->controller->addHtml('<script type="text/javascript">Ext.onReady(function() {
    MODx.load({
        xtype: "modx-form-supportwidget",
        renderTo: "modx-form-supportwidget",
        connector_url: "' . $modxsupportwidget->getOption('connectorUrl') . '",
        userDetails: ' . $this->modx->toJSON($userArray) . '
    });
});</script>');
        return $this->getFileChunk($modxsupportwidget->getOption('templatesPath') . 'modxsupportwidget.tpl');
    }
}
return 'modDashboardWidgetMODXSupport';
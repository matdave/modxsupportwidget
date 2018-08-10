<?php
/**
 * Created with PhpStorm
 * User: matdave
 * Project: modxsupportwidget
 * Date: 8/3/2018
 * https://github.com/matdave
 */
require_once 'vendor/autoload.php';
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;

class modxSupportWidget
{
    public $modx = null;
    public $namespace = 'modxsupportwidget';
    public $cache = null;
    public $options = array();
    public $supportEmail = null;
    public $userArray = array();
    public $providerCache = array();
    public $pi;

    public function __construct(modX &$modx, array $options = array())
    {
        $this->modx =& $modx;
        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/modxsupportwidget/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/modxsupportwidget/');
        $this->supportEmail = $this->getOption('support_email', $options, 'help@modx.com');
        $user = $this->modx->user;
        if(!empty($user)){
            $this->userArray = $user->toArray();
            $profile = $user->getOne('Profile');
            if(!empty($profile)){
                $this->userArray = array_merge($profile->toArray(),$this->userArray);
            }
        }
        $this->getServer($this->options);
        $this->options = array_merge(array(
            'namespace' => $this->namespace,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'widgetsPath' => $corePath . 'elements/widgets/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'mgr/js/',
            'cssUrl' => $assetsUrl . 'mgr/css/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ), $options);
        $this->modx->addPackage('modxsupportwidget', $this->getOption('modelPath'));
        $this->modx->lexicon->load('modxsupportwidget:default');
    }
    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    /**
     * @param string $tpl
     * @param array $placeholders
     * @return string
     */
    public function getFileChunk($tpl,array $placeholders = array()) {
        $output = '';
        $file = $tpl;
        if (!file_exists($file)) {
            $file = $this->modx->getOption('manager_path').'templates/'.$this->modx->getOption('manager_theme',null,'default').'/'.$tpl;
        }
        if (!file_exists($file)) {
            $file = $this->modx->getOption('manager_path').'templates/default/'.$tpl;
        }
        if (file_exists($file)) {
            /** @var modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setCacheable(false);
            $tplContent = file_get_contents($file);
            $chunk->setContent($tplContent);
            $output = $chunk->process($placeholders);
        }
        return $output;
    }

    public function getClient() {
        DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_NONE);
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $dd = new DeviceDetector($userAgent);
        $dd->parse();
        if ($dd->isBot()) {
            return $this->modx->toJSON($dd->getBot());
        }else{
            return $dd->getClient('type').' '.$dd->getClient('name').' '.$dd->getClient('version').' on '.$dd->getOs('name').' '.$dd->getOs('version').' '.$dd->getDeviceName();
        }
    }

    public function checkForUpdates($package, $modx, $providerCache = array()){
        $updates = array('count' => 0);
        if ($package->get('provider') > 0 && $modx->getOption('auto_check_pkg_updates',null,false)) {
            $updateCacheKey = 'mgr/providers/updates/'.$package->get('provider').'/'.$package->get('signature');
            $updateCacheOptions = array(
                xPDO::OPT_CACHE_KEY => $modx->cacheManager->getOption('cache_packages_key', null, 'packages'),
                xPDO::OPT_CACHE_HANDLER => $modx->cacheManager->getOption('cache_packages_handler', null, $modx->cacheManager->getOption(xPDO::OPT_CACHE_HANDLER)),
            );
            $updates = $modx->cacheManager->get($updateCacheKey, $updateCacheOptions);
            if (empty($updates)) {
                /* cache providers to speed up load time */
                /** @var modTransportProvider $provider */
                if (!empty($providerCache[$package->get('provider')])) {
                    $provider =& $providerCache[$package->get('provider')];
                } else {
                    $provider = $package->getOne('Provider');
                    if ($provider) {
                        $providerCache[$provider->get('id')] = $provider;
                    }
                }
                if ($provider) {
                    $updates = $provider->latest($package->get('signature'));
                    $updates = array('count' => count($updates));
                    $modx->cacheManager->set($updateCacheKey, $updates, 1600, $updateCacheOptions);
                }
            }
        }
        return (int)$updates['count'] >= 1 ? true : false;
    }

    public function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');
        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public function getLogSize(){
        $log = $this->modx->getOption(xPDO::OPT_CACHE_PATH) . 'logs/error.log';
        $logsize = 0;
        if (file_exists($log)) {
            $logsize = $this->formatBytes(filesize($log));
        }
        return $logsize;
    }
    
    public function getPackages(){
        //Get Transport Packages
        $packs = array();
        $updates = 0;
        $c = $this->modx->newQuery('transport.modTransportPackage');
        $c->where(array('installed:IS NOT'=>null));
        $c->sortby('package_name', 'ASC');
        $c->sortby('installed', 'ASC');
        $packages = $this->modx->getCollection('transport.modTransportPackage', $c);
        if(!empty($packages)){
            foreach($packages as $p){
                $update = $this->checkForUpdates($p, $this->modx, $this->providerCache);
                $packs[$p->get('package_name')] = array(
                    'update' => $update
                    ,'package_name' => $p->get('package_name')
                    ,'version' => $p->get('version_major'). '.' . $p->get('version_minor'). '.' . $p->get('version_patch'). '-' . $p->get('release')
                    ,'installed' => $p->get('installed')
                );
            }
        }

        return array_values($packs);
    }

    public function getPackagesTable(){
        $updates = 0;
        $packs = $this->getPackages();
        $message = "<table><thead><th>Package</th><th>Version</th><th>Installed</th></thead><tbody>";
        foreach($packs as $m){
            $message .=  '<tr><td>'.$m['package_name']. ' '. ($m['update'] ? ' <strong>(update!)</strong>' : null) .'</td><td>' . $m['version']. '</td><td>' . $m['installed'].'</td></tr>';
            if($m['update']){
                $updates++;
            }
        }
        $message .="</tbody></table>";
        $message .= ($updates > 0)? "<strong>".$updates." Update(s) Available</strong>":null;

        return $message;
    }

    public function countResources(){
        $c = $this->modx->newQuery('modResource');
        return $this->modx->getCount('modResource', $c);
    }

    public function countSessions(){
        $c = $this->modx->newQuery('modSession');
        return $this->modx->getCount('modSession', $c);
    }

    public function countActions(){
        $c = $this->modx->newQuery('modManagerLog');
        return $this->modx->getCount('modManagerLog', $c);
    }

    public function countVersionX(){
        $path = $this->modx->getOption('versionx.core_path', null, MODX_CORE_PATH . 'components/versionx/');
        $versionx = $this->modx->getService('versionx', 'VersionX', $path . 'model/');
        if (!$versionx) {
            return 'N/A';
        }
        $return = '';
        $c = $this->modx->newQuery('vxChunk');
        $return  .= 'vxChunk = '. $this->modx->getCount('vxChunk', $c).'; ';
        $c = $this->modx->newQuery('vxPlugin');
        $return  .= 'vxPlugin = '. $this->modx->getCount('vxPlugin', $c).'; ';
        $c = $this->modx->newQuery('vxResource');
        $return  .= 'vxResource = '. $this->modx->getCount('vxResource', $c).'; ';
        $c = $this->modx->newQuery('vxSnippet');
        $return  .= 'vxSnippet = '. $this->modx->getCount('vxSnippet', $c).'; ';
        $c = $this->modx->newQuery('vxTemplate');
        $return  .= 'vxTemplate = '. $this->modx->getCount('vxTemplate', $c).'; ';
        $c = $this->modx->newQuery('vxTemplateVar');
        $return  .= 'vxTemplateVar = '. $this->modx->getCount('vxTemplateVar', $c).'; ';
        return $return;
    }

    public function parsePHPModules() {
        ob_start();
        phpinfo(INFO_MODULES);
        $s = ob_get_contents();
        ob_end_clean();
        $s = strip_tags($s,'<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
        $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
        $vModules = array();
        for ($i=1;$i<count($vTmp);$i++) {
            if (preg_match('/<h2>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
                $vName = trim($vMat[1]);
                $vTmp2 = explode("\n",$vTmp[$i+1]);
                foreach ($vTmp2 AS $vOne) {
                    $vPat = '<info>([^<]+)<\/info>';
                    $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
                    $vPat2 = "/$vPat\s*$vPat/";
                    if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
                        $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
                    } elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
                        $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
                    }
                }
            }
        }
        return $vModules;
    }


    public function getPhpInfo($type = -1) {
        ob_start();
        phpinfo($type);
        $pi = preg_replace(
            array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
                .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#", '#<tr>#', '#</tr>#'),
            array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
            ob_get_clean());
        $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
        unset($sections[0]);
        $pi = array();
        foreach($sections as $section){
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all(
                '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                $section, $askapache, PREG_SET_ORDER);
            foreach($askapache as $m)
                $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
        }
        return $pi;
    }


    public function getServer(array $scriptProperties = array()) {
        $pi = $this->getPhpInfo(INFO_GENERAL);
        $m = $this->parsePHPModules();
        $dbtype_mysql = $this->modx->config['dbtype'] == 'mysql';
        $dbtype_sqlsrv = $this->modx->config['dbtype'] == 'sqlsrv';
        if ($dbtype_mysql && !empty($m['mysql'])) $pi = array_merge($pi,array('mysql' => $m['mysql']));
        if ($dbtype_mysql && !empty($m['mysqlnd'])) $pi = array_merge($pi,array('pdo' => $m['mysqlnd']));
        if ($dbtype_sqlsrv && !empty($m['sqlsrv'])) $pi = array_merge($pi,array('sqlsrv' => $m['sqlsrv']));
        if (!empty($m['PDO'])) $pi = array_merge($pi,array('pdo' => $m['PDO']));
        if ($dbtype_mysql && !empty($m['pdo_mysql'])) $pi = array_merge($pi,array('pdo_mysql' => $m['pdo_mysql']));
        if ($dbtype_sqlsrv && !empty($m['pdo_sqlsrv'])) $pi = array_merge($pi,array('pdo_sqlsrv' => $m['pdo_sqlsrv']));
        if (!empty($m['zip'])) $pi = array_merge($pi,array('zip' => $m['zip']));
        $this->pi = array_merge($pi,$this->getPhpInfo(INFO_CONFIGURATION));
        return array(
            'pi' => $this->pi,
        );
    }
}
<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload2bb67486b4e64f19803bc5289fdaa902($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'tuleap\\velocity\\plugin\\plugindescriptor' => '/Velocity/Plugin/PluginDescriptor.php',
            'tuleap\\velocity\\plugin\\plugininfo' => '/Velocity/Plugin/PluginInfo.php',
            'tuleap\\velocity\\semantic\\semanticvelocity' => '/Velocity/Semantic/SemanticVelocity.php',
            'velocityplugin' => '/velocityPlugin.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload2bb67486b4e64f19803bc5289fdaa902');
// @codeCoverageIgnoreEnd
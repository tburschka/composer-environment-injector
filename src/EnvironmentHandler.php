<?php

namespace ComposerEnvironmentInjector;

use Composer\Script\Event;

class EnvironmentHandler
{
    public static function injectEnvironment(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extras['environment-injector'])) {
            throw new \InvalidArgumentException('The environment handler needs to be configured through the extra.environment-injector setting.');
        }

        $configs = $extras['environment-injector'];

        if (!is_array($configs)) {
            throw new \InvalidArgumentException('The extra.environment-injector setting must be an array or a configuration object.');
        }

        if (array_keys($configs) !== range(0, count($configs) - 1)) {
            $configs = array($configs);
        }

        $processor = new Processor($event->getIO());

        foreach ($configs as $config) {
            if (!is_array($config)) {
                throw new \InvalidArgumentException('The extra.environment-injector setting must be an array of configuration objects.');
            }
            $processor->processFile($config);
        }
    }
}
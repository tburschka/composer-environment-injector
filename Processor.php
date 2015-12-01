<?php

namespace ComposerEnvironmentInjector;

use Composer\IO\IOInterface;
use Symfony\Component\Yaml;

class Processor
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string
     */
    private $prefix = "ENV_";

    /**
     * @var bool
     */
    private $overwrite = false;

    /**
     * @var string
     */
    private $file;

    /**
     * @var Yaml\Parser
     */
    private $yamlParser;


    /**
     * Processor constructor.
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @param $config
     */
    public function processFile($config)
    {
        $this->processConfig($config);
        $this->io->write(sprintf('<info>Inject environment from "%s" file</info>', $this->file));
        $this->putEnvironment();
    }

    /**
     * Processes the config
     *
     * @param array $config
     */
    private function processConfig(array $config)
    {
        if (empty($config['env'])) {
            throw new \InvalidArgumentException(
                'The extra.environment-injector.env setting is required to use this environment handler.'
            );
        } elseif (getenv($config['env'])) {
            $config['env'] = getenv($config['env']);
        } // no else

        $this->file = str_replace('{env}', $config['env'], $config['source']);
        if (empty($config['source'])) {
            throw new \InvalidArgumentException(
                'The extra.environment-injector.file setting is required to use this environment handler.'
            );
        } elseif (!is_file($this->file)) {
            throw new \InvalidArgumentException(
                sprintf('The environment file "%s" does not exist. Validate your settings.', $this->file)
            );
        } // no else

        if ($config['prefix']) {
            $this->prefix = $config['prefix'];
        } // no else

        if ($config['overwrite']) {
            $this->overwrite = (bool) $config['overwrite'];
        } // no else
    }

    /**
     * Load a yaml file
     *
     * @param $file
     * @return array
     */
    private function loadYaml($file)
    {
        $yaml = file_get_contents($file);
        try {
            $content = $this->getYamlParser()->parse($yaml, true);
        } catch (\Exception $e) {
            throw new \UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
        return $content;
    }

    /**
     * Get the yaml parser
     *
     * @return Yaml\Parser
     */
    private function getYamlParser()
    {
        if (!$this->yamlParser) {
            $this->yamlParser = new Yaml\Parser();
        }
        return $this->yamlParser;
    }

    /**
     * Encode environment parameter
     *
     * @param $parameterName
     * @param $parameterValue
     * @param bool|true $encodeValue
     * @return string
     */
    private function encodeEnvironment($parameterName, $parameterValue, $encodeValue = true)
    {
        $encodedEnv = $this->prefix . strtoupper($parameterName);
        if ($encodeValue) {
            $encodedEnv .= '="' . addslashes($parameterValue) . '"';
        }
        return $encodedEnv;
    }

    /**
     * Put the environment
     */
    private function putEnvironment()
    {
        $parameters = $this->loadYaml($this->file);
        foreach ($parameters['parameters'] as $parameterName => $parameterValue) {
            $encodedEnv = $this->encodeEnvironment($parameterName, $parameterValue, false);
            if (!$this->overwrite && getenv($encodedEnv)) {
                continue;
            }
            putenv($this->encodeEnvironment($parameterName, $parameterValue, true));
        }
    }

}
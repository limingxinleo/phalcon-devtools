<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (https://www.phalconphp.com)      |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  |          limingxinleo <715557344@qq.com>                     |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Builder;

use Phalcon\Utils;
use SplFileObject;

/**
 * Logic Class
 *
 * Builder to generate logic
 *
 * @package Phalcon\Builder
 */
class Logic extends Component
{
    /**
     * Create Builder object
     *
     * @param array $options Builder options
     * @throws BuilderException
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['name'])) {
            throw new BuilderException('Please specify the Logic name.');
        }

        if (!isset($options['force'])) {
            $options['force'] = false;
        }

        parent::__construct($options);
    }

    /**
     * @return string
     * @throws \Phalcon\Builder\BuilderException
     */
    public function build()
    {
        // DONE(limx): 加载配置
        $config = $this->getConfig();

        if ($this->options->contains('directory')) {
            $this->path->setRootPath($this->options->get('directory'));
        }

        // DONE(limx):如果有子目录，则记录子目录
        $subdir = '';
        if ($this->options->contains('subdir')) {
            $subdir = $this->options->get('subdir');
        }
        if (!empty($subdir)) {
            $subdir = Utils::camelize($subdir);
        }

        $namespace = '';
        if ($this->options->contains('namespace') && $this->checkNamespace($this->options->get('namespace'))) {
            $namespace = 'namespace ' . $this->options->get('namespace') . ';' . PHP_EOL . PHP_EOL;
        }
        // DONE(limx): 如果设置了命名空间，默认使用命名空间
        if (empty($namespace) && !empty($config->logic->namespace)) {
            $namespace = 'namespace ' . $config->logic->namespace . ';' . PHP_EOL . PHP_EOL;
            // DONE(limx):如果有子目录，则重写命名空间
            if (!empty($subdir)) {
                $namespace = 'namespace ' . $config->logic->namespace . '\\' . $subdir . ';' . PHP_EOL . PHP_EOL;
            }
        }

        $baseClass = $this->options->get('baseClass');
        // DONE(limx): 如果在配置中设置过基类，默认使用基类，如果没有设置，则不使用
        if (!$baseClass) {
            if ($config->logic->baseClass) {
                $baseClass = $config->logic->baseClass;
                // DONE(limx):如果有子目录，则use控制器基类
                if (!empty($subdir)) {
                    $uses[] = sprintf("use App\\Logics\\%s;", $baseClass);
                }
            }
        }

        if (!$logicsDir = $this->options->get('logicsDir')) {
            $config = $this->getConfig();
            if (!isset($config->application->logicsDir)) {
                throw new BuilderException('Please specify a logics directory.');
            }

            $logicsDir = $config->application->logicsDir;
        }

        if (!$this->options->contains('name')) {
            throw new BuilderException('The controller name is required.');
        }

        $name = str_replace(' ', '_', $this->options->get('name'));

        $className = Utils::camelize($name);

        // Oops! We are in APP_PATH and try to get logicsDir from outside from project dir
        if ($this->isConsole() && substr($logicsDir, 0, 3) === '../') {
            $logicsDir = ltrim($logicsDir, './');
        }

        $logicPath = rtrim($logicsDir, '\\/') . DIRECTORY_SEPARATOR . $className . ".php";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($subdir)) {
            $tempDir = rtrim($logicsDir, '\\/') . DIRECTORY_SEPARATOR . $subdir;
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $logicPath = $tempDir . DIRECTORY_SEPARATOR . $className . ".php";
        }

        $useDefinition = "";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($uses)) {
            $useDefinition = join("\n", $uses) . PHP_EOL . PHP_EOL;
        }

        $code = "<?php\n\n" . $namespace . $useDefinition . "class " . $className . " extends " . $baseClass . "\n{\n\t\n}\n\n";
        $code = str_replace("\t", "    ", $code);

        if (file_exists($logicPath) && !$this->options->contains('force')) {
            throw new BuilderException(sprintf('The Logic %s already exists.', $name));
        }

        $logic = new SplFileObject($logicPath, 'w');

        if (!$logic->fwrite($code)) {
            throw new BuilderException(
                sprintf('Unable to write to %s. Check write-access of a file.', $logic->getRealPath())
            );
        }

        if ($this->isConsole()) {
            $this->notifySuccess(
                sprintf('logic "%s" was successfully created.', $name)
            );
            echo $logic->getRealPath(), PHP_EOL;
        }

        return $className . '.php';
    }
}

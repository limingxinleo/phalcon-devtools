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
 * Job Class
 *
 * Builder to generate Job
 *
 * @package Phalcon\Builder
 */
class Job extends Component
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
            throw new BuilderException('Please specify the Job name.');
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

        $namespace = '';
        $namespaceClass = '';
        if ($this->options->contains('namespace') && $this->checkNamespace($this->options->get('namespace'))) {
            $namespaceClass = $this->options->get('namespace');
        }
        if (empty($namespaceClass) && !empty($config->job->namespace)) {
            $namespaceClass = $config->job->namespace;
        }
        // 如果设置了子目录，则在命名空间后面跟上子目录。
        if (!empty($subdir) && $namespaceClass) {
            $namespaceClass = $namespaceClass . '\\' . Utils::camelizeWithSlash($subdir, '\\');
        }

        if ($namespaceClass) {
            $namespace = 'namespace ' . $namespaceClass . ';' . PHP_EOL . PHP_EOL;
        }

        $baseClass = $this->options->get('baseClass');
        // DONE(limx): 如果在配置中设置过基类，默认使用基类，如果没有设置，则不使用
        if (!$baseClass) {
            if (isset($config->job->baseClass)) {
                $baseClass = $config->job->baseClass;
                // DONE(limx):如果有子目录，则use控制器基类
                if (!empty($subdir)) {
                    $uses[] = sprintf("use App\\Jobs\\%s;", $baseClass);
                }
            }
        }

        if (!$jobsDir = $this->options->get('jobsDir')) {
            $config = $this->getConfig();
            if (!isset($config->application->jobsDir)) {
                throw new BuilderException('Please specify a jobs directory.');
            }

            $jobsDir = $config->application->jobsDir;
        }

        if (!$this->options->contains('name')) {
            throw new BuilderException('The controller name is required.');
        }

        $name = str_replace(' ', '_', $this->options->get('name'));

        $className = Utils::camelize($name);

        // Oops! We are in APP_PATH and try to get jobsDir from outside from project dir
        if ($this->isConsole() && substr($jobsDir, 0, 3) === '../') {
            $jobsDir = ltrim($jobsDir, './');
        }

        $logicPath = rtrim($jobsDir, '\\/') . DIRECTORY_SEPARATOR . $className . ".php";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($subdir)) {
            $tempDir = rtrim($jobsDir, '\\/') . DIRECTORY_SEPARATOR . $subdir;
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $logicPath = $tempDir . DIRECTORY_SEPARATOR . $className . ".php";
        }

        $useDefinition = "";
        // DONE(limx):如果有子目录，则修改文件目录
        $uses[] = 'use Xin\Swoole\Queue\JobInterface;';
        if (!empty($uses)) {
            $useDefinition = join("\n", $uses) . PHP_EOL . PHP_EOL;
        }
        $extends = '';
        if (!empty($baseClass)) {
            $extends = " extends " . $baseClass;
        }
        $code = "<?php\n\n" . $namespace . $useDefinition . "class " . $className . $extends . " implements JobInterface\n{\n\t\n}\n\n";
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

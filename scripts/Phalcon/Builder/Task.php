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
 * Task Class
 *
 * Builder to generate task
 *
 * @package Phalcon\Builder
 */
class Task extends Component
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
            throw new BuilderException('Please specify the task name.');
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
            $subdir = Utils::camelizeWithSlash($subdir);
        }

        $namespace = '';
        $namespaceClass = '';
        if ($this->options->contains('namespace') && $this->checkNamespace($this->options->get('namespace'))) {
            $namespaceClass = $this->options->get('namespace');
        }
        if (empty($namespaceClass) && !empty($config->task->namespace)) {
            $namespaceClass = $config->task->namespace;
        }
        // 如果设置了子目录，则在命名空间后面跟上子目录。
        if (!empty($subdir) && $namespaceClass) {
            $namespaceClass = $namespaceClass . '\\' . Utils::camelizeWithSlash($subdir, '\\');
        }

        if ($namespaceClass) {
            $namespace = 'namespace ' . $namespaceClass . ';' . PHP_EOL . PHP_EOL;
        }

        $baseClass = $this->options->get('baseClass');
        // DONE(limx): 如果在配置中设置过基类，默认使用基类，如果没有设置，则使用 \Phalcon\Cli\Task
        if (!$baseClass) {
            if (empty($config->task->baseClass)) {
                $baseClass = '\Phalcon\Cli\Task';
            } else {
                $baseClass = $config->task->baseClass;
                // DONE(limx):如果有子目录，则use控制器基类
                if (!empty($subdir)) {
                    $uses[] = sprintf("use App\\Tasks\\%s;", $baseClass);
                }
            }
        }

        if (!$tasksDir = $this->options->get('tasksDir')) {
            $config = $this->getConfig();
            if (!isset($config->application->tasksDir)) {
                throw new BuilderException('Please specify a tasks directory.');
            }

            $tasksDir = $config->application->tasksDir;
        }

        if (!$this->options->contains('name')) {
            throw new BuilderException('The controller name is required.');
        }

        $name = str_replace(' ', '_', $this->options->get('name'));

        $className = Utils::camelize($name);

        // Oops! We are in APP_PATH and try to get tasksDir from outside from project dir
        if ($this->isConsole() && substr($tasksDir, 0, 3) === '../') {
            $tasksDir = ltrim($tasksDir, './');
        }

        $taskPath = rtrim($tasksDir, '\\/') . DIRECTORY_SEPARATOR . $className . "Task.php";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($subdir)) {
            $tempDir = rtrim($tasksDir, '\\/') . DIRECTORY_SEPARATOR . $subdir;
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $taskPath = $tempDir . DIRECTORY_SEPARATOR . $className . "Task.php";
        }

        $useDefinition = "";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($uses)) {
            $useDefinition = join("\n", $uses) . PHP_EOL . PHP_EOL;
        }

        $code = "<?php\n\n" . $namespace . $useDefinition . "class " . $className . "Task extends " . $baseClass . "\n{\n\n\tpublic function mainAction()\n\t{\n\n\t}\n\n}\n\n";
        $code = str_replace("\t", "    ", $code);

        if (file_exists($taskPath) && !$this->options->contains('force')) {
            throw new BuilderException(sprintf('The Task %s already exists.', $name));
        }

        $task = new SplFileObject($taskPath, 'w');

        if (!$task->fwrite($code)) {
            throw new BuilderException(
                sprintf('Unable to write to %s. Check write-access of a file.', $task->getRealPath())
            );
        }

        if ($this->isConsole()) {
            $this->notifySuccess(
                sprintf('Task "%s" was successfully created.', $name)
            );
            echo $task->getRealPath(), PHP_EOL;
        }

        return $className . 'Task.php';
    }
}

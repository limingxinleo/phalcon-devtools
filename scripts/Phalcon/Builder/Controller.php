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
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Builder;

use Phalcon\Utils;
use SplFileObject;

/**
 * Controller Class
 *
 * Builder to generate controller
 *
 * @package Phalcon\Builder
 */
class Controller extends Component
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
            throw new BuilderException('Please specify the controller name.');
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
        // use列表
        $uses = [];
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
        if (empty($namespaceClass) && !empty($config->controller->namespace)) {
            $namespaceClass = $config->controller->namespace;
        }
        // 如果设置了子目录，则在命名空间后面跟上子目录。
        if (!empty($subdir) && $namespaceClass) {
            $namespaceClass = $namespaceClass . '\\' . Utils::camelizeWithSlash($subdir, '\\');
        }

        if ($namespaceClass) {
            $namespace = 'namespace ' . $namespaceClass . ';' . PHP_EOL . PHP_EOL;
        }


        $baseClass = $this->options->get('baseClass');
        // DONE(limx): 如果在配置中设置过基类，默认使用基类，如果没有设置，则使用 \Phalcon\Mvc\Controller
        if (!$baseClass) {
            if (empty($config->controller->baseClass)) {
                $baseClass = '\Phalcon\Mvc\Controller';
            } else {
                $baseClass = $config->controller->baseClass;
                // DONE(limx):如果有子目录，则use控制器基类
                if (!empty($subdir)) {
                    $uses[] = sprintf("use App\\Controllers\\%s;", $baseClass);
                }
            }
        }

        if (!$controllersDir = $this->options->get('controllersDir')) {
            $config = $this->getConfig();
            if (empty($config->path('application.controllersDir'))) {
                throw new BuilderException('Please specify a controller directory.');
            }

            $controllersDir = $config->path('application.controllersDir');
        }

        if (!$this->options->contains('name')) {
            throw new BuilderException('The controller name is required.');
        }

        $name = str_replace(' ', '_', $this->options->get('name'));

        $className = Utils::camelize($name);

        // Oops! We are in APP_PATH and try to get controllersDir from outside from project dir
        if ($this->isConsole() && substr($controllersDir, 0, 3) === '../') {
            $controllersDir = ltrim($controllersDir, './');
        }

        $controllerPath = rtrim($controllersDir, '\\/') . DIRECTORY_SEPARATOR . $className . "Controller.php";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($subdir)) {
            $tempDir = rtrim($controllersDir, '\\/') . DIRECTORY_SEPARATOR . $subdir;
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $controllerPath = $tempDir . DIRECTORY_SEPARATOR . $className . "Controller.php";
        }
        $useDefinition = "";
        // DONE(limx):如果有子目录，则修改文件目录
        if (!empty($uses)) {
            $useDefinition = join("\n", $uses) . PHP_EOL . PHP_EOL;
        }

        $code = "<?php\n\n" . $namespace . $useDefinition . "class " . $className . "Controller extends " . $baseClass . "\n{\n\n\tpublic function indexAction()\n\t{\n\n\t}\n\n}\n\n";
        $code = str_replace("\t", "    ", $code);

        if (file_exists($controllerPath) && !$this->options->contains('force')) {
            throw new BuilderException(sprintf('The Controller %s already exists.', $name));
        }

        $controller = new SplFileObject($controllerPath, 'w');

        if (!$controller->fwrite($code)) {
            throw new BuilderException(
                sprintf('Unable to write to %s. Check write-access of a file.', $controller->getRealPath())
            );
        }

        if ($this->isConsole()) {
            $this->notifySuccess(
                sprintf('Controller "%s" was successfully created.', $name)
            );
            echo $controller->getRealPath(), PHP_EOL;
        }

        return $className . 'Controller.php';
    }
}

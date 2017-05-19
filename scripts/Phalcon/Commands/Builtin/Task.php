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
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Commands\Builtin;

use Phalcon\Builder;
use Phalcon\Script\Color;
use Phalcon\Commands\Command;
use Phalcon\Builder\Task as TaskBuilder;

/**
 * Controller Command
 *
 * Create a handler for the command line.
 *
 * @package Phalcon\Commands\Builtin
 */
class Task extends Command
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getPossibleParams()
    {
        return [
            'name=s' => 'Task name',
            'namespace=s' => "Task's namespace [option]",
            'directory=s' => 'Base path on which project is located [optional]',
            'output=s' => 'Directory where the Task should be created [optional]',
            'base-class=s' => 'Base class to be inherited by the Task [optional]',
            'force' => 'Force to rewrite Task [optional]',
            'help' => 'Shows this help [optional]',
            'subdir=s' => '文件子目录',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $parameters
     * @return mixed
     */
    public function run(array $parameters)
    {
        $taskName = $this->getOption(['name', 1]);

        $taskBuilder = new TaskBuilder([
            'name' => $taskName,
            'directory' => $this->getOption('directory'),
            'controllersDir' => $this->getOption('output'),
            'namespace' => $this->getOption('namespace'),
            'baseClass' => $this->getOption('base-class'),
            'subdir' => $this->getOption('subdir'),
            'force' => $this->isReceivedOption('force')
        ]);

        return $taskBuilder->build();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getCommands()
    {
        return ['task', 'create-task'];
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function getHelp()
    {
        print Color::head('Help:') . PHP_EOL;
        print Color::colorize('  Creates a task') . PHP_EOL . PHP_EOL;

        print Color::head('Usage:') . PHP_EOL;
        print Color::colorize('  task [name] [directory]', Color::FG_GREEN) . PHP_EOL . PHP_EOL;

        print Color::head('Arguments:') . PHP_EOL;
        print Color::colorize('  help', Color::FG_GREEN);
        print Color::colorize("\tShows this help text") . PHP_EOL . PHP_EOL;

        $this->printParameters($this->getPossibleParams());
    }

    /**
     * {@inheritdoc}
     *
     * @return integer
     */
    public function getRequiredParams()
    {
        return 1;
    }
}

<?php

/*
 * This file is part of the memio/spec-gen package.
 *
 * (c) Loïc Faugeron <faugeron.loic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Memio\SpecGen;

use Memio\SpecGen\GenerateConstructor\GeneratedConstructor;
use Memio\SpecGen\GenerateMethod\GeneratedMethod;
use PhpSpec\Extension;
use PhpSpec\ServiceContainer;

/**
 * Adding to phpspec's Dependency Injection Container Memio SpecGen's services.
 *
 * Here we over write phpspec's generators by ours.
 */
class MemioSpecGenExtension implements Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(ServiceContainer $container, array $params)
    {
        $this->defineupSharedServices($container);
        $this->defineupCodeEditor($container);
        $this->defineupGenerateConstructorHandler($container);
        $this->defineupGenerateMethodHandler($container);
        $this->defineupGenerators($container);
    }

    /**
     * @param ServiceContainer $container
     */
    private function defineupSharedServices(ServiceContainer $container)
    {
        $container->define('redaktilo.editor', function () {
            return \Gnugat\Redaktilo\EditorFactory::createEditor();
        });
        $container->define('memio.pretty_printer', function () {
            return \Memio\Memio\Config\Build::prettyPrinter();
        });
        $container->define('memio_spec_gen.event_dispatcher', function (ServiceContainer $container) {
            return new \Symfony\Component\EventDispatcher\EventDispatcher();
        });
        $container->define('memio_spec_gen.command_bus', function (ServiceContainer $container) {
            return new \Memio\SpecGen\CommandBus\CommandBus();
        });
        $container->define('memio_spec_gen.variable_argument_marshaller', function (ServiceContainer $container) {
            return new \Memio\SpecGen\Marshaller\VariableArgumentMarshaller(
                new \Memio\SpecGen\Marshaller\Service\NameGuesser(),
                new \Memio\SpecGen\Marshaller\Service\TypeGuesser()
            );
        });
    }

    /**
     * @param ServiceContainer $container
     */
    private function defineupCodeEditor(ServiceContainer $container)
    {
        $container->define('memio_spec_gen.code_editor', function (ServiceContainer $container) {
            $editor = $container->get('redaktilo.editor');
            $prettyPrinter = $container->get('memio.pretty_printer');

            $insertConstructorHandler = new \Memio\SpecGen\CodeEditor\InsertConstructorHandler($editor, $prettyPrinter);
            $insertMethodHandler = new \Memio\SpecGen\CodeEditor\InsertMethodHandler($editor, $prettyPrinter);
            $insertUseStatementHandler = new \Memio\SpecGen\CodeEditor\InsertUseStatementHandler(
                $editor,
                $prettyPrinter
            );
            $insertUseStatementsHandler = new \Memio\SpecGen\CodeEditor\InsertUseStatementsHandler(
                $editor,
                $insertUseStatementHandler
            );
            $insertPropertyHandler = new \Memio\SpecGen\CodeEditor\InsertPropertyHandler(
                $editor,
                $prettyPrinter
            );
            $insertPropertiesHandler = new \Memio\SpecGen\CodeEditor\InsertPropertiesHandler(
                $insertPropertyHandler
            );

            $commandBus = new \Memio\SpecGen\CommandBus\CommandBus();
            $commandBus->addCommandHandler($insertConstructorHandler);
            $commandBus->addCommandHandler($insertMethodHandler);
            $commandBus->addCommandHandler($insertUseStatementHandler);
            $commandBus->addCommandHandler($insertUseStatementsHandler);
            $commandBus->addCommandHandler($insertPropertyHandler);
            $commandBus->addCommandHandler($insertPropertiesHandler);

            return new \Memio\SpecGen\CodeEditor\CodeEditor($commandBus, $editor);
        });
    }

    /**
     * @param ServiceContainer $container
     */
    private function defineupGenerateConstructorHandler(ServiceContainer $container)
    {
        $eventDispatcher = $container->get('memio_spec_gen.event_dispatcher');
        $commandBus = $container->get('memio_spec_gen.command_bus');

        $insertGeneratedConstructorListener = new \Memio\SpecGen\GenerateConstructor\InsertGeneratedConstructorListener(
            $container->get('memio_spec_gen.code_editor'),
            $container->get('memio.pretty_printer')
        );
        $logGeneratedConstructorListener = new \Memio\SpecGen\GenerateConstructor\LogGeneratedConstructorListener(
            $container->get('console.io')
        );
        $eventDispatcher->addListener(GeneratedConstructor::EVENT_NAME, array($insertGeneratedConstructorListener, 'onGeneratedConstructor'));
        $eventDispatcher->addListener(GeneratedConstructor::EVENT_NAME, array($logGeneratedConstructorListener, 'onGeneratedConstructor'));

        $generateConstructorHandler = new \Memio\SpecGen\GenerateConstructor\GenerateConstructorHandler(
            $container->get('memio_spec_gen.event_dispatcher'),
            $container->get('memio_spec_gen.variable_argument_marshaller')
        );
        $commandBus->addCommandHandler($generateConstructorHandler);
    }

    /**
     * @param ServiceContainer $container
     */
    private function defineupGenerateMethodHandler(ServiceContainer $container)
    {
        $eventDispatcher = $container->get('memio_spec_gen.event_dispatcher');
        $commandBus = $container->get('memio_spec_gen.command_bus');

        $insertGeneratedMethodListener = new \Memio\SpecGen\GenerateMethod\InsertGeneratedMethodListener(
            $container->get('memio_spec_gen.code_editor'),
            $container->get('memio.pretty_printer')
        );
        $logGeneratedMethodListener = new \Memio\SpecGen\GenerateMethod\LogGeneratedMethodListener(
            $container->get('console.io')
        );
        $eventDispatcher->addListener(GeneratedMethod::EVENT_NAME, array($insertGeneratedMethodListener, 'onGeneratedMethod'));
        $eventDispatcher->addListener(GeneratedMethod::EVENT_NAME, array($logGeneratedMethodListener, 'onGeneratedMethod'));

        $generateMethodHandler = new \Memio\SpecGen\GenerateMethod\GenerateMethodHandler(
            $container->get('memio_spec_gen.event_dispatcher'),
            $container->get('memio_spec_gen.variable_argument_marshaller')
        );
        $commandBus->addCommandHandler($generateMethodHandler);
    }

    /**
     * @param ServiceContainer $container
     */
    private function defineupGenerators(ServiceContainer $container)
    {
        $container->define('code_generator.generators.method', function (ServiceContainer $container) {
            return new \Memio\SpecGen\MethodGenerator($container->get('memio_spec_gen.command_bus'));
        });
        $container->define('code_generator.generators.constructor', function (ServiceContainer $container) {
            return new \Memio\SpecGen\ConstructorGenerator($container->get('memio_spec_gen.command_bus'));
        });
    }
}

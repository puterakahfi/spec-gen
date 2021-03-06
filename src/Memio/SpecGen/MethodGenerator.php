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

use Memio\SpecGen\CommandBus\CommandBus;
use Memio\SpecGen\GenerateMethod\GenerateMethod;
use PhpSpec\CodeGenerator\Generator\Generator;
use PhpSpec\Locator\Resource;

/**
 * When phpspec finds an undefined method in a specification, it calls this generator.
 */
class MethodGenerator implements Generator
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Resource $resource, $generation, array $data)
    {
        return 'method' === $generation;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(Resource $resource, array $data = array())
    {
        $generateMethod = new GenerateMethod(
            $resource->getSrcFilename(),
            $resource->getSrcClassName(),
            $data['name'],
            $data['arguments']
        );
        $this->commandBus->handle($generateMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }
}

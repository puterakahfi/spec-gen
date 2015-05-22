<?php

/*
 * This file is part of the memio/spec-gen package.
 *
 * (c) Loïc Chardonnet <loic.chardonnet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Memio\SpecGen\CodeEditor;

use Gnugat\Redaktilo\Editor;
use Memio\PrettyPrinter\PrettyPrinter;
use Memio\SpecGen\CommandBus\Command;
use Memio\SpecGen\CommandBus\CommandHandler;

class InsertMethodHandler implements CommandHandler
{
    const CLASS_ENDING = '/^}$/';

    /**
     * @var Editor
     */
    private $editor;

    /**
     * @var PrettyPrinter
     */
    private $prettyPrinter;

    /**
     * @param Editor        $editor
     * @param PrettyPrinter $prettyPrinter
     */
    public function __construct(Editor $editor, PrettyPrinter $prettyPrinter)
    {
        $this->editor = $editor;
        $this->prettyPrinter = $prettyPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Command $command)
    {
        return $command instanceof InsertMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Command $command)
    {
        $methodPattern = '/^    public function '.$command->method->getName().'\(/';
        if ($this->editor->hasBelow($command->file, $methodPattern, 0)) {
            return;
        }
        $this->editor->jumpBelow($command->file, self::CLASS_ENDING, 0);
        $command->file->decrementCurrentLineNumber(1);
        $line = trim($command->file->getLine());
        if ('{' !== $line && '' !== $line) {
            $this->editor->insertBelow($command->file, '');
        }
        $generatedCode = $this->prettyPrinter->generateCode($command->method);
        $this->editor->insertBelow($command->file, $generatedCode);
    }
}

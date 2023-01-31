<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

use LogicException;
use PhpParser\Node as ParserNode;

final class ReturnTypeMap implements CallReturnTypeOverrideInterface
{
    private array $map = [];

    public function addMapping(string $argumentName, string|ParserNode\Name\FullyQualified $returnTypeMapping): void
    {
        if (array_key_exists($argumentName, $this->map)) {
            throw new LogicException("Return type for argument '{$argumentName}' already specified");
        }
        $this->map[$argumentName] = $returnTypeMapping;
    }

    public function getMappingForArgument(string $argumentName): null|string|ParserNode\Name\FullyQualified
    {
        return $this->map[$argumentName] ?? null;
    }

    public static function merge(self ...$maps): self
    {
        $result = new self();

        foreach ($maps as $map) {
            foreach ($map->map as $argumentName => $returnTypeMapping) {
                $result->map[$argumentName] = $returnTypeMapping;
            }
        }

        return $result;
    }
}

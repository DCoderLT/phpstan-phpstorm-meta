<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

class MethodCallTypeOverride
{
    public function __construct(
        public readonly string $classlikeName,
        public readonly string $methodName,
        public readonly int $argumentOffset,
        public readonly CallReturnTypeOverrideInterface $returnType,
    ) {
    }
}

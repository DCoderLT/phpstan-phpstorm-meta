<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

class FunctionCallTypeOverride
{
    public function __construct(
        public readonly string $functionName,
        public readonly int $argumentOffset,
        public readonly CallReturnTypeOverrideInterface $returnType,
    ) {
    }
}

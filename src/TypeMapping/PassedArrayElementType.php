<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

final class PassedArrayElementType implements CallReturnTypeOverrideInterface
{
    public function __construct(
        public readonly int $argumentOffset
    ) {}
}

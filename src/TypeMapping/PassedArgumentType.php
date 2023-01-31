<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

final class PassedArgumentType implements CallReturnTypeOverrideInterface
{
    public function __construct(
        public readonly int $argumentOffset
    ) {}
}

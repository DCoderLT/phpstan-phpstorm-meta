<?php

declare(strict_types=1);

namespace PhpStormMetaStan\TypeMapping;

use LogicException;

final class CallReturnOverrideCollection
{
    /**
     * @var MethodCallTypeOverride|FunctionCallTypeOverride[]
     */
    private array $overridesByFqn = [];

    public function addMethodCallOverride(MethodCallTypeOverride $override): void
    {
        $fqn = "{$override->classlikeName}::{$override->methodName}";

        $key = strtolower($fqn);

        if (array_key_exists($key, $this->overridesByFqn)) {
            throw new LogicException("An override for method '$fqn' has already been defined");
        }

        $this->overridesByFqn[$key] = $override;
    }

    public function addFunctionCallOverride(FunctionCallTypeOverride $override): void
    {
        $fqn = $override->functionName;

        $key = strtolower($fqn);

        if (array_key_exists($key, $this->overridesByFqn)) {
            throw new LogicException("An override for function '$fqn' has already been defined");
        }

        $this->overridesByFqn[$key] = $override;
    }

    public function getOverrideForCall(string $fqn): null|MethodCallTypeOverride|FunctionCallTypeOverride
    {
        $key = strtolower($fqn);

        return $this->overridesByFqn[$key] ?? null;
    }
}

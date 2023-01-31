<?php

declare(strict_types=1);

namespace PhpStormMetaStan\Service;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

class StaticMethodReturnTypeResolver implements DynamicStaticMethodReturnTypeExtension
{
    private readonly array $methodNames;

    public function __construct(
        private readonly TypeFromMetaResolver $metaResolver,
        private readonly string $className,
        array $methodNames,
    ) {
        $this->methodNames = array_map(strtolower(...), $methodNames);
    }

    public function getClass(): string
    {
        return $this->className;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = strtolower($methodReflection->getName());

        return in_array($methodName, $this->methodNames, true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): ?Type {
        $knownType = null;

        $methodName = strtolower($methodReflection->getName());
        $args = $methodCall->getArgs();

        if ($args) {
            $metaType = $this->metaResolver->resolveReferencedType($this->className, $methodName, ...$args);
            if ($metaType) {
                $knownType = $metaType;
            }
        }

        return $knownType;
    }
}

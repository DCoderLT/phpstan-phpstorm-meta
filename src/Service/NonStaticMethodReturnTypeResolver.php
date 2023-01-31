<?php

declare(strict_types=1);

namespace PhpStormMetaStan\Service;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class NonStaticMethodReturnTypeResolver implements DynamicMethodReturnTypeExtension
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

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = strtolower($methodReflection->getName());

        return in_array($methodName, $this->methodNames, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
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

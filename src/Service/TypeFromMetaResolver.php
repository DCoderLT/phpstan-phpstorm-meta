<?php

declare(strict_types=1);

namespace PhpStormMetaStan\Service;

use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use PhpStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PhpStormMetaStan\TypeMapping\CallReturnTypeOverrideInterface;
use PhpStormMetaStan\TypeMapping\PassedArgumentType;
use PhpStormMetaStan\TypeMapping\PassedArrayElementType;
use PhpStormMetaStan\TypeMapping\ReturnTypeMap;

class TypeFromMetaResolver
{
    public function __construct(
        private readonly MetaFileParser $parser,
    ) {
    }

    public function resolveReferencedType(
        string $classReference,
        string $methodName,
        Arg ...$args
    ): ?Type {
        $meta = $this->parser->getMeta();

        $override = $meta->getOverrideForCall("$classReference::$methodName");

        if ($override) {
            $arg = $args[$override->argumentOffset] ?? null;
            if ($arg) {
                return $this->resolveTypeFromArgument($override->returnType, $arg);
            }
        }

        return null;
    }

    private function resolveTypeFromArgument(CallReturnTypeOverrideInterface $overrideType, Arg $arg): ?Type
    {
        $argValue = $arg->value;
        if ($overrideType instanceof ReturnTypeMap) {
            if ($argValue instanceof String_) {
                $resolvedType = $overrideType->getMappingForArgument($argValue->value);
                return $this->parseResolvedType($resolvedType);
            }
            return null;
        }

        if ($overrideType instanceof PassedArgumentType) {
            // TODO
            return null;
        }

        if ($overrideType instanceof PassedArrayElementType) {
            // TODO
            return null;
        }

        throw new InvalidArgumentException();
    }

    private function parseResolvedType(FullyQualified|string|null $resolvedType): ?Type
    {
        if (!$resolvedType) {
            return null;
        }

        if ($resolvedType instanceof FullyQualified) {
            return new ObjectType($resolvedType->toString());
        }

        $unionTypes = explode('|', $resolvedType);
        if (count($unionTypes) === 1) {
            return new ObjectType($resolvedType);
        }

        $resolvedSubtypes = [];
        foreach ($unionTypes as $subtype) {
            $resolvedSubtypes [] = new ObjectType($subtype);
        }

        return TypeCombinator::union(...$resolvedSubtypes);
    }
}

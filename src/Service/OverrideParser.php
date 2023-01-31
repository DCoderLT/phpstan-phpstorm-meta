<?php

declare(strict_types=1);

namespace PhpStormMetaStan\Service;

use PhpParser\Node as ParserNode;
use PhpStormMetaStan\TypeMapping\FunctionCallTypeOverride;
use PhpStormMetaStan\TypeMapping\MethodCallTypeOverride;
use PhpStormMetaStan\TypeMapping\PassedArgumentType;
use PhpStormMetaStan\TypeMapping\PassedArrayElementType;
use PhpStormMetaStan\TypeMapping\ReturnTypeMap;

class OverrideParser
{
    public function parseOverride(
        ParserNode\Arg $callableArg,
        ParserNode\Arg $overrideArg,
    ): null|MethodCallTypeOverride|FunctionCallTypeOverride {
        $identifier = $callableArg->value;

        $override = $overrideArg->value;
        if (!$override instanceof ParserNode\Expr\FuncCall
            || !$override->name instanceof ParserNode\Name
        ) {
            return null;
        }

        $map = null;
        $typeOffset = null;
        $elementTypeOffset = null;

        if ($override->getArgs()) {
            $overrideName = $override->name->toString();
            $overrideArg0 = $override->getArgs()[0]->value;
            if ($overrideName === 'map' &&
                $overrideArg0 instanceof ParserNode\Expr\Array_
            ) {
                $map = new ReturnTypeMap();
                foreach ($overrideArg0->items as $arrayItem) {
                    if ($arrayItem
                        && $arrayItem->key instanceof ParserNode\Scalar\String_
                    ) {
                        $arrayKey = $arrayItem->key->value;
                        $arrayValue = $arrayItem->value;
                        if ($arrayValue instanceof ParserNode\Expr\ClassConstFetch
                            && $arrayValue->class instanceof ParserNode\Name\FullyQualified
                            && $arrayValue->name instanceof ParserNode\Identifier
                            && strtolower($arrayValue->name->name)
                        ) {
                            $map->addMapping($arrayKey, clone $arrayValue->class);
                        } elseif ($arrayValue instanceof ParserNode\Scalar\String_) {
                            $map->addMapping($arrayKey, $arrayValue->value);
                        }
                    }
                }
            } elseif ($overrideName === 'type' &&
                $overrideArg0 instanceof ParserNode\Scalar\LNumber
            ) {
                $typeOffset = new PassedArgumentType($overrideArg0->value);
            }
            if ($overrideName === 'elementType'
                && $overrideArg0 instanceof ParserNode\Scalar\LNumber
            ) {
                $elementTypeOffset = new PassedArrayElementType($overrideArg0->value);
            }
        }

        $returnType = $map ?? $typeOffset ?? $elementTypeOffset;

        if (!$returnType) {
            return null;
        }

        if ($identifier instanceof ParserNode\Expr\StaticCall) {
            if ($identifier->class instanceof ParserNode\Name\FullyQualified &&
                $identifier->name instanceof ParserNode\Identifier &&
                $identifier->getArgs()
            ) {
                $identifierArg0 = $identifier->getArgs()[0]->value;
                if ($identifierArg0 instanceof ParserNode\Scalar\LNumber) {
                    return new MethodCallTypeOverride(
                        $identifier->class->toString(),
                        $identifier->name->toString(),
                        $identifierArg0->value,
                        $returnType,
                    );
                }
            }
        }

        if ($identifier instanceof ParserNode\Expr\FuncCall) {
            if ($identifier->name instanceof ParserNode\Name\FullyQualified &&
                $identifier->getArgs()
            ) {
                $identifierArg0 = $identifier->getArgs()[0]->value;
                if ($identifierArg0 instanceof ParserNode\Scalar\LNumber) {
                    return new FunctionCallTypeOverride(
                        $identifier->name->toString(),
                        $identifierArg0->value,
                        $returnType,
                    );
                }
            }
        }

        return null;
    }
}

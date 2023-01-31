<?php

declare(strict_types=1);

namespace PhpStormMetaStan\Service;

use InvalidArgumentException;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Parser\CachedParser;
use PhpStormMetaStan\Service\OverrideParser;
use PhpStormMetaStan\TypeMapping\CallReturnOverrideCollection;
use PhpStormMetaStan\TypeMapping\FunctionCallTypeOverride;
use PhpStormMetaStan\TypeMapping\MethodCallTypeOverride;

class MetaFileParser
{
    private readonly CallReturnOverrideCollection $parsedMeta;
    private array $parsedMetaPaths = [];
    private bool $metaParsed;

    public function __construct(
        private readonly CachedParser $parser,
        private readonly OverrideParser $overrideParser,
        private readonly array $metaPaths,
    ) {
        $this->parsedMeta = new CallReturnOverrideCollection();
        $this->metaParsed = $this->metaPaths === [];
    }

    public function getMeta(): CallReturnOverrideCollection
    {
        if (!$this->metaParsed) {
            $this->parseMeta($this->parsedMeta, ...$this->metaPaths);
            $this->metaParsed = true;
        }

        return $this->parsedMeta;
    }

    private function parseMeta(
        CallReturnOverrideCollection $resultCollector,
        string ...$metaPaths,
    ): void {
        $metaFiles = [];

        $newlyParsedMetaPaths = [];
        foreach ($metaPaths as $path) {
            if (!array_key_exists($path, $this->parsedMetaPaths)) {
                if (file_exists($path)) {
                    $singleFile = new \SplFileInfo($path);
                    $metaFiles[] = $singleFile->getPathname();
                }

                $newlyParsedMetaPaths[] = $path;
            }
        }

        foreach ($metaFiles as $metaFile) {
            $stmts = $this->parser->parseFile($metaFile);

            foreach ($stmts as $topStmt) {
                if ($topStmt instanceof Stmt\Namespace_) {
                    if ($topStmt->name && $topStmt->name->toString() === 'PHPSTORM_META') {
                        foreach ($topStmt->stmts ?? [] as $metaStmt) {
                            if ($metaStmt instanceof Expression
                                && $metaStmt->expr instanceof FuncCall
                                && $metaStmt->expr->name instanceof Name
                                && $metaStmt->expr->name->toString() === 'override'
                            ) {
                                $args = $metaStmt->expr->getArgs();
                                if (count($args) >= 2) {
                                    [$callableArg, $overrideArg] = $args;
                                    $parsedOverride = $this->overrideParser->parseOverride($callableArg, $overrideArg);

                                    if ($parsedOverride instanceof MethodCallTypeOverride) {
                                        $resultCollector->addMethodCallOverride($parsedOverride);
                                    } elseif ($parsedOverride instanceof FunctionCallTypeOverride) {
                                        $resultCollector->addFunctionCallOverride($parsedOverride);
                                    } elseif ($parsedOverride) {
                                        throw new InvalidArgumentException("Unrecognized phpstorm meta override");
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($newlyParsedMetaPaths as $path) {
                $this->parsedMetaPaths[$path] = $path;
            }
        }
    }
}

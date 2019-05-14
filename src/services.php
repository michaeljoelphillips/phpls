<?php

declare(strict_types=1);

use LanguageServer\LSP\Command\Completion;
use LanguageServer\LSP\Command\DidChange;
use LanguageServer\LSP\Command\DidOpen;
use LanguageServer\LSP\Command\Initialize;
use LanguageServer\LSP\Command\SignatureHelp;
use LanguageServer\LSP\DocumentParser;
use LanguageServer\LSP\SourceLocator\RegistrySourceLocator;
use LanguageServer\LSP\TextDocumentRegistry;
use LanguageServer\LSP\TypeResolver;
use LanguageServer\RPC\Encoder\JsonRpcEncoder;
use LanguageServer\RPC\MessageSerializer;
use LanguageServer\RPC\RpcServer;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\Container\ContainerInterface;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

return [
    RpcServer::class => function (ContainerInterface $container) {
        return new RpcServer($container, $container->get(MessageSerializer::class));
    },
    SerializerInterface::class => function (ContainerInterface $container) {
        return new Serializer(
            [
                new PropertyNormalizer(),
            ],
            [
                new JsonRpcEncoder(),
            ]
        );
    },
    Parser::class => function (ContainerInterface $container) {
        return (new ParserFactory())->create(
            ParserFactory::ONLY_PHP7,
                new Lexer([
                    'usedAttributes' => [
                        'comments',
                        'startLine',
                        'endLine',
                        'startFilePos',
                        'endFilePos',
                    ],
                ])
            );
    },
    MemoizingParser::class => function (ContainerInterface $container) {
        return new MemoizingParser($container->get(Parser::class));
    },
    Locator::class => function (ContainerInterface $container) {
        return new Locator($container->get(MemoizingParser::class));
    },
    DocumentParser::class => function (ContainerInterface $container) {
        return new DocumentParser($container->get(MemoizingParser::class));
    },
    ClassReflector::class => function (ContainerInterface $container) {
        $locator = $container->get(Locator::class);

        return new ClassReflector(
            new AggregateSourceLocator([
                new RegistrySourceLocator($locator, $container->get(TextDocumentRegistry::class)),
                new DirectoriesSourceLocator(
                    [
                        '/Users/mphillips2/Code/answer-automation-v2/src',
                    ],
                    $locator
                ),
            ])
        );
    },
    TextDocumentRegistry::class => DI\create(TextDocumentRegistry::class),
    'initialize' => DI\create(Initialize::class),
    'initialized' => DI\create(Completion::class),
    'textDocument/completion' => DI\create(Completion::class),
    'textDocument/signatureHelp' => function (ContainerInterface $container) {
        return new SignatureHelp(
            $container->get(ClassReflector::class),
            $container->get(DocumentParser::class),
            new TypeResolver(),
            $container->get(TextDocumentRegistry::class)
        );
    },
    'textDocument/didOpen' => function (ContainerInterface $container) {
        return new DidOpen($container->get(TextDocumentRegistry::class));
    },
    'textDocument/didChange' => function (ContainerInterface $container) {
        return new DidChange($container->get(TextDocumentRegistry::class));
    },
];

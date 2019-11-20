<?php

declare(strict_types=1);

use LanguageServer\Method\Exit_;
use LanguageServer\Method\Initialize;
use LanguageServer\Method\Initialized;
use LanguageServer\Method\TextDocument\Completion;
use LanguageServer\Method\TextDocument\CompletionProvider\ClassConstantProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\InstanceMethodProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\InstanceVariableProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\StaticMethodProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\StaticPropertyProvider;
use LanguageServer\Method\TextDocument\DidChange;
use LanguageServer\Method\TextDocument\DidOpen;
use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Parser\LenientParser;
use LanguageServer\RegistrySourceLocator;
use LanguageServer\Server\JsonRpcEncoder;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

return [
    LoggerInterface::class => function (ContainerInterface $container) {
        $logger = new Logger('default');
        $logger->pushHandler(new StreamHandler(fopen('/tmp/language-server.log', 'w+'), Logger::DEBUG));

        return $logger;
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
        return new LenientParser(
            (new ParserFactory())->create(
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
            )
        );
    },
    MemoizingParser::class => function (ContainerInterface $container) {
        return new MemoizingParser($container->get(Parser::class));
    },
    DocumentParser::class => function (ContainerInterface $container) {
        return new DocumentParser($container->get(MemoizingParser::class));
    },
    IncompleteDocumentParser::class => function (ContainerInterface $container) {
        return new IncompleteDocumentParser($container->get(MemoizingParser::class));
    },
    SourceLocator::class => function (ContainerInterface $container) {
        $factory = new LazyLoadingValueHolderFactory();

        return $factory->createProxy(
            AggregateSourceLocator::class,
            function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($container) {
                $locator = new AstLocator($container->get(MemoizingParser::class));

                $initializer = null;
                $wrappedObject = new AggregateSourceLocator([
                    new RegistrySourceLocator($locator, $container->get(TextDocumentRegistry::class)),
                    new MemoizingSourceLocator(
                        new AggregateSourceLocator([
                            new PhpInternalSourceLocator($locator, new PhpStormStubsSourceStubber($container->get(MemoizingParser::class))),
                            (new MakeLocatorForComposerJsonAndInstalledJson())($container->get('project_root'), $locator),
                        ]),
                    ),
                ]);
            }
        );
    },
    ClassReflector::class => function (ContainerInterface $container) {
        return new ClassReflector($container->get(SourceLocator::class));
    },
    FunctionReflector::class => function (ContainerInterface $container) {
        return new FunctionReflector(
            $container->get(SourceLocator::class),
            $container->get(ClassReflector::class)
        );
    },
    TypeResolver::class => function (ContainerInterface $container) {
        return new TypeResolver($container->get(ClassReflector::class));
    },
    TextDocumentRegistry::class => DI\create(TextDocumentRegistry::class),
    InstanceMethodProvider::class => function (ContainerInterface $container) {
        return new InstanceMethodProvider();
    },
    InstanceVariableProvider::class => function (ContainerInterface $container) {
        return new InstanceVariableProvider();
    },
    ClassConstantProvider::class => function (ContainerInterface $container) {
        return new ClassConstantProvider();
    },
    StaticMethodProvider::class => function (ContainerInterface $container) {
        return new StaticMethodProvider();
    },
    StaticPropertyProvider::class => function (ContainerInterface $container) {
        return new StaticPropertyProvider();
    },
    'completionProviders' => [
        DI\get(InstanceMethodProvider::class),
        DI\get(InstanceVariableProvider::class),
        DI\get(StaticMethodProvider::class),
        DI\get(StaticPropertyProvider::class),
        DI\get(ClassConstantProvider::class),
    ],
    'initialize' => function (ContainerInterface $container) {
        return new Initialize($container);
    },
    'initialized' => DI\create(Initialized::class),
    'exit' => function (ContainerInterface $container) {
        return new Exit_($container->get(TextDocumentRegistry::class));
    },
    'textDocument/didSave' => function (ContainerInterface $container) {
        return new DidSave(
            $container->get(TextDocumentRegistry::class)
        );
    },
    'textDocument/completion' => function (ContainerInterface $container) {
        return new Completion(
            $container->get(IncompleteDocumentParser::class),
            $container->get(TextDocumentRegistry::class),
            $container->get(ClassReflector::class),
            $container->get(TypeResolver::class),
            ...$container->get('completionProviders')
        );
    },
    'textDocument/signatureHelp' => function (ContainerInterface $container) {
        return new SignatureHelp(
            $container->get(ClassReflector::class),
            $container->get(FunctionReflector::class),
            $container->get(IncompleteDocumentParser::class),
            $container->get(TypeResolver::class),
            $container->get(TextDocumentRegistry::class)
        );
    },
    'textDocument/didOpen' => function (ContainerInterface $container) {
        return new DidOpen(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    'textDocument/didChange' => function (ContainerInterface $container) {
        return new DidChange(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    'textDocument/didClose' => function () {
        return function () { return; };
    },
];

<?php

declare(strict_types=1);

use LanguageServer\Method\Exit_;
use LanguageServer\Method\Initialize;
use LanguageServer\Method\Initialized;
use LanguageServer\Method\Shutdown;
use LanguageServer\Method\TextDocument\Completion;
use LanguageServer\Method\TextDocument\CompletionProvider\ClassConstantProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\MethodProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\InstanceVariableProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\StaticPropertyProvider;
use LanguageServer\Method\TextDocument\DidChange;
use LanguageServer\Method\TextDocument\DidClose;
use LanguageServer\Method\TextDocument\DidOpen;
use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\DocumentParser;
use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Parser\LenientParser;
use LanguageServer\RegistrySourceLocator;
use LanguageServer\Server\Serializer\JsonRpcEncoder;
use LanguageServer\Server\Serializer\MessageDenormalizer;
use LanguageServer\Server\Serializer\MessageSerializer;
use LanguageServer\Server\Server;
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
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Stream\CompositeStream;
use React\Stream\DuplexStreamInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
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
use LanguageServer\Server\MessageSerializerInterface;

return [
    Server::class => function (ContainerInterface $container) {
        return new Server(
            $container->get(MessageSerializerInterface::class),
            $container->get(LoggerInterface::class),
            $container->get('messageHandlers')
        );
    },
    MessageSerializerInterface::class => function (ContainerInterface $container) {
        return $container->get(MessageSerializer::class);
    },
    DuplexStreamInterface::class => function (ContainerInterface $container) {
        $loop = $container->get(LoopInterface::class);

        return new CompositeStream(
            new ReadableResourceStream(STDIN, $loop),
            new WritableResourceStream(STDOUT, $loop)
        );
    },
    LoopInterface::class => function (ContainerInterface $container) {
        return Factory::create();
    },
    LoggerInterface::class => function (ContainerInterface $container) {
        $logger = new Logger('default');
        $logger->pushHandler(new StreamHandler(fopen('/tmp/language-server.log', 'w+'), Logger::DEBUG));

        return $logger;
    },
    SerializerInterface::class => function (ContainerInterface $container) {
        return new Serializer(
            [
                new MessageDenormalizer(),
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
                $locator = new AstLocator(
                    $container->get(MemoizingParser::class),
                    function () use ($container) {
                        return $container->get(FunctionReflector::class);
                    }
                );

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
    MethodProvider::class => function (ContainerInterface $container) {
        return new MethodProvider();
    },
    InstanceVariableProvider::class => function (ContainerInterface $container) {
        return new InstanceVariableProvider();
    },
    ClassConstantProvider::class => function (ContainerInterface $container) {
        return new ClassConstantProvider();
    },
    StaticPropertyProvider::class => function (ContainerInterface $container) {
        return new StaticPropertyProvider();
    },
    'completionProviders' => [
        DI\get(MethodProvider::class),
        DI\get(InstanceVariableProvider::class),
        DI\get(StaticPropertyProvider::class),
        DI\get(ClassConstantProvider::class),
    ],
    'messageHandlers' => [
        DI\get(Initialize::class),
        DI\get(Initialized::class),
        DI\get(DidSave::class),
        DI\get(Completion::class),
        DI\get(SignatureHelp::class),
        DI\get(DidOpen::class),
        DI\get(DidChange::class),
        DI\get(DidClose::class),
        DI\get(Exit_::class),
    ],
    Initialize::class => function (ContainerInterface $container) {
        return new Initialize($container);
    },
    Initialized::class => DI\create(Initialized::class),
    Shutdown::class => function (ContainerInterface $container) {
        return new Shutdown($container->get(Server::class));
    },
    Exit_::class => function (ContainerInterface $container) {
        return new Exit_($container->get(TextDocumentRegistry::class));
    },
    DidSave::class => function (ContainerInterface $container) {
        return new DidSave(
            $container->get(TextDocumentRegistry::class)
        );
    },
    Completion::class => function (ContainerInterface $container) {
        return new Completion(
            $container->get(IncompleteDocumentParser::class),
            $container->get(TextDocumentRegistry::class),
            $container->get(ClassReflector::class),
            $container->get(TypeResolver::class),
            ...$container->get('completionProviders')
        );
    },
    SignatureHelp::class => function (ContainerInterface $container) {
        return new SignatureHelp(
            $container->get(ClassReflector::class),
            $container->get(FunctionReflector::class),
            $container->get(IncompleteDocumentParser::class),
            $container->get(TypeResolver::class),
            $container->get(TextDocumentRegistry::class)
        );
    },
    DidOpen::class => function (ContainerInterface $container) {
        return new DidOpen(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    DidChange::class => function (ContainerInterface $container) {
        return new DidChange(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    DidClose::class => function () {
        return new DidClose();
    },
];

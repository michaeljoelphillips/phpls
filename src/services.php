<?php

declare(strict_types=1);

use LanguageServer\Config\ConfigFactory;
use LanguageServer\Console\RunCommand;
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
use LanguageServer\Method\TextDocument\DidClose;
use LanguageServer\Method\TextDocument\DidOpen;
use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\IncompleteDocumentParser;
use LanguageServer\Parser\LenientParser;
use LanguageServer\RegistrySourceLocator;
use LanguageServer\Server\Serializer\JsonRpcEncoder;
use LanguageServer\Server\Serializer\MessageDenormalizer;
use LanguageServer\Server\Serializer\MessageSerializer;
use LanguageServer\Server\Server;
use LanguageServer\TextDocumentRegistry;
use LanguageServer\TypeResolver;
use Monolog\Handler\NullHandler;
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
use Symfony\Component\Console\Application;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

return [
    Server::class => static function (ContainerInterface $container) {
        return new Server(
            $container->get(MessageSerializer::class),
            $container->get('messageHandlers')
        );
    },
    MessageSerializer::class => static function (ContainerInterface $container) {
        return new MessageSerializer($container->get(SerializerInterface::class));
    },
    LoopInterface::class => static function (ContainerInterface $container) {
        return Factory::create();
    },
    Application::class => static function (ContainerInterface $container) {
        $app = new Application();
        $app->add($container->get(RunCommand::class));
        $app->setDefaultCommand('phpls:run', true);

        return $app;
    },
    LoggerInterface::class => static function (ContainerInterface $container) {
        $config = $container->get('config')['log'];

        $logger = new Logger('default');

        if ($config['enabled'] === true) {
            $logLevel = $config['level'] === 'debug' ? Logger::DEBUG : Logger::INFO;

            $logger->pushHandler(new StreamHandler(fopen($config['path'], 'w+'), $logLevel));
        } else {
            $logger->pushHandler(new NullHandler());
        }

        return $logger;
    },
    SerializerInterface::class => static function (ContainerInterface $container) {
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
    Parser::class => static function (ContainerInterface $container) {
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
    MemoizingParser::class => static function (ContainerInterface $container) {
        return new MemoizingParser($container->get(Parser::class));
    },
    IncompleteDocumentParser::class => static function (ContainerInterface $container) {
        return new IncompleteDocumentParser($container->get(MemoizingParser::class));
    },
    SourceLocator::class => static function (ContainerInterface $container) {
        $factory = new LazyLoadingValueHolderFactory();

        return $factory->createProxy(
            AggregateSourceLocator::class,
            static function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($container) : void {
                $locator = new AstLocator(
                    $container->get(MemoizingParser::class),
                    static function () use ($container) {
                        return $container->get(FunctionReflector::class);
                    }
                );

                $initializer   = null;
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
    ClassReflector::class => static function (ContainerInterface $container) {
        return new ClassReflector($container->get(SourceLocator::class));
    },
    FunctionReflector::class => static function (ContainerInterface $container) {
        return new FunctionReflector(
            $container->get(SourceLocator::class),
            $container->get(ClassReflector::class)
        );
    },
    TypeResolver::class => static function (ContainerInterface $container) {
        return new TypeResolver($container->get(ClassReflector::class));
    },
    TextDocumentRegistry::class => DI\create(TextDocumentRegistry::class),
    StaticMethodProvider::class => static function (ContainerInterface $container) {
        return new StaticMethodProvider();
    },
    InstanceMethodProvider::class => static function (ContainerInterface $container) {
        return new InstanceMethodProvider();
    },
    InstanceVariableProvider::class => static function (ContainerInterface $container) {
        return new InstanceVariableProvider();
    },
    ClassConstantProvider::class => static function (ContainerInterface $container) {
        return new ClassConstantProvider();
    },
    StaticPropertyProvider::class => static function (ContainerInterface $container) {
        return new StaticPropertyProvider();
    },
    'completionProviders' => [
        DI\get(InstanceMethodProvider::class),
        DI\get(StaticMethodProvider::class),
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
    Initialize::class => static function (ContainerInterface $container) {
        return new Initialize($container);
    },
    Initialized::class => DI\create(Initialized::class),
    Exit_::class => static function (ContainerInterface $container) {
        return new Exit_();
    },
    DidSave::class => static function (ContainerInterface $container) {
        return new DidSave(
            $container->get(TextDocumentRegistry::class)
        );
    },
    Completion::class => static function (ContainerInterface $container) {
        return new Completion(
            $container->get(IncompleteDocumentParser::class),
            $container->get(TextDocumentRegistry::class),
            $container->get(ClassReflector::class),
            $container->get(TypeResolver::class),
            ...$container->get('completionProviders')
        );
    },
    SignatureHelp::class => static function (ContainerInterface $container) {
        return new SignatureHelp(
            $container->get(ClassReflector::class),
            $container->get(FunctionReflector::class),
            $container->get(IncompleteDocumentParser::class),
            $container->get(TypeResolver::class),
            $container->get(TextDocumentRegistry::class)
        );
    },
    DidOpen::class => static function (ContainerInterface $container) {
        return new DidOpen(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    DidChange::class => static function (ContainerInterface $container) {
        return new DidChange(
            $container->get(TextDocumentRegistry::class),
            $container->get(IncompleteDocumentParser::class)
        );
    },
    DidClose::class => static function () {
        return new DidClose();
    },
    'config' => static function () {
        return (new ConfigFactory())->__invoke();
    },
];

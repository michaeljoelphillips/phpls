<?php

declare(strict_types=1);

use LanguageServer\Config\ConfigFactory;
use LanguageServer\Console\RunCommand;
use LanguageServer\MemoizingParser;
use LanguageServer\Reflection\MemoizingSourceLocator;
use LanguageServer\Method\Exit_;
use LanguageServer\Method\Initialize;
use LanguageServer\Method\Initialized;
use LanguageServer\Method\TextDocument\Completion;
use LanguageServer\Method\TextDocument\CompletionProvider\ClassConstantProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\InstanceMethodProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\InstanceVariableProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\MethodDocTagProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\PropertyDocTagProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\StaticMethodProvider;
use LanguageServer\Method\TextDocument\CompletionProvider\StaticPropertyProvider;
use LanguageServer\Method\TextDocument\DidChange;
use LanguageServer\Method\TextDocument\DidClose;
use LanguageServer\Method\TextDocument\DidOpen;
use LanguageServer\Method\TextDocument\DidSave;
use LanguageServer\Method\TextDocument\SignatureHelp;
use LanguageServer\Parser\CorrectiveParser;
use LanguageServer\Reflection\RegistrySourceLocator;
use LanguageServer\Server\Cache\UsageAwareCache;
use LanguageServer\Server\Log\LogHandler;
use LanguageServer\Server\Serializer\JsonRpcEncoder;
use LanguageServer\Server\Serializer\MessageDenormalizer;
use LanguageServer\Server\Serializer\MessageSerializer;
use LanguageServer\Server\Server as LanguageServer;
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
use React\Socket\Connector as TcpClient;
use React\Socket\Server as TcpServer;
use React\Stream\CompositeStream;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

return [
    LanguageServer::class => static function (ContainerInterface $container) {
        return new LanguageServer(
            $container->get(MessageSerializer::class),
            $container->get(LoggerInterface::class),
            $container->get('messageHandlers')
        );
    },
    'stream' => static function (ContainerInterface $container) {
        $mode = $container->get('mode');
        $port = $container->get('port');
        $loop = $container->get(LoopInterface::class);

        switch ($mode) {
            case 'stdio':
                return new CompositeStream(
                    new ReadableResourceStream(STDIN, $loop),
                    new WritableResourceStream(STDOUT, $loop)
                );
            case 'client':
                $client = new TcpClient($loop);

                return $client->connect(sprintf('127.0.0.1:%d', $port));
            case 'server':
                return new TcpServer(sprintf('127.0.0.1:%d', $port), $loop);
        }
    },
    'port' => null,
    'mode' => null,
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
        $logger   = new Logger('default');
        $config   = $container->get('config')['log'];
        $logLevel = $config['level'] === 'debug' ? Logger::DEBUG : Logger::INFO;

        if ($config['enabled'] === true) {
            $logger->pushHandler(new StreamHandler(fopen($config['path'], 'w+'), $logLevel));
        } else {
            $logger->pushHandler(new NullHandler());
        }

        $lspLogHandler = new LogHandler($container->get(MessageSerializer::class), $logLevel);
        $lspLogHandler->setStream($container->get('stream'));

        $logger->pushHandler($lspLogHandler);

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
        return new MemoizingParser(
            $container->get('parserCache'),
            new CorrectiveParser(
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
                ),
                $container->get(LoggerInterface::class)
            )
        );
    },
    'parserCache' => static function () {
        return new UsageAwareCache();
    },
    'reflectorCache' => static function () {
        return new UsageAwareCache();
    },
    SourceLocator::class => static function (ContainerInterface $container) {
        $factory = new LazyLoadingValueHolderFactory();

        return $factory->createProxy(
            AggregateSourceLocator::class,
            static function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($container) : void {
                $locator = new AstLocator(
                    $container->get(Parser::class),
                    static function () use ($container) {
                        return $container->get(FunctionReflector::class);
                    }
                );

                $initializer   = null;
                $wrappedObject = new AggregateSourceLocator([
                    new RegistrySourceLocator($locator, $container->get(TextDocumentRegistry::class)),
                    new MemoizingSourceLocator(
                        $container->get('reflectorCache'),
                        new AggregateSourceLocator([
                            new PhpInternalSourceLocator($locator, new PhpStormStubsSourceStubber($container->get(Parser::class))),
                            (new MakeLocatorForComposerJsonAndInstalledJson())($container->get('project_root'), $locator),
                        ]),
                    ),
                ]);
            }
        );
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
    'completionProviders' => [
        DI\get(InstanceMethodProvider::class),
        DI\get(StaticMethodProvider::class),
        DI\get(InstanceVariableProvider::class),
        DI\get(StaticPropertyProvider::class),
        DI\get(ClassConstantProvider::class),
        DI\get(MethodDocTagProvider::class),
        DI\get(PropertyDocTagProvider::class),
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
    Completion::class => static function (ContainerInterface $container) {
        return new Completion(
            $container->get(TextDocumentRegistry::class),
            $container->get(ClassReflector::class),
            $container->get(TypeResolver::class),
            $container->get(LoggerInterface::class),
            ...$container->get('completionProviders')
        );
    },
    'config' => static function () {
        return (new ConfigFactory())->__invoke();
    },
];

<?php

declare(strict_types=1);

namespace LanguageServer\RPC\Normalizers;

use LanguageServer\LSP\InitializeParams;
use LanguageServer\RPC\RequestMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class RequestMessageDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const METHOD_PARAMETER_MAP = [
        'initialize' => InitializeParams::class,
    ];

    private const FORMAT = 'jsonrpc';

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $requestMessage = new RequestMessage();

        $parameterObjectType = self::METHOD_PARAMETER_MAP[$data['method']];

        $parameterObject = $this
            ->denormalizer
            ->denormalize($data['params'], $parameterObjectType, $format);

        $requestMessage->id = $data['id'];
        $requestMessage->method = $data['method'];
        $requestMessage->params = $parameterObject;

        return $requestMessage;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return RequestMessage::class === $type && self::FORMAT === $format;
    }
}

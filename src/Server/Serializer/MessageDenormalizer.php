<?php

declare(strict_types=1);

namespace LanguageServer\Server\Serializer;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\Server\Protocol\RequestMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MessageDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (false === array_key_exists('id', $data)) {
            return new NotificationMessage($data['method'], $data['params'] ?? null);
        }

        return new RequestMessage($data['id'], $data['method'], $data['params'] ?? null);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Message::class === $type && is_array($data);
    }
}

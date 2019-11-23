<?php

namespace LanguageServer\Server;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\Server\Protocol\RequestMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MessageDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (array_key_exists('id', $data) === false) {
            return new NotificationMessage($data['method'], $data['params'] ?? null);
        }

        return new RequestMessage($data['id'], $data['method'], $data['params'] ?? null);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Message::class && is_array($data);
    }
}

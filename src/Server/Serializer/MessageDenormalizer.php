<?php

declare(strict_types=1);

namespace LanguageServer\Server\Serializer;

use LanguageServer\Server\Protocol\Message;
use LanguageServer\Server\Protocol\NotificationMessage;
use LanguageServer\Server\Protocol\RequestMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function array_key_exists;
use function is_array;

class MessageDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param array<mixed, mixed> $context
     *
     * @return Message
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (array_key_exists('id', $data) === false) {
            return new NotificationMessage($data['method'], $data['params'] ?? null);
        }

        return new RequestMessage($data['id'], $data['method'], $data['params'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Message::class && is_array($data);
    }
}

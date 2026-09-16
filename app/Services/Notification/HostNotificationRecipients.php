<?php

namespace App\Services\Notification;

class HostNotificationRecipients
{
    /** @return list<string> */
    public static function parse(?string $value): array
    {
        $addresses = preg_split('/[,;\r\n]+/', $value ?? '') ?: [];

        return array_values(array_unique(array_filter(array_map(
            static fn (string $address): string => strtolower(trim($address)),
            $addresses,
        ))));
    }
}

<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Contracts;

interface Subscribable
{
    public function getSubscribableLabel(): string;

    public function getSubscribableUrl(): ?string;

    public static function getSubscribableType(): string;
}

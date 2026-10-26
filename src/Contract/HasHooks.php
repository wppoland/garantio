<?php

declare(strict_types=1);

namespace Garantio\Contract;

defined('ABSPATH') || exit;

interface HasHooks
{
    public function registerHooks(): void;
}

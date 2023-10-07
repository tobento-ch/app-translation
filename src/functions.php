<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Translation;

use Psr\Container\ContainerInterface;
use Tobento\Service\HelperFunction\Functions;
use Tobento\Service\Translation\TranslatorInterface;

if (!function_exists('trans')) {
    /**
     * Returns the translated message.
     *
     * @param string $message The message to translate.
     * @param array $parameters Any parameters for the message.
     * @param null|string $locale The locale or null to use the default.
     * @return string The translated message.
     */
    function trans(string $message, array $parameters = [], null|string $locale = null): string
    {
        return Functions::get(ContainerInterface::class)
            ->get(TranslatorInterface::class)
            ->trans($message, $parameters, $locale);
    }
}
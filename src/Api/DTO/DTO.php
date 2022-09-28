<?php
/*
 * This file is part of the pushull-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pushull\PushullTranslationProvider\Api\DTO;

if (class_exists('Spatie\DataTransferObject\FlexibleDataTransferObject')) {
    class DTO extends \Spatie\DataTransferObject\FlexibleDataTransferObject
    {
    }
} else {
    class DTO extends \Spatie\DataTransferObject\DataTransferObject
    {
    }
}

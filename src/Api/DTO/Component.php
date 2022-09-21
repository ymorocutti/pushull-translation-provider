<?php
/*
 * This file is part of the pilcrowls-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pilcrowls\PilcrowlsTranslationProvider\Api\DTO;

class Component extends DTO
{
    /** @var string */
    public $slug;

    /** @var string */
    public $url;

    /** @var string */
    public $repository_url;

    /** @var string */
    public $translations_url;

    /** @var bool */
    public $created = false;
}

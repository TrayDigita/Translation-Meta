<?php
/*
 * Copyright (C) 2021 Tray Digita
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace TrayDigita\TranslationMeta;

/**
 * @property-read string $text_domain
 * @property-read AbstractTranslations $translations
 * @property-read AbstractMetaData $metadata
 * @property-read $this $translation
 * @mixin AbstractMetaData
 */
abstract class AbstractTranslation
{
    /**
     * @var string default is default of WordPress text domain
     */
    protected $text_domain = 'default';

    /**
     * @var AbstractTranslations
     */
    protected $translations;

    /**
     * @var AbstractMetaData|null
     */
    protected $metadata = null;

    /**
     * @param AbstractTranslations $translations
     * @param string $text_domain
     */
    public function __construct(
        AbstractTranslations $translations,
        string $text_domain = 'default'
    ) {
        $this->translations = $translations;
        // fallback to default
        $this->text_domain = $text_domain?:'default';
    }

    /**
     * @return string
     */
    public function getTextDomain(): string
    {
        return $this->text_domain;
    }

    /**
     * @return AbstractTranslations
     */
    public function getTranslations(): AbstractTranslations
    {
        return $this->translations;
    }

    /**
     * @return AbstractMetaData
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param string $name
     *
     * @return $this|mixed|string|AbstractMetaData|AbstractTranslations
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'translation':
                return $this;
            case 'translations':
                return $this->getTranslations();
            case 'metadata':
                return $this->getMetadata();
            case 'text_domain':
                return $this->getTextDomain();
        }
        return $this->getMetadata()->$name;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return false|mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func([$this->getMetadata(), $name], ...$arguments);
    }
}

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

abstract class AbstractTranslations
{
    /**
     * @var array<string, array<string, AbstractTranslation>>
     */
    protected $translations = [];

    /**
     * @return AbstractTranslation[]
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Append translation if not exists
     *
     * @param AbstractTranslation $translation
     * @param string|null $locale
     *
     * @return bool
     */
    public function add(AbstractTranslation $translation, string $locale = null) : bool
    {
        $textDomain = $translation->getTextDomain();
        $locale = $locale?:$translation->getLocale();
        if (!isset($this->translations[$textDomain])
            || !isset($this->translations[$textDomain][$locale])
        ) {
            $this->translations[$textDomain][$locale] = $translation;
            return true;
        }

        return false;
    }

    /**
     * Set or replace translation
     *
     * @param string $locale
     * @param AbstractTranslation $translation
     */
    public function set(AbstractTranslation $translation, string $locale = null)
    {
        $textDomain = $translation->getTextDomain();
        $locale = $locale?:$translation->getLocale();
        $this->translations[$textDomain][$locale] = $translation;
    }

    public function remove(string $text_domain, string $locale = null)
    {
        if (!isset($this->translations[$text_domain])) {
            return;
        }
        if (!$locale) {
            unset($this->translations[$text_domain]);
            return;
        }
        unset($this->translations[$text_domain][$text_domain]);
    }

    /**
     * @param string $text_domain
     * @param string|null $locale
     *
     * @return AbstractTranslation|AbstractTranslation[]|null
     */
    public function get(string $text_domain, string $locale = null)
    {
        if (!isset($this->translations[$text_domain])) {
            return null;
        }

        if (!$locale) {
            return $this->translations[$text_domain];
        }
        return $this->translations[$text_domain][$locale];
    }
}

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
 * @property-read string $type
 */
abstract class AbstractTransient extends AbstractTranslation
{
    protected $type = '';

    /**
     * @return string theme|plugin
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $slug
     *
     * @return array<string, string|bool>
     */
    public function getTransient(string $slug) : array
    {
        $package = $this->get('Package');
        $package = $package && \substr($package, -4) === '.zip'
            ? $package
            : '';
        $autoupdate = $this->get('Autoupdate') === 'true';
        return [
            'type'      => $this->getType(),
            'slug'      => $slug,
            'language'  => $this->getLocale(),
            'version'   => $this->getProjectIdVersion(),
            'updated'   => $this->getPoRevisionDate(),
            'package'   => $package,
            'autoupdate'=> $autoupdate
        ];
    }

    /**
     * @inheritDoc
     */
    public function __get(string $name)
    {
        if ($name === 'type') {
            return $this->getType();
        }

        return parent::__get($name);
    }
}

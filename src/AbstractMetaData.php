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

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use function is_string;
use function preg_replace;
use function preg_replace_callback;
use function property_exists;
use function str_replace;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function ucwords;

/**
 * @property-read string $pot_creation_date
 * @property-read string $po_revision_date
 * @property-read string $revision_date
 * @property-read string $project_id_version
 * @property-read string $version
 * @property-read string $x_generator
 * @property-read string $generator
 * @property-read string $language
 * @property-read string $locale
 * @property-read string $language_team
 * @property-read string $team
 * @property-read AbstractTranslation $translation
 * @property-read array<string, string> $defaults
 */
abstract class AbstractMetaData
{
    const DATE_FORMAT = 'Y-m-d H:i:sO';

    /**
     * @var array<string, string>
     */
    const DEFAULT_METADATA = [
        'POT-Creation-Date'  => '',
        'PO-Revision-Date'   => '',
        'Project-Id-Version' => '',
        'X-Generator'        => '',
        'Language'           => '',
        'Language-Team'      => '',
        'Locale'             => '',
        'Version'            => '',
    ];

    /**
     * @var array|string[]
     */
    protected $data = self::DEFAULT_METADATA;

    /**
     * @var AbstractTranslation
     */
    protected $translation;

    /**
     * @param AbstractTranslation $translation
     * @param array $metadata
     */
    public function __construct(AbstractTranslation $translation, array $metadata = [])
    {
        $this->translation = $translation;
        $this->data        = $this->initialize($metadata);
    }

    /**
     * @return string
     */
    public function getPotCreationDate(): string
    {
        return $this->get('POT-Creation-Date');
    }

    /**
     * @return string
     */
    public function getPoRevisionDate(): string
    {
        return $this->get('PO-Revision-Date');
    }

    /**
     * @return string
     */
    public function getProjectIdVersion(): string
    {
        return $this->get('Project-Id-Version');
    }

    /**
     * @return string
     */
    public function getXGenerator(): string
    {
        return $this->get('X-Generator');
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->get('Language');
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->get('Locale');
    }

    /**
     * @return string
     */
    public function getLanguageTeam(): string
    {
        return $this->get('Language-Team');
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->get('Version');
    }

    /**
     * @return array|string[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return AbstractTranslation
     */
    public function getTranslation(): AbstractTranslation
    {
        return $this->translation;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function normalizeKey(string $name) : string
    {
        $lowerName = strtolower(str_replace(['_', ' '], '-', trim($name)));
        if (strpos($lowerName, 'pot-') === 0) {
            $lowerName = 'POT-' . substr($lowerName, 3);
        } elseif (strpos($lowerName, 'po-') === 0) {
            $lowerName = 'PO-' . substr($lowerName, 3);
        }
        return ucwords($lowerName, '-');
    }

    /**
     * @param string $date
     *
     * @return string
     */
    protected function normalizeDate(string $date) : string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }
        $date = preg_replace_callback(
            '~^
            (?P<year>[1-9][0-9]{3})
            [-:\s]*(?P<month>0[0-9]|1[012])
            [-:\s]*(?P<day>[012][0-9]|3[01])
            \s*
            (?<hour>[01][0-9]|2[0-4])
            [-:\s]*(?<minute>[0-5][0-9])
            [-:\s]*(?<second>[0-5][0-9](?:\.[0-9]+)?)
            (?P<suffix>.+)
            $
            ~x',
            function ($e) {
                $suffix = preg_replace(
                    '~(UTC|GMT)\s*([0-9])~i',
                    'GMT+$2',
                    $e['suffix']
                );
                return "{$e['year']}-{$e['month']}-{$e['day']}"
                    . " {$e['hour']}:{$e['minute']}:{$e['second']} $suffix";
            },
            $date
        );
        try {
            $date = new DateTimeImmutable($date, new DateTimeZone('UTC'));
            return $date->format(self::DATE_FORMAT);
        } catch (Throwable $exception) {
            return '';
        }
    }

    public function normalizeLocale(string $locale) : string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return '';
        }
        return preg_replace_callback(
            '~^(?P<prefix>[_-]*[a-zA-Z]*[_-]+)?(?P<suffix>[a-zA-Z]+)$~',
            function ($e) {
                $prefix = $e['prefix']??'';
                $prefix = trim($prefix, '-_');
                $suffix = $e['suffix'];
                if ($prefix) {
                    $prefix = strtolower($prefix).'_';
                }
                return strtolower($prefix) . strtoupper($suffix);
            },
            $locale
        );
    }

    /**
     * @param array $metadata
     *
     * @return string[]
     * @private
     */
    private function initialize(array $metadata) : array
    {
        $data = $this->getDefaults();
        foreach ($metadata as $key => $item) {
            if (! is_string($key)) {
                continue;
            }
            $key = isset($data[$key]) ? $key : $this->normalizeKey($key);
            // ignore empty
            if (!$item && !empty($data[$key])) {
                continue;
            }
            $item = $item === true ? 'true' : $item;
            $item = $item === false ? 'false' : $item;
            // convert to string
            $data[$key] = (string) $item;
        }

        // combine
        if (empty($data['Project-Id-Version']) && !empty($data['Version'])) {
            $data['Project-Id-Version'] = $data['Version'];
        }
        if (empty($data['Version']) && !empty($data['Project-Id-Version'])) {
            $data['Version'] = $data['Project-Id-Version'];
        }
        if (empty($data['X-Generator']) && !empty($data['Generator'])) {
            $data['X-Generator'] = $data['Generator'];
        }
        if (empty($data['Generator']) && !empty($data['X-Generator'])) {
            $data['Generator'] = $data['X-Generator'];
        }
        if (empty($data['Language']) && !empty($data['Locale'])) {
            $data['Language'] = $data['Locale'];
        }
        if (empty($data['Locale']) && !empty($data['Language'])) {
            $data['Locale'] = $data['Language'];
        }
        if (empty($data['Language-Team']) && !empty($data['Team'])) {
            $data['Language-Team'] = $data['Team'];
        }
        if (empty($data['Team']) && !empty($data['Language-Team'])) {
            $data['Team'] = $data['Language-Team'];
        }
        if (empty($data['POT-Creation-Date']) && !empty($data['Creation-Date'])) {
            $data['POT-Creation-Date'] = $data['Creation-Date'];
        }
        if (empty($data['Creation-Date']) && !empty($data['POT-Creation-Date'])) {
            $data['Creation-Date'] = $data['POT-Creation-Date'];
        }
        if (empty($data['Revision-Date']) && !empty($data['PO-Revision-Date'])) {
            $data['Revision-Date'] = $data['PO-Revision-Date'];
        }
        if (empty($data['PO-Revision-Date']) && !empty($data['Revision-Date'])) {
            $data['PO-Revision-Date'] = $data['Revision-Date'];
        }

        $data['Locale'] = $this->normalizeLocale($data['Locale']);
        $data['Language'] = $this->normalizeLocale($data['Language']);
        $data['POT-Creation-Date'] = $this->normalizeDate($data['POT-Creation-Date']);
        $data['Creation-Date'] = $this->normalizeDate($data['POT-Creation-Date']);
        $data['PO-Revision-Date'] = $this->normalizeDate($data['POT-Creation-Date']);
        $data['Revision-Date'] = $this->normalizeDate($data['POT-Creation-Date']);
        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function getDefaults() : array
    {
        return static::DEFAULT_METADATA;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function get(string $name): string
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        $name = $this->normalizeKey($name);
        return $this->data[$name] ?? '';
    }

    /**
     * @param string $name
     *
     * @return mixed|string
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'translation':
                return $this->getTranslation();
            case 'data':
                return $this->getData();
            case 'defaults':
                return $this->getDefaults();
        }
        return property_exists($this, $name)
            ? $this->$name
            : $this->get($name);
    }

    public function __set($name, $value)
    {
        // pass
    }
}

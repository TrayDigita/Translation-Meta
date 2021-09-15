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

use SplFileObject;
use function array_change_key_case;
use function array_merge;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use const CASE_LOWER;

/**
 * Translation only support .json, .po & .mo
 *
 * @property-read null|string $file
 */
class FileTranslation extends AbstractTranslation
{
    /**
     * @var string
     */
    private $file;

    /**
     * @param AbstractTranslations $translations
     * @param string $text_domain
     * @param string|null $file
     */
    public function __construct(
        AbstractTranslations $translations,
        string $text_domain = 'default',
        string $file = null
    ) {
        parent::__construct($translations, $text_domain);
        $this->file     = $file;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @inheritDoc
     * @return AbstractMetaData
     */
    public function getMetaData() : AbstractMetaData
    {
        if ($this->metadata) {
            return $this->metadata;
        }
        $this->metadata = new TranslationMetaData($this);
        if (!$this->file || ! is_string($this->file) || ! is_file($this->file)) {
            return $this->metadata;
        }

        $defaults = [];
        $metadata = $this->metadata->getDefaults();
        $ext = substr($this->file, -3);
        if ($ext === '.mo') {
            foreach ($metadata as $key => $item) {
                $defaults[$key] = preg_quote($key, '~');
            }
            $spl = new SplFileObject($this->file, 'r');
            // set max line to 256 byte chars
            $spl->setMaxLineLen(256);
            foreach ($spl as $line) {
                if (! $defaults) {
                    break;
                }
                foreach ($defaults as $key => $item) {
                    if (preg_match("/^\s*$item\s*:\s*(.+)?$/", $line, $match)
                        && isset($match[1])
                    ) {
                        unset($defaults[$key]);
                        $metadata[$key] = $match[1];
                        break;
                    }
                }
            }
        } elseif ($ext === '.po') {
            foreach ($metadata as $key => $item) {
                $defaults[$key] = '"' . trim($key, '"');
            }
            $metadata = get_file_data(
                $this->file,
                $defaults
            );
            foreach ($metadata as $header => $value) {
                // Remove possible contextual '\n' and closing double quote.
                $metadata[$header] = preg_replace('~(\\\n)?"$~', '', $value);
            }
        } elseif (substr($this->file, -5) === '.json') {
            // json
            $data = @file_get_contents($this->file, false, null, 0, 1024 * 10 * 10);
            if (is_string($data)) {
                $arrayData = json_decode($data, true);
                $defaults = $metadata;
                unset(
                    $defaults['POT-Creation-Date'],
                    $defaults['PO-Revision-Date'],
                    $defaults['Project-Id-Version'],
                    $defaults['X-Generator']
                );
                $defaults = array_merge(
                    [
                        'translation-revision-date' => '',
                        'creation-date' => '',
                        'version' => '',
                        'generator' => '',
                    ],
                    $defaults
                );
                $defaults = array_change_key_case($defaults, CASE_LOWER);
                if ($arrayData === false && strpos(trim($data), '{')) {
                    $arrayData = [];
                    foreach ($defaults as $key => $default) {
                        $arrayData[$key] = '';
                        if (! is_string($key)) {
                            continue;
                        }
                        $quote = preg_quote($key, '~');
                        preg_match(
                            "~
                                [\"](?P<name>$quote)[\"]\s*:\s*
                                (?P<value>
                                    (?i)(?:
                                        true|false|null (?# boolean &null)
                                        |[0-9]+(?:\.[0-9]+)? (?# integer & float)
                                    )|(?=[\"])[\"](?P<string_value>.*)?[\"]
                                )\s*
                                [,}]
                                ~smx",
                            $data,
                            $match
                        );
                        if (empty($match)) {
                            continue;
                        }
                        if (!empty($match['string_value'])) {
                            $arrayData[$key] = stripslashes($match['string_value']);
                        } else {
                            $arrayData[$key] = strtolower($match['value']);
                        }
                    }
                }
                if (is_array($arrayData)) {
                    $metadata['PO-Revision-Date'] = $arrayData['translation-revision-date']??'';
                    $metadata['POT-Creation-Date'] = $arrayData['creation-date']??'';
                    $metadata['Project-Id-Version'] = $arrayData['version']??'';
                    $metadata['X-Generator'] = $arrayData['generator']??'';
                    unset(
                        $defaults['translation-revision-date'],
                        $defaults['creation-date'],
                        $defaults['version'],
                        $defaults['generator']
                    );
                    foreach ($defaults as $key => $item) {
                        if (! is_string($key)) {
                            continue;
                        }
                        $metadata[$key] = (string) ($arrayData[$key] ?? '');
                    }
                }
            }
        }

        unset($spl);
        $this->metadata = new TranslationMetaData($this, $metadata);
        return $this->metadata;
    }

    /**
     * @param string $name
     *
     * @return null|array[]|string|string[]|TranslationMetaData|FileTranslation
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'file':
                return $this->getFile();
        }

        return parent::__get($name);
    }
}

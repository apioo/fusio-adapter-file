<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\File\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\DateTime\DateTime;
use PSX\Http\Writer;
use Symfony\Component\Yaml\Yaml;

/**
 * FileEngine
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileEngine extends ActionAbstract
{
    protected ?string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file;
    }

    public function setFile(?string $file): void
    {
        $this->file = $file;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $sha1  = sha1_file($this->file);
        $mtime = filemtime($this->file);

        $headers = [
            'Last-Modified' => date(DateTime::HTTP, $mtime),
            'ETag' => '"' . $sha1 . '"',
        ];

        $match = $request->getHeader('If-None-Match');
        if (!empty($match)) {
            $match = trim($match, '"');
            if ($sha1 == $match) {
                return $this->response->build(304, $headers, '');
            }
        }

        $since = $request->getHeader('If-Modified-Since');
        if (!empty($since)) {
            if ($mtime < strtotime($since)) {
                return $this->response->build(304, $headers, '');
            }
        }

        $extension = pathinfo($this->file, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'json':
                $data = json_decode(file_get_contents($this->file));
                break;

            case 'yaml':
                $data = Yaml::parse(file_get_contents($this->file));
                break;

            default:
                $data = new Writer\File($this->file);
        }

        return $this->response->build(200, $headers, $data);
    }
}

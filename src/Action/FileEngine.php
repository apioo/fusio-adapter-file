<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\File\Csv;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Request\HttpRequest;
use Fusio\Engine\RequestInterface;
use PSX\DateTime\DateTime;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Exception\InternalServerErrorException;
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
    protected ?string $file = null;

    public function setFile(?string $file): void
    {
        $this->file = $file;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $file  = $this->file ?? throw new InternalServerErrorException('No file configured');
        $sha1  = sha1_file($file);
        $mtime = filemtime($file);

        $headers = [
            'Last-Modified' => date(DateTime::HTTP, $mtime),
            'ETag' => '"' . $sha1 . '"',
        ];

        if ($request instanceof HttpRequest) {
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
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'json':
                $data = $this->wrap(json_decode(file_get_contents($file)), $file);
                break;

            case 'yml':
            case 'yaml':
                $data = $this->wrap(Yaml::parse(file_get_contents($file)), $file);
                break;

            case 'csv':
                $data = $this->wrap(Csv::parseFile($file, $configuration->get('delimiter')), $file);
                break;

            default:
                $data = new Writer\File($file);
        }

        return $this->response->build(200, $headers, $data);
    }

    private function wrap(mixed $value, string $file): \stdClass
    {
        return (object) [
            'fileName' => pathinfo($file, PATHINFO_BASENAME),
            'content' => $value
        ];
    }
}

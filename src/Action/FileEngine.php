<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
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
use PSX\Framework\Http;
use Symfony\Component\Yaml\Yaml;

/**
 * FileEngine
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class FileEngine extends ActionAbstract
{
    /**
     * @var string
     */
    protected $file;

    public function __construct($file = null)
    {
        $this->file = $file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $modifyTime = filemtime($this->file);
        $extension  = pathinfo($this->file, PATHINFO_EXTENSION);

        $headers = [
            'Last-Modified' => date(DateTime::HTTP, $modifyTime),
            'ETag' => '"' . sha1_file($this->file) . '"',
        ];

        switch ($extension) {
            case 'json':
                $data = json_decode(file_get_contents($this->file));
                break;

            case 'yaml':
                $data = Yaml::parse(file_get_contents($this->file));
                break;

            default:
                $data = new Http\Body\File($this->file);
        }

        return $this->response->build(200, $headers, $data);
    }
}

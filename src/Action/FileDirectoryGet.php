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

use Fusio\Engine\ConfigurableInterface;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Exception as StatusCode;

/**
 * FileDirectoryGet
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryGet extends FileEngine implements ConfigurableInterface
{
    use FileDirectoryTrait;

    public function getName(): string
    {
        return 'File-Directory-Get';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $directory = $this->getDirectory($configuration);

        $id = $request->get('id');
        if (empty($id)) {
            throw new StatusCode\BadRequestException('No id provided');
        }

        $file = $this->findFileById($directory, $id);
        if (empty($file)) {
            throw new StatusCode\NotFoundException('Provided id does not exist');
        }

        $this->setFile($file);

        return parent::handle($request, $configuration, $context);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
        $builder->add($elementFactory->newInput('delimiter', 'Delimiter', 'text', 'Optional a delimiter for CSV files default is ";"'));
    }

    private function findFileById(string $directory, string $id): ?string
    {
        $files = $this->getFilesInDirectory($directory);
        foreach ($files as $file) {
            if ($this->getUuidForFile($file) === $id) {
                return $directory . '/' . $file;
            }
        }

        return null;
    }
}

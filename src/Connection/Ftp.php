<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\File\Connection;

use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use League\Flysystem\Adapter;
use League\Flysystem\Filesystem as Flysystem;

/**
 * Ftp
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Ftp implements ConnectionInterface
{
    public function getName()
    {
        return 'FTP';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getConnection(ParametersInterface $config)
    {
        $port = (int) $config->get('port');
        if (empty($port)) {
            $port = 21;
        }

        $passive = (bool) $config->get('passive');
        $ssl = (bool) $config->get('ssl');

        $adapter = [
            'host' => $config->get('host'),
            'port' => $port,
            'username' => $config->get('username'),
            'password' => $config->get('password'),
            'root' => $config->get('root'),
            'passive' => $passive,
            'ssl' => $ssl,
            'timeout' => 30,
        ];

        return new Flysystem(new Adapter\Ftp($adapter));
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $builder->add($elementFactory->newInput('host', 'Host', 'text', 'FTP host'));
        $builder->add($elementFactory->newInput('port', 'Port', 'number', 'FTP port (default is 21)'));
        $builder->add($elementFactory->newInput('username', 'Username', 'text', 'FTP username'));
        $builder->add($elementFactory->newInput('password', 'Password', 'text', 'FTP password'));
        $builder->add($elementFactory->newInput('root', 'Root', 'text', 'Optional the root dir'));
        $builder->add($elementFactory->newSelect('passive', 'Passive', [0 => 'No', 1 => 'Yes'], 'Use passive mode'));
        $builder->add($elementFactory->newSelect('ssl', 'SSL', [0 => 'No', 1 => 'Yes'], ''));
    }
}

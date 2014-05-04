<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2014 ActiveMongo                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/

namespace crodas\ActiveMongo2;

use ActiveMongo2\Configuration;
use ActiveMongo2\Connection as AMongoConnection;
use Illuminate\Database\Connection as LConnection;
use MongoClient;
use Illuminate\Support;

/**
 *  Wrap ActiveMongo service so it can be used
 *  in a laravel way.
 */
class Connection extends LConnection
{
    protected $instance;

    public function __construct (AMongoConnection $mong)
    {   
        $this->instance =  $mong;
    }
}

class ServiceProvider extends Support\ServiceProvider
{
    public function register()
    {
        $app = $this->app;
        $app['db']->extend('activemongo', function($config) use ($app) {
            $murl = "mongodb://";
            $port = (array)(empty($config['port']) ? 27017 : $config['port']);
            foreach ((array)$config['host'] as $id => $host) {
                $murl .= "{$host}:{$port[$id]}";
            }

            $tempMapper    = $app['config']['cache.path'] . "/activemongo2." . sha1($murl) . ".php";
            $connection    = new MongoClient($murl);
            $configuration = new Configuration($tempMapper);
            $configuration->addModelPath(app_path() . "/models");

            if (empty($app['config']['debug'])) {
                $configuration->development();
            }

            
            /**
             *  Wrap ActiveMongo\Connection so laravel can understand it
             */
            return new Connection(
                new AMongoConnection($configuration, $connection, $config['database'])
            );
        });
    }
}


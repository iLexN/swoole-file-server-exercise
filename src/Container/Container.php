<?php
declare(strict_types=1);

namespace App\FileServer\Container;

use App\FileServer\Controller\DownloadFromServer;
use GuzzleHttp\Client;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Mime\MimeTypes;

final class Container
{

    public function __construct(
        private \WeakMap $cache = new \WeakMap()
    ) {

    }

    public function mineTypes(): MimeTypes
    {
        $id = ServiceType::MineTypes;
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $this->cache[$id] = new MimeTypes();
        return $this->cache[$id];
    }

    public function logger(): Logger
    {
        $id = ServiceType::Logger;
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $logger = new Logger('App');
        $logger->pushHandler(
            new ErrorLogHandler(
                ErrorLogHandler::SAPI,
                Level::Debug
            )
        );
        $this->cache[$id] = $logger;
        return $this->cache[$id];
    }

    public function controllerDownload(): DownloadFromServer
    {
        $id = Controller::Download;
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $obj = new DownloadFromServer($this);

        $this->cache[$id] = $obj;
        return $this->cache[$id];
    }

    public function httpClient(): Client
    {
        $id = ServiceType::HttpClient;
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $obj = new Client([
            // Base URI is used with relative requests
            'base_uri' => $_ENV[StringVariable::TargetHost->value],
            // You can set any number of default request options.
            'timeout' => 5.0,
        ]);

        $this->cache[$id] = $obj;
        return $this->cache[$id];
    }

    public function accessToken():string{
        $id = StringVariable::AccessToken;
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        $this->cache[$id] = $_ENV[$id->value];
        return $this->cache[$id];
    }
}

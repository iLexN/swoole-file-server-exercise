<?php
declare(strict_types=1);

namespace App\FileServer\Controller;

use App\FileServer\Container\Container;
use App\FileServer\Container\StringVariable;
use Monolog\Logger;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Mime\MimeTypes;

final class Download
{

    protected Logger $logger;

    private MimeTypes $mineTypes;

    public function __construct(Container $container)
    {
        $this->logger = $container->logger();
        $this->mineTypes = $container->mineTypes();
    }

    public function __invoke(Request $request, Response $response): void
    {
        $file = StringVariable::DirRoot->value . $request->server['request_uri'];
        $this->logger->info('file:', [$request->server['request_uri']]);
        if (!file_exists($file)) {
            $response->status(404);
            $this->logger->info('File not exist', [
                $request->server['request_uri'],
            ]);
            return;
        }

        $mimeType = $this->mineTypes->guessMimeType($file);
        $response->header('Content-Type', $mimeType);
        $response->sendfile($file);
    }
}

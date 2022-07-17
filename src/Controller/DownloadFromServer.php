<?php
declare(strict_types=1);

namespace App\FileServer\Controller;

use App\FileServer\Container\Container;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class DownloadFromServer
{

    /**
     * 2M
     */
    private const CHUNK_SIZE = 2097152;

    protected Logger $logger;

    private Client $client;

    private string $accessToken;

    public function __construct(Container $container)
    {
        $this->logger = $container->logger();
        $this->client = $container->httpClient();
        $this->accessToken = $container->accessToken();
    }

    public function __invoke(
        Request $request,
        Response $response
    ): void {
        $uri = $request->server['request_uri'];
        $this->logger->info('file:', [$uri]);

        try {
            $httpResponse = $this->client->get($uri, [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ],
                RequestOptions::STREAM => true,
                RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (\Throwable $throwable) {
            $this->logger->info('Curl error', [
                'file' => $uri,
                'error' => $throwable->getMessage(),
            ]);
            $response->setStatusCode(500);
            $response->end('Error');
            return;
        }

        if ($httpResponse->getStatusCode() >= 400) {
            $response->setStatusCode(
                $httpResponse->getStatusCode(),
                $httpResponse->getReasonPhrase()
            );
            $this->logger->info("File Response error", [
                $httpResponse->getStatusCode(),
                $httpResponse->getReasonPhrase(),
            ]);
            return;
        }

        $this->setHeader($response, $httpResponse);
        $this->setBody($httpResponse, $response);
        $this->logger->info('File done:', [$uri]);
    }

    /**
     * @param \Swoole\Http\Response $response
     * @param \Psr\Http\Message\ResponseInterface $httpResponse
     *
     * @return void
     */
    private function setHeader(
        Response $response,
        ResponseInterface $httpResponse
    ): void {
        $response->header(
            'Content-Type',
            $httpResponse->getHeader('Content-Type')[0]
        );
        $response->header(
            'Content-Length',
            $httpResponse->getHeader('Content-Length')[0]
        );
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $httpResponse
     * @param \Swoole\Http\Response $response
     *
     * @return void
     */
    private function setBody(
        ResponseInterface $httpResponse,
        Response $response,
    ): void {
        $stream = $httpResponse->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (!$stream->isReadable()) {
            $response->end((string)$stream);
            return;
        }

        if ($stream->getSize() !== null && $stream->getSize() <= self::CHUNK_SIZE) {
            $response->end($stream->getContents());
            return;
        }

        while (!$stream->eof()) {
            $response->write($stream->read(self::CHUNK_SIZE));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Service\ApiClient\Client as ApiClient;

class SharedSpaceService
{
    public function __construct(private ApiClient $client, private LoggerInterface $logger)
    {
    }

    /**
     * @return array{results: array, total: int}|false
     */
    public function matchSharedSpaces(string $fullOrPartialName, array $options = []): bool|array
    {
        try {
            $response = $this->client->httpGet('/v2/admin/match-shared-spaces', array_merge(
                ['fullOrPartialName' => $fullOrPartialName],
                $options
            ));

            if (is_array($response) && isset($response['results']) && is_array($response['results'])) {
                return [
                    'results' => $response['results'],
                    'total' => intval($response['total'] ?? 0),
                ];
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Match shared spaces failed', [
                'exception' => $e,
            ]);
            return false;
        }
    }
}

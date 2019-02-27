<?php

declare(strict_types=1);

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Wraps the Monolog logger to have a dedicated log file per import.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2019 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FileLogger
{
    public $numSkipped = 0;
    public $numCreated = 0;
    public $numUpdated = 0;

    /** @var LoggerInterface */
    private $logger;

    /** @var ParameterBagInterface */
    private $params;

    /** @var string */
    private $logFilePath;

    public function __construct(LoggerInterface $logger, ParameterBagInterface $params)
    {
        $this->logger = $logger;
        $this->params = $params;
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function startLogging()
    {
        $this->logFilePath = $this->generateLogFilePath();
        $this->logger->pushHandler(new StreamHandler($this->logFilePath, Logger::DEBUG, false));
    }

    public function logResponses(array $responses)
    {
        foreach ($responses as $response) {
            $statusCode = $response['status_code'];

            if($statusCode >= 200 && $statusCode < 300) {
                if (201 === $statusCode) {
                    $this->numCreated++;
                }

                if (204 === $statusCode) {
                    $this->numUpdated++;
                }
            } else {
                $this->numSkipped++;
                $this->logger->warning(
                    sprintf('Skipped record "%s", an error occured during import: %s', $response['code'], json_encode($response['errors']))
                );
            }
        }
    }

    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    private function generateLogFilePath(): string
    {
        return sprintf(
            '%s/import-%s.log',
            $this->params->get('kernel.logs_dir'),
            date('Y-m-d-H-i-s')
        );
    }
}

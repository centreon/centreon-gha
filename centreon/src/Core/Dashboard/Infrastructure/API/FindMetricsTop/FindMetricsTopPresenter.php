<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

 declare(strict_types=1);

namespace Core\Dashboard\Infrastructure\API\FindMetricsTop;

use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\Application\Common\UseCase\AbstractPresenter;
use Core\Application\Common\UseCase\ResponseStatusInterface;
use Core\Dashboard\Application\UseCase\FindMetricsTop\FindMetricsTopPresenterInterface;
use Core\Dashboard\Application\UseCase\FindMetricsTop\FindMetricsTopResponse;
use Core\Dashboard\Application\UseCase\FindMetricsTop\Response\MetricInformationDto;
use Core\Dashboard\Domain\Model\Metric\ResourceMetric;
use Core\Infrastructure\Common\Presenter\PresenterFormatterInterface;

class FindMetricsTopPresenter extends AbstractPresenter implements FindMetricsTopPresenterInterface
{
    public function __construct(private RequestParametersInterface $requestParameters, protected PresenterFormatterInterface $presenterFormatter)
    {
        parent::__construct($presenterFormatter);
    }

    public function presentResponse(FindMetricsTopResponse|ResponseStatusInterface $response): void
    {
        if ($response instanceof ResponseStatusInterface) {
            $this->setResponseStatus($response);
        } else {
            $this->present(
                [
                    'result' => [
                        'name' => $response->metricName,
                        'unit' => $response->metricUnit,
                        'resources' => self::formatResource($response->resourceMetrics),
                    ],
                    'meta' => $this->requestParameters->toArray(),
                ]
            );
        }
    }

    private function formatSorting(): string
    {
        $sort = $this->requestParameters->getSort();
        return $sort[0] === 'ASC' ? 'top' : 'bottom';
    }

    /**
     * @param ResourceMetric[] $resourceMetrics
     * @return array
     */
    private static function formatResource(array $resourceMetrics): array {
        return array_map(function (MetricInformationDto $metricInformation) {
            return [
                'id' => $metricInformation->serviceId,
                'name' => $metricInformation->resourceName,
                'current_value' => $metricInformation->currentValue,
                'warning_threshold' => $metricInformation->warningHighThreshold,
                'critical_threshold' => $metricInformation->criticalHighThreshold,
                'warning_low_threshold' => $metricInformation->warningLowThreshold,
                'critical_low_threshold' => $metricInformation->criticalLowThreshold,
                'min' => $metricInformation->minimumValue,
                'max' => $metricInformation->maximumValue,
            ];
        }, $resourceMetrics);
    }
}

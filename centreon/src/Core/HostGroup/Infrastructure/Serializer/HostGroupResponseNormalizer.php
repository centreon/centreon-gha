<?php

/*
 * Copyright 2005 - 2025 Centreon (https://www.centreon.com/)
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

namespace Core\HostGroup\Infrastructure\Serializer;

use Core\Common\Domain\SimpleEntity;
use Core\HostGroup\Application\UseCase\FindHostGroups\HostGroupResponse;
use Core\HostGroup\Application\UseCase\GetHostGroup\GetHostGroupResponse;
use Core\ResourceAccess\Domain\Model\TinyRule;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class HostGroupResponseNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $normalizer,
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null)
    {
        return $data instanceof HostGroupResponse
        || $data instanceof GetHostGroupResponse;
    }

    /**
     * @param GetHostGroupResponse|HostGroupResponse $object
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @throws \Throwable
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = [])
    {
        /** @var array<string, bool|float|int|string> $data */
        $data = $this->normalizer->normalize($object->hostgroup, $format, $context);

        if (isset($data['alias']) && $data['alias'] === '') {
            $data['alias'] = null;
        }
        if (isset($data['comment']) && $data['comment'] === '') {
            $data['comment'] = null;
        }

        if (in_array('HostGroup:List', $context['groups'], true)) {
            $data['enabled_hosts_count'] = $object->hostsCount
                ? $object->hostsCount->getEnabledHostsCount()
                : 0;
            $data['disabled_hosts_count'] = $object->hostsCount
                ? $object->hostsCount->getDisabledHostsCount()
                : 0;
        }
        if (in_array('HostGroup:Get', $context['groups'], true)) {
            $data['hosts'] = array_map(
                fn (SimpleEntity $host) => $this->normalizer->normalize($host, $format),
                $object->hosts
            );

            /** @var array{groups: string[]} $context */
            if (true === ($context['is_cloud_platform'] ?? false)) {
                $data['resource_access_rules'] = array_map(
                    fn (TinyRule $rule) => $this->normalizer->normalize($rule, $format, $context),
                    $object->rules
                );
            }
        }

        return $data;
    }
}

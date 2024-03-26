<?php

/*
 * Copyright 2005 - 2020 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
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

namespace Centreon\Application\Controller;

use JsonSchema\Validator;
use Centreon\Domain\Contact\Contact;
use Centreon\Domain\Log\LoggerTrait;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Abstraction over the FOSRestController
 *
 * @package Centreon\Application\Controller
 */
abstract class AbstractController extends AbstractFOSRestController
{
    use LoggerTrait;

    protected const ROLE_UNAUTHORIZED_EXCEPTION_MESSAGE = 'You are not authorized to access this resource';

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessAdmin(): void
    {
        parent::denyAccessUnlessGranted(
            Contact::ROLE_ADMIN,
            null,
            static::ROLE_UNAUTHORIZED_EXCEPTION_MESSAGE
        );
    }

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedForApiConfiguration(): void
    {
        parent::denyAccessUnlessGranted(
            Contact::ROLE_API_CONFIGURATION,
            null,
            static::ROLE_UNAUTHORIZED_EXCEPTION_MESSAGE
        );
    }

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedForApiRealtime(): void
    {
        parent::denyAccessUnlessGranted(
            Contact::ROLE_API_REALTIME,
            null,
            static::ROLE_UNAUTHORIZED_EXCEPTION_MESSAGE
        );
    }

    /**
     * Get current base uri
     *
     * @return string
     */
    protected function getBaseUri(): string
    {
        $baseUri = '';

        if (
            isset($_SERVER['REQUEST_URI'])
            && preg_match(
                '/^(.+)\/((api|widgets|modules|include|authentication)\/|main(\.get)?\.php).+/',
                $_SERVER['REQUEST_URI'],
                $matches
            )
        ) {
            $baseUri = $matches[1];
        }

        return $baseUri;
    }

    /**
     * Validate the data sent.
     *
     * @param Request $request Request sent by client
     * @param string $jsonValidationFile Json validation file
     * @throws \InvalidArgumentException
     */
    protected function validateDataSent(Request $request, string $jsonValidationFile): void
    {
        // We want to enforce the decoding as possible objects.
        $receivedData = json_decode((string) $request->getContent(), false);
        if (!is_array($receivedData) && ! ($receivedData instanceof \stdClass)) {
            throw new \InvalidArgumentException('Error when decoding your sent data');
        }

        $validator = new Validator();
        $validator->validate(
            $receivedData,
            (object) [
                '$ref' => 'file://' . realpath(
                    $jsonValidationFile
                )
            ],
            Constraint::CHECK_MODE_VALIDATE_SCHEMA
        );

        if (!$validator->isValid()) {
            $message = '';
            $this->error('Invalid request body');
            foreach ($validator->getErrors() as $error) {
                $message .= ! empty($error['property'])
                    ? sprintf("[%s] %s\n", $error['property'], $error['message'])
                    : sprintf("%s\n", $error['message']);
            }
            throw new \InvalidArgumentException($message);
        }
    }


    /**
     * Validate the data sent and retrieve it.
     *
     * @param Request $request Request sent by client
     * @param string $jsonValidationFile Json validation file
     * @return array<string, mixed>
     * @throws \InvalidArgumentException
     */
    protected function validateAndRetrieveDataSent(Request $request, string $jsonValidationFile): array
    {
        $this->validateDataSent($request, $jsonValidationFile);
        return json_decode((string) $request->getContent(), true);
    }
}

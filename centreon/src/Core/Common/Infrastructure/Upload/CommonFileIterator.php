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

declare(strict_types = 1);

namespace Core\Common\Infrastructure\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CommonFileIterator implements FileIteratorInterface
{
    private int $fileIndex = 0;

    private int $fileNumber = 0;

    private string $currentFilename = '';

    /** @var list<UploadedFile> */
    private array $files = [];

    /**
     * @param UploadedFile $file
     */
    public function addFile(UploadedFile $file): void
    {
        $this->files[] = $file;
        $this->fileNumber++;
    }

    public function count(): int
    {
        return $this->fileNumber;
    }

    /**
     * @return string
     */
    public function current(): string
    {
        $this->currentFilename = $this->files[$this->fileIndex]->getClientOriginalName();

        return $this->files[$this->fileIndex]->getContent();
    }

    public function next(): void
    {
        $this->fileIndex++;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->currentFilename;
    }

    public function valid(): bool
    {
        return $this->fileIndex < $this->fileNumber;
    }

    public function rewind(): void
    {
        $this->fileIndex = 0;
    }
}

<?php

namespace Tobuli\Importers;

use Symfony\Component\HttpFoundation\File\File;

interface RemapInterface
{
    public function getHeaders(File $file): array;

    public function remapHeaders(array &$headers);

    public function setFieldsRenameMap(array $fieldsRenameMap);
}
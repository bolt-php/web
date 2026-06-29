<?php

namespace framework\web\transformers;

use framework\validation\interfaces\TypeTransformer;
use framework\web\request\UploadedFile;

class UploadedFileTransformer implements TypeTransformer
{
    /**
     * Convert database path to UploadedFile object.
     * Note: This object will only have the 'path' property populated.
     */
    public function transformFromDatabase($value)
    {
        if (empty($value)) {
            return null;
        } else if ($value instanceof UploadedFile) {
            return $value;
        }
        return new UploadedFile(['original_path' => $value, 'path' => app()->path->resolve($value), 'size' => 1, 'error' => UPLOAD_ERR_OK]);
    }

    /**
     * Move the uploaded file to the /uploads directory and return its new path.
     */
    public function transformToDatabase($value)
    {
        if ($value instanceof UploadedFile) {
            $result = $value->move('/uploads');
            return $result['path'];
        }
        return $value;
    }

    /**
     * An uploaded file input is considered empty when no file was selected.
     * This prevents overwriting the previously saved value during updates.
     */
    public function isEmpty($value): bool
    {
        if ($value instanceof UploadedFile) {
            return !$value->isValid();
        }
        return empty($value);
    }
}

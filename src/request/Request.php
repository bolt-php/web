<?php

namespace framework\web\request;

use framework\contracts\request\RequestInterface;
use stdClass;

/**
 * Request Class
 *
 * This class provides several helper
 * methods and utilities over the raw
 * $_GET, $_POST, $_FILES, etc. superglobals.
 */
class Request implements RequestInterface
{
    protected $vals;
    protected $files;

    public function __construct()
    {
        // Organize and wrap files
        $this->files = $this->organizeFiles($_FILES);
    }

    /**
     * Recursively reorganizes $_FILES structure to a more natural one.
     * 
     * PHP's structure for multi-file uploads (e.g. name="docs[]") is:
     * $_FILES['docs']['name'][0], $_FILES['docs']['tmp_name'][0], etc.
     * This reorganizes it into:
     * $this->files['docs'][0] = UploadedFile(...)
     */
    protected function organizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $name => $file) {
            if (isset($file['name']) && is_array($file['name'])) {
                $normalized[$name] = $this->fixPhpFilesStructure($file);
            } else {
                $normalized[$name] = new UploadedFile($file);
            }
        }
        return $normalized;
    }

    protected function fixPhpFilesStructure(array $file): array
    {
        $fixed = [];
        foreach (array_keys($file['name']) as $key) {
            $data = [
                'name'     => $file['name'][$key],
                'type'     => $file['type'][$key],
                'tmp_name' => $file['tmp_name'][$key],
                'error'    => $file['error'][$key],
                'size'     => $file['size'][$key],
            ];

            if (is_array($data['name'])) {
                $fixed[$key] = $this->fixPhpFilesStructure($data);
            } else {
                $fixed[$key] = new UploadedFile($data);
            }
        }
        return $fixed;
    }

    public function __get($name)
    {
        if (isset($this->vals[$name])) {
            return $this->vals[$name];
        }
    }

    public function path()
    {
        return app()->url->path();
    }

    /**
     * Get a value from the $_GET superglobal, with an optional default.
     * 
     * @param string $key The key to retrieve from the $_GET array.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_GET if set, otherwise the default value.
     */
    public function get(string $key = '', $default = null)
    {
        if ($key === '') {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a value from the $_POST superglobal, with an optional default.
     * @param string $key The key to retrieve from the $_POST array.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_POST if set, otherwise the default value.
     */
    public function post(string $key = '', $default = null)
    {
        if ($key === '') {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get a value from either $_POST or $_GET, with an optional default. POST takes precedence over GET.
     * @param string $key The key to retrieve from the $_POST or $_GET arrays.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_POST if set, otherwise the value from $_GET, or the default value.
     */
    public function input(string $key = '', $default = null)
    {
        if (empty($key)) {
            return array_merge($this->get(), $this->post());
        }
        return $this->post($key, $this->get($key, $default));
    }

    /**
     * Get the HTTP method of the request, supporting method override via a __method query parameter. Defaults to GET if not specified.
     * @return string The HTTP method of the request.
     */
    public function method()
    {
        return $_GET['__method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function file(string $key = '')
    {
        return $this->files($key);
    }

    public function files(string $key = '')
    {
        if (empty($key)) {
            return $this->files;
        }

        // Support dot notation for nested access
        $parts = explode('.', $key);
        $value = $this->files;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Attach a new variable to $request instance
     * 
     * This can be used to attach auth status, flags, statuses, etc. to the request
     * object. They can later be accessed similar to a regular property.
     * 
     * Example:
     * Request::put('data', 'test');
     * $data = $request->data;
     */
    public function put($key, $value)
    {
        $this->vals[$key] = $value;
    }

    /**
     * Validates the current request
     * 
     * This function automatically redirects to the previous form
     * and errors can then be displayed using `@error` directive.
     */
    public function validate(array|string $rules)
    {
        $data = $this->input(); // or all()

        $errors = app()->validator->validate((object) $data, $rules);

        if ($errors) {
            // store errors + old input in session
            app()->session->set('errors', $errors);
            app()->session->set('old', $data);

            // redirect back
            app()->url->back();
            exit;
        }

        return true;
    }
}
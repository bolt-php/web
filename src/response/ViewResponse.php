<?php

namespace framework\web\response;

use framework\web\utils\ViewCompiler;

class ViewResponse extends HttpResponse
{
    protected $path;
    protected $data;

    public function __construct($path, $params)
    {
        $this->path = $path;
        $this->data = $params;
    }

    protected function compiler()
    {
        $path = app()->path;

        if (str_starts_with($this->path, '@')) {
            $base = explode('.', $this->path);
            $dir = $path->resolve(substr($base[0] . '/', 0));
            $this->path = substr($this->path, strlen($base[0]) + 1);
        }
        else if (preg_match('/([a-z_]+)::(.+)/', $this->path, $matches)) {
            // The view is namepsaced
            $dir = $path->namespaced($matches[1], 'views');
            $this->path = substr($this->path, strlen($matches[1]) + 2);
        }
        else {
            $dir = $path->resolve('@views/');
        }

        return new ViewCompiler(
            $dir,
            $path->resolve('@runtime/views')
        );
    }

    public function render()
    {
        $compiler = $this->compiler();

        $compiler->render($this->path, $this->data);
    }

    public function exists()
    {
        $compiler = $this->compiler();

        return $compiler->exists($this->path);
    }

    public function with($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }
}
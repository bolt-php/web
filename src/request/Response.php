<?php

namespace framework\web\request;

use framework\contracts\request\ResponseInterface;
use framework\web\response\ViewResponse;
use framework\web\exceptions\NotFoundException;

class Response implements ResponseInterface {
    /**
     * Send a plain text response
     * @param string $content
     */
    public function send($content) {
        echo $content;
    }

    /**
     * Send a JSON response
     * @param mixed $data
     */
    public function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect to a different URL
     * @param string $url
     */
    public function redirect($url) {
        $url = app()->url->to($url);
        header("Location: $url");
        exit;
    }

    /**
     * Render a view template
     * @param string $template
     * @param array $data
     * @return ViewResponse
     */
    public function view($template, $data = []) {
        return new ViewResponse($template, $data);
    }

    /**
     * Return a file response
     * @param string $path
     * @throws NotFoundException
     */
    public function file($path) {
        if (!file_exists($path) || is_dir($path)) {
            throw new NotFoundException("File not found: $path");
        }

        $mimeType = mime_content_type($path);
        header("Content-Type: $mimeType");
        header("Content-Length: " . filesize($path));
        
        readfile($path);
        exit;
    }
}
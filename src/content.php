<?php
require_once(__DIR__ . '/request_result.php');

class Content
{
    private string $content;
    private ?string $content_template;

    protected function __construct(string $content, ?string $content_template)
    {
        $this->content = $content;
        $this->content_template = $content_template;
    }

    public static function success(string $content, ?string $content_template = "success"): Content
    {
        return new Content($content, $content_template);
    }

    public static function error(string $content, ?string $content_template = "error"): Content
    {
        return new Content($content, $content_template);
    }

    public function result(): RequestResult
    {
        return RequestResult::withContent($this);
    }

    public function generate(): string
    {
        if ($this->content_template) {
            require_once(__DIR__ . "/template.php");
            return use_template("content/{$this->content_template}", [
                "content" => $this->content,
                "header" => generate_header()
            ]);
        } else {
            return $this->content;
        }
    }
}

function generate_header(): string
{
    $logged_in = array_key_exists('onedrive.client.state', $_SESSION);
    return use_template('header', ['logged_in' => $logged_in]);
}

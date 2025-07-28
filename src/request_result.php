<?php
declare(strict_types=1);

namespace PHPOneDrive;

require_once(__DIR__ . '/content.php');

class RequestResult
{
    private array $headers = [];
    private ?Content $page_content;
    private ?string $page_template;

    protected function __construct(?Content $page_content, ?string $page_template)
    {
        $this->page_content = $page_content;
        $this->page_template = $page_template;
    }

    public static function withContent(Content $content): RequestResult
    {
        return new RequestResult($content, "index");
    }

    public static function download(string $file_content, string $mime_type, int $size, string $name): RequestResult
    {
        $request_result = new RequestResult(Content::success($file_content, null), null);
        $request_result->add_header("Content-Type: {$mime_type}");
        $request_result->add_header("Content-Length: {$size}");
        $request_result->add_header("Content-Disposition: attachment; filename=\"{$name}\"");
        $request_result->add_header("Content-Transfer-Encoding: binary");
        return $request_result;
    }

    public static function redirect(string $url): RequestResult
    {
        $request_result = new RequestResult(null, null);
        $request_result->add_header('HTTP/1.1 302 Found', true, 302);
        $request_result->add_header("Location: $url");
        return $request_result;
    }

    public function output(): void
    {
        foreach ($this->headers as $h)
            call_user_func_array("header", $h);

        if ($this->page_content) {
            if ($this->page_template) {
                require_once(__DIR__ . "/template.php");
                echo use_template("page/{$this->page_template}", ["content" => $this->page_content->generate()]);
            } else {
                echo $this->page_content->generate();
            }
        }
    }

    // same parameters as the PHP header() function
    public function add_header(string $header, bool $replace = true, int $response_code = 0): void
    {
        $this->headers[] = [$header, $replace, $response_code];
    }
}

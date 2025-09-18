<?php
namespace Core;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public function status(int $code): self { $this->status = $code; return $this; }
    public function header(string $k, string $v): self { $this->headers[$k] = $v; return $this; }
    public function html(string $content): self { $this->header('Content-Type','text/html; charset=utf-8'); $this->body = $content; return $this; }
    public function json($data): self { $this->header('Content-Type','application/json'); $this->body = json_encode($data, JSON_UNESCAPED_UNICODE); return $this; }
    public function redirect(string $to, int $code=302): never { header('Location: '.$to, true, $code); exit; }

    public function send(): void {
        http_response_code($this->status);
        foreach ($this->headers as $k=>$v) header("$k: $v");
        echo $this->body;
    }
}

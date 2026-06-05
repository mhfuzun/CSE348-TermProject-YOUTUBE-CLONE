<?php

/**
 * View:
 *  - görüntüleme için gerekli tanımları içerir.
 *  - her control sınıfı bir view oluşturur. bu sayede döüş anında ekrana gerekli içerik basılır.
 *  - ayrıca html içerik ekrana basılırken gerekli değişkenler'de $data içinden sağlanır.
 *  - bu değişkenler için DTO kullanılması tercih edilir.
 */
class View {
    private string $page;
    private string $title;
    private array $data;
    private string $layout;

    public function __construct(
        string $page,
        string $title,
        array $data = [],
        string $layout = 'main'
    ) {
        $this->page = $page;
        $this->title = $title;
        $this->data = $data;
        $this->layout = $layout;
    }

    // app içinden çağrılan render edici method
    public function render(): string {
        $pagePath = $this->pagePath($this->page);
        $layoutPath = $this->pagePath($this->layout);

        $title = $this->title;
        $page = $this->page;
        extract($this->data, EXTR_SKIP);

        ob_start(); // store all print command in output buffer
        require $pagePath;
        $content = ob_get_clean(); // store output buffer in content variable

        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }

    // page içinden güvenli değişken çekme, xss attack engeller
    public static function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    // güvenli page getirme method'u
    private function pagePath(string $page): string {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $page)) {
            throw new InvalidArgumentException("Invalid page name: {$page}");
        }

        $path = __DIR__ . "/../pages/{$page}.php";

        if (!is_file($path)) {
            throw new RuntimeException("Page file not found: {$page}");
        }

        return $path;
    }

    public function addData($key, $value): void {
        $this->data[$key] = $value;
    }
}

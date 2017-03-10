<?php
namespace Ant\Foundation\Http\Api\Decorator;

/**
 * Class FileRenderer
 * @package Ant\Foundation\Http\Api\Decorator
 */
class FileRenderer extends Renderer
{
    public $type = 'application/octet-stream';

    public $fileName = 'example.txt';

    /**
     * {@inheritDoc}
     */
    public function decorate()
    {
        if (!is_string($this->package) && !is_integer($this->package)) {
            throw new \RuntimeException('Response content must be string');
        }

        $response = $this->response;

        $headers = [
            'Content-Type' => $this->type,
            "Content-Disposition" => "attachment; filename=\"{$this->fileName}\"",
            'Content-Transfer-Encoding' => 'binary',
        ];

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name,$value);
        }

        $response->getBody()->write($this->package);

        return $response;
    }
}